<?php

namespace App\Http\Controllers;

use App\Enums\AlertScope;
use App\Enums\ChannelType;
use App\Enums\MonitorType;
use App\Http\Requests\Onboarding\CompleteSetupRequest;
use App\Http\Resources\NotificationChannelResource;
use App\Jobs\RunMonitorCheck;
use App\Models\Monitor;
use App\Models\NotificationChannel;
use App\Onboarding\OnboardingProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * The guided first run: one screen that walks somebody from an empty account
 * to a monitor that is being checked and can reach them.
 *
 * It is a different route through the same decisions the ordinary create form
 * asks about, not a parallel implementation — validation comes from
 * MonitorRequest, and the live test step calls the same preview endpoint the
 * form's "Test check" button does.
 */
class OnboardingController extends Controller
{
    public function show()
    {
        $this->authorize('create', Monitor::class);

        $user = Auth::user();

        return Inertia::render('onboarding/Setup', [
            'types' => MonitorType::values(),
            'typeOptions' => MonitorType::formOptions(),
            'channels' => NotificationChannelResource::collection(
                NotificationChannel::query()
                    ->where('user_id', $user->id)
                    ->orderBy('name')
                    ->get(),
            )->resolve(),
            'suggestedEmail' => $user->email,
            'progress' => OnboardingProgress::for($user),
        ]);
    }

    /**
     * Everything the wizard collected, committed together.
     *
     * One transaction because a half-finished setup is the outcome the whole
     * flow exists to avoid: a monitor with nothing listening, or a channel
     * attached to a monitor that failed to save.
     */
    public function store(CompleteSetupRequest $request): RedirectResponse
    {
        $monitor = DB::transaction(function () use ($request) {
            $user = $request->user();
            $monitor = $user->monitors()->create($request->monitorAttributes());

            if ($email = $request->alertEmail()) {
                $this->channelFor($user, $email);
            }

            $uuids = $request->channelUuids();

            if ($uuids !== []) {
                $monitor->notificationChannels()->sync(
                    NotificationChannel::query()
                        ->where('user_id', $user->id)
                        ->whereIn('uuid', $uuids)
                        ->pluck('id'),
                );
            }

            return $monitor;
        });

        // So the monitor has a result by the time they land on it, rather than
        // an empty page until the next scheduled sweep.
        RunMonitorCheck::dispatch($monitor);

        return to_route('monitors.show', $monitor)
            ->with('success', __('onboarding.messages.created'));
    }

    /**
     * "I'll set it up myself" — remembered, so the dashboard stops offering.
     */
    public function skip(): RedirectResponse
    {
        $user = Auth::user();

        $user->preferences = array_merge($user->preferences ?? [], [
            OnboardingProgress::PREFERENCE_KEY => true,
        ]);
        $user->save();

        return to_route('dashboard');
    }

    /**
     * Reuses a channel already pointed at this address rather than stacking up
     * duplicates for somebody who runs the wizard twice.
     */
    private function channelFor($user, string $email): void
    {
        $existing = NotificationChannel::query()
            ->where('user_id', $user->id)
            ->where('type', ChannelType::Email)
            ->get()
            ->first(fn (NotificationChannel $channel) => ($channel->config['email'] ?? null) === $email);

        if ($existing !== null) {
            return;
        }

        $user->notificationChannels()->create([
            'name' => __('onboarding.setup.alerts.channel_name'),
            'type' => ChannelType::Email,
            'config' => ['email' => $email],
            'is_active' => true,
            // Covers every monitor, including the ones they add next — the
            // alternative is a second silent monitor the first time they
            // forget to tick this channel.
            'alert_scope' => AlertScope::All,
        ]);
    }
}
