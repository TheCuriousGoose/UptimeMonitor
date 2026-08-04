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

    public function record(Monitor $monitor, CheckResult $result, ?CarbonInterface $checkedAt = null): MonitorCheck
    {
        $checkedAt ??= now();

        [$check, $transition, $incident] = DB::transaction(
            fn () => $this->persist($monitor, $result, $checkedAt),
        );

        if ($transition === Transition::WentDown) {
            $this->alerts->dispatch($monitor, AlertMessage::down($monitor, $result->error, $incident));
        }

        if ($transition === Transition::Recovered) {
            $this->alerts->dispatch($monitor, AlertMessage::recovered($monitor, $incident));
        }

        return $check;
    }

    /**
     * @return array{0: MonitorCheck, 1: Transition, 2: ?Incident}
     */
    private function persist(Monitor $monitor, CheckResult $result, CarbonInterface $checkedAt): array
    {
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

        return [$check, $transition, $incident];
    }

    /**
     * A monitor only flips to down once it has failed `confirmation_threshold`
     * checks in a row, which keeps a single blip from paging anyone.
     */
    private function confirmedStatus(Monitor $monitor, CheckResult $result, ?bool $previous): bool
    {
        if ($result->isUp) {
            return true;
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
