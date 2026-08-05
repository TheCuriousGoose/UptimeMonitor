<?php

namespace App\Http\Controllers;

use App\Enums\AlertScope;
use App\Enums\ChannelType;
use App\Http\Requests\Channels\StoreChannelRequest;
use App\Http\Requests\Channels\UpdateChannelRequest;
use App\Http\Resources\MonitorResource;
use App\Http\Resources\NotificationChannelResource;
use App\Models\Monitor;
use App\Models\NotificationChannel;
use App\Monitoring\AlertTemplate;
use App\Policies\NotificationChannelPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Every delivery target lives here — email and webhooks alongside PagerDuty and
 * Opsgenie. These were split across two pages once, on the theory that the
 * enterprise tools were a different kind of thing. They are not: Slack, Discord
 * and Teams are the same webhook POST, and all seven types share this model,
 * policy, validation and delivery pipeline.
 */
#[UsePolicy(NotificationChannelPolicy::class)]
class IntegrationController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', NotificationChannel::class);

        $integrations = NotificationChannel::query()
            ->where('user_id', Auth::id())
            ->with('monitors:id,uuid')
            ->withCount('monitors')
            ->orderBy('name')
            ->get();

        return Inertia::render('integrations/Index', [
            'integrations' => NotificationChannelResource::collection($integrations)->resolve(),
            'providers' => ChannelType::values(),
            'scopes' => AlertScope::values(),
            'placeholders' => AlertTemplate::PLACEHOLDERS,
            'monitors' => MonitorResource::collection($this->userMonitors())->resolve(),
        ]);
    }

    public function store(StoreChannelRequest $request)
    {
        $integration = $request->user()->notificationChannels()->create($request->channelAttributes());

        $this->syncMonitors($integration, $request->alertScope(), $request->monitorUuids());

        return to_route('integrations.index')
            ->with('success', __('integrations.messages.connected.success'));
    }

    public function update(UpdateChannelRequest $request, NotificationChannel $integration)
    {
        $this->authorize('update', $integration);

        $integration->update($request->channelAttributes());

        if ($request->has('alert_scope')) {
            $this->syncMonitors($integration, $request->alertScope(), $request->monitorUuids());
        }

        return to_route('integrations.index')
            ->with('success', __('integrations.messages.updated.success'));
    }

    public function destroy(NotificationChannel $integration)
    {
        $this->authorize('delete', $integration);

        $integration->delete();

        return to_route('integrations.index')
            ->with('success', __('integrations.messages.disconnected.success'));
    }

    /**
     * Monitors are resolved against the integration's owner, never the acting
     * user, so an admin editing someone else's integration cannot point it at
     * their own monitors.
     *
     * @param  array<int, string>  $uuids
     */
    private function syncMonitors(NotificationChannel $integration, AlertScope $scope, array $uuids): void
    {
        // An `all` scope reads nothing from the pivot, so rows left behind
        // would resurrect a stale selection if the scope were switched back.
        if ($scope === AlertScope::All) {
            $integration->monitors()->detach();

            return;
        }

        $ids = Monitor::query()
            ->where('created_by', $integration->user_id)
            ->whereIn('uuid', $uuids)
            ->pluck('id');

        $integration->monitors()->sync($ids);
    }

    /**
     * @return Collection<int, Monitor>
     */
    private function userMonitors()
    {
        return Monitor::query()
            ->where('created_by', Auth::id())
            ->orderBy('name')
            ->get();
    }
}
