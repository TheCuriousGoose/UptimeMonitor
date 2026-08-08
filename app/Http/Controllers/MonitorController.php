<?php

namespace App\Http\Controllers;

use App\Actions\Monitors\SyncMonitorChannels;
use App\Enums\MonitorType;
use App\Http\Requests\Monitors\IndexRequest;
use App\Http\Requests\Monitors\ShowRequest;
use App\Http\Requests\Monitors\StoreRequest;
use App\Http\Requests\Monitors\UpdateRequest;
use App\Http\Resources\IncidentResource;
use App\Http\Resources\MonitorCheckResource;
use App\Http\Resources\MonitorResource;
use App\Http\Resources\NotificationChannelResource;
use App\Models\Monitor;
use App\Models\NotificationChannel;
use App\Monitoring\UptimeStats;
use App\Onboarding\OnboardingProgress;
use App\Policies\MonitorPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

#[UsePolicy(MonitorPolicy::class)]
class MonitorController extends Controller
{
    public function index(IndexRequest $request)
    {
        $monitors = Monitor::query()
            ->forUser(Auth::user())
            ->search($request->search())
            ->whereStatus($request->status())
            ->with(['createdBy'])
            ->sort($request->sort(), $request->direction())
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('monitors/Index', [
            'monitors' => $monitors->toResourceCollection(),
            'filters' => [
                'search' => $request->search(),
                'status' => $request->status(),
                'sort' => $request->sort(),
                'direction' => $request->direction(),
            ],
        ]);
    }

    public function show(ShowRequest $request, Monitor $monitor, UptimeStats $stats)
    {
        $since = $request->from();

        $monitor->load('notificationChannels');
        $monitor->setRelation(
            'checks',
            $monitor->checks()->where('checked_at', '>=', $since)->orderBy('checked_at')->get(),
        );

        return Inertia::render('monitors/Show', [
            'monitor' => (new MonitorResource($monitor))->resolve(),
            // A monitor nothing is listening to fails silently forever, and
            // an all-scope channel covers it without appearing on the pivot,
            // so the page cannot work this out from the relation alone.
            'alertsCovered' => NotificationChannel::query()
                ->active()
                ->forMonitor($monitor)
                ->exists(),
            // Nothing to attach versus nothing attached are different problems
            // with different fixes, and sending the first case to the edit
            // form just lands them on an empty checkbox list.
            'hasChannels' => NotificationChannel::query()
                ->where('user_id', $monitor->created_by)
                ->exists(),
            'checks' => MonitorCheckResource::collection($monitor->checks)->resolve(),
            'stats' => $stats->forMonitor($monitor, $since),
            'series' => $stats->responseSeries($monitor, $since),
            'incidents' => IncidentResource::collection(
                $monitor->incidents()->orderByDesc('started_at')->limit(20)->get(),
            )->resolve(),
            'period' => $request->period(),
            'periods' => ShowRequest::PERIODS,
        ]);
    }

    public function create()
    {
        $this->authorize('create', Monitor::class);

        return Inertia::render('monitors/Create', [
            'types' => MonitorType::values(),
            'typeOptions' => MonitorType::formOptions(),
            'channels' => NotificationChannelResource::collection($this->userChannels())->resolve(),
            'onboarding' => OnboardingProgress::for(Auth::user()),
        ]);
    }

    public function store(StoreRequest $request, SyncMonitorChannels $syncChannels)
    {
        $monitor = $request->user()->monitors()->create($request->monitorAttributes());

        $syncChannels($monitor, $request->channelUuids());

        return to_route('monitors.show', $monitor)
            ->with('success', __('monitors.messages.created.success'));
    }

    public function edit(Monitor $monitor)
    {
        $this->authorize('update', $monitor);

        $monitor->load('notificationChannels');

        return Inertia::render('monitors/Edit', [
            'monitor' => (new MonitorResource($monitor))->resolve(),
            'types' => MonitorType::values(),
            'typeOptions' => MonitorType::formOptions(),
            'channels' => NotificationChannelResource::collection($this->userChannels())->resolve(),
        ]);
    }

    public function update(UpdateRequest $request, Monitor $monitor, SyncMonitorChannels $syncChannels)
    {
        $monitor->update($request->monitorAttributes());

        if ($request->has('notification_channels')) {
            $syncChannels($monitor, $request->channelUuids());
        }

        return to_route('monitors.show', $monitor)
            ->with('success', __('monitors.messages.updated.success'));
    }

    public function destroy(Monitor $monitor)
    {
        $this->authorize('delete', $monitor);

        $monitor->delete();

        return to_route('monitors.index')
            ->with('success', __('monitors.messages.deleted.success'));
    }

    private function userChannels()
    {
        return NotificationChannel::query()
            ->where('user_id', Auth::id())
            ->orderBy('name')
            ->get();
    }
}
