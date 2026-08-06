<?php

namespace App\Monitoring;

use App\Checkers\CheckResult;
use App\Models\Incident;
use App\Models\Monitor;
use App\Models\MonitorCheck;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Turns a raw check result into persisted state: the check row, the monitor's
 * confirmed status, the incident timeline, and any alerts that fall out of a
 * status transition.
 */
class StatusEvaluator
{
    public function __construct(private readonly AlertDispatcher $alerts) {}

    /**
     * Deadlocks here used to lose the check entirely: the job runs with
     * tries=1, so a rolled-back transaction meant the result was never
     * recorded. Retrying the transaction (not the network check) recovers
     * from the transient case without re-probing the target.
     */
    private const TRANSACTION_ATTEMPTS = 3;

    public function record(Monitor $monitor, CheckResult $result, ?CarbonInterface $checkedAt = null): MonitorCheck
    {
        $checkedAt ??= now();

        $evaluation = DB::transaction(
            fn () => $this->persist($monitor, $result, $checkedAt),
            self::TRANSACTION_ATTEMPTS,
        );

        $this->announce($monitor, $result, $evaluation);

        return $evaluation->check;
    }

    /**
     * Fired outside the transaction: a queued job must never be visible to a
     * worker before the rows it describes are committed.
     */
    private function announce(Monitor $monitor, CheckResult $result, Evaluation $evaluation): void
    {
        match ($evaluation->transition) {
            Transition::WentDown => $this->alerts->dispatch(
                $monitor,
                AlertMessage::down($monitor, $result->error, $evaluation->incident),
            ),
            Transition::Recovered => $this->alerts->dispatch(
                $monitor,
                AlertMessage::recovered($monitor, $evaluation->incident),
            ),
            Transition::None => null,
        };

        match ($evaluation->degradation) {
            Degradation::Began => $this->alerts->dispatch(
                $monitor,
                AlertMessage::degraded($monitor, $result->responseMs, (int) $monitor->degraded_response_ms),
            ),
            Degradation::Ended => $this->alerts->dispatch(
                $monitor,
                AlertMessage::improved($monitor, $result->responseMs),
            ),
            Degradation::None => null,
        };
    }

    private function persist(Monitor $monitor, CheckResult $result, CarbonInterface $checkedAt): Evaluation
    {
        // Take the monitor row lock FIRST, before touching monitor_checks or
        // incidents. Every writer that reaches this table now acquires locks
        // in the same order, which is what stops the dispatcher and the
        // workers from deadlocking against each other.
        //
        // It also re-reads the row, so the streak arithmetic below starts from
        // committed state on every attempt — a retried transaction must not
        // increment a streak the rolled-back attempt already bumped.
        $locked = Monitor::query()->whereKey($monitor->getKey())->lockForUpdate()->first();

        if ($locked !== null) {
            $monitor->setRawAttributes($locked->getAttributes(), sync: true);
        }

        $check = MonitorCheck::create([
            'monitor_id' => $monitor->id,
            'is_up' => $result->isUp,
            'response_ms' => $result->responseMs,
            'error' => $result->error,
            'meta' => $result->meta ?: null,
            'checked_at' => $checkedAt,
        ]);

        $previous = $monitor->latest_is_up;

        $monitor->failure_streak = $result->isUp ? 0 : $monitor->failure_streak + 1;
        $monitor->success_streak = $result->isUp ? $monitor->success_streak + 1 : 0;

        $confirmed = $this->confirmedStatus($monitor, $result, $previous);
        $transition = $this->transitionFor($previous, $confirmed);
        $degradation = $this->applyDegradation($monitor, $result, $confirmed);

        $monitor->latest_is_up = $confirmed;
        $monitor->last_checked_at = $checkedAt;
        $monitor->next_check_at = $monitor->nextCheckFrom($checkedAt);

        if ($previous !== $confirmed) {
            $monitor->status_changed_at = $checkedAt;
        }

        $monitor->save();

        $incident = match ($transition) {
            Transition::WentDown => $this->openIncident($monitor, $result, $checkedAt),
            Transition::Recovered => $this->resolveIncident($monitor, $checkedAt),
            Transition::None => $this->touchOngoingIncident($monitor, $result),
        };

        return new Evaluation($check, $transition, $degradation, $incident);
    }

    /**
     * Latency is monitor policy, not a checker concern: every checker reports
     * a duration, and the streak arithmetic needs the same row lock the status
     * does. So it is decided here rather than inside the checker, and
     * CheckResult keeps its four fields.
     *
     * Only evaluated while the monitor is confirmed up — a down monitor's
     * slowness is noise, and its degradation state is cleared silently so the
     * Began/Ended edges stay balanced.
     */
    private function applyDegradation(Monitor $monitor, CheckResult $result, bool $confirmed): Degradation
    {
        $threshold = $monitor->degraded_response_ms;
        $wasDegraded = (bool) $monitor->is_degraded;

        if ($threshold === null || ! $confirmed || ! $result->isUp) {
            $monitor->is_degraded = false;
            $monitor->degraded_streak = 0;

            return Degradation::None;
        }

        if ($result->responseMs > $threshold) {
            $monitor->degraded_streak++;

            // Reuses confirmation_threshold rather than adding a second knob:
            // one slow sample is as unreliable as one failed one.
            $monitor->is_degraded = $monitor->degraded_streak >= max(1, $monitor->confirmation_threshold);

            return $monitor->is_degraded && ! $wasDegraded ? Degradation::Began : Degradation::None;
        }

        $monitor->degraded_streak = 0;
        $monitor->is_degraded = false;

        return $wasDegraded ? Degradation::Ended : Degradation::None;
    }

    /**
     * A monitor only flips to down once it has failed `confirmation_threshold`
     * checks in a row, which keeps a single blip from paging anyone, and only
     * flips back up after `recovery_threshold` successes. The recovery side is
     * what stops a flapping target from being announced down, up, and down
     * again within a couple of minutes.
     */
    private function confirmedStatus(Monitor $monitor, CheckResult $result, ?bool $previous): bool
    {
        if ($result->isUp) {
            if ($monitor->success_streak >= max(1, $monitor->recovery_threshold)) {
                return true;
            }

            // Holding "up" for a monitor that has never reported keeps a new
            // monitor's first success flipping it out of Pending immediately —
            // there is no outage to confirm a recovery from.
            return $previous ?? true;
        }

        if ($monitor->failure_streak >= max(1, $monitor->confirmation_threshold)) {
            return false;
        }

        // Not yet confirmed: hold the previous status, defaulting to up.
        return $previous ?? true;
    }

    private function transitionFor(?bool $previous, bool $confirmed): Transition
    {
        return match (true) {
            $confirmed === false && $previous !== false => Transition::WentDown,
            $confirmed === true && $previous === false => Transition::Recovered,
            default => Transition::None,
        };
    }

    private function openIncident(Monitor $monitor, CheckResult $result, CarbonInterface $checkedAt): Incident
    {
        return Incident::create([
            'monitor_id' => $monitor->id,
            'started_at' => $this->outageStartedAt($monitor, $checkedAt),
            'cause' => $result->error ? mb_substr($result->error, 0, 255) : null,
            'failed_checks' => $monitor->failure_streak,
        ]);
    }

    private function resolveIncident(Monitor $monitor, CarbonInterface $checkedAt): ?Incident
    {
        $incident = $monitor->ongoingIncident();

        $incident?->update(['resolved_at' => $checkedAt]);

        return $incident;
    }

    private function touchOngoingIncident(Monitor $monitor, CheckResult $result): ?Incident
    {
        if ($result->isUp || $monitor->latest_is_up !== false) {
            return null;
        }

        $incident = $monitor->ongoingIncident();

        $incident?->increment('failed_checks');

        return $incident;
    }

    /**
     * Backdate the incident to the first failure in the streak rather than the
     * check that happened to cross the confirmation threshold.
     */
    private function outageStartedAt(Monitor $monitor, CarbonInterface $checkedAt): CarbonInterface
    {
        $streak = max(1, $monitor->failure_streak);

        $firstFailure = $monitor->checks()
            ->where('is_up', false)
            ->orderByDesc('checked_at')
            ->limit($streak)
            ->get()
            ->last();

        return $firstFailure?->checked_at ?? $checkedAt;
    }
}
