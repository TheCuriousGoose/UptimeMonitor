<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Monitors\SyncMonitorChannels;
use App\Http\Controllers\Controller;
use App\Http\Requests\Monitors\IndexRequest;
use App\Http\Requests\Monitors\ShowRequest;
use App\Http\Requests\Monitors\StoreRequest;
use App\Http\Requests\Monitors\UpdateRequest;
use App\Http\Resources\MonitorCheckResource;
use App\Http\Resources\MonitorResource;
use App\Models\Monitor;
use App\Policies\MonitorPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Reuses the same FormRequests, policy, and model scopes as the web
 * MonitorController — ownership and role-permission rules must not drift
 * between the two surfaces.
 */
#[UsePolicy(MonitorPolicy::class)]
class MonitorController extends Controller
{
    private const MAX_PER_PAGE = 100;

    public function index(IndexRequest $request)
    {
        $monitors = Monitor::query()
            ->forUser(Auth::user())
            ->search($request->search())
            ->whereStatus($request->status())
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return MonitorResource::collection($monitors);
    }

    public function store(StoreRequest $request, SyncMonitorChannels $syncChannels)
    {
        $monitor = $request->user()->monitors()->create($request->monitorAttributes());

        $syncChannels($monitor, $request->channelUuids());

        return (new MonitorResource($monitor->load('notificationChannels')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(ShowRequest $request, Monitor $monitor)
    {
        return new MonitorResource($monitor->load('notificationChannels'));
    }

    public function update(UpdateRequest $request, Monitor $monitor, SyncMonitorChannels $syncChannels)
    {
        $monitor->update($request->monitorAttributes());

        if ($request->has('notification_channels')) {
            $syncChannels($monitor, $request->channelUuids());
        }

        return new MonitorResource($monitor->fresh()->load('notificationChannels'));
    }

    public function destroy(Monitor $monitor)
    {
        $this->authorize('delete', $monitor);

        $monitor->delete();

        return response()->noContent();
    }

    /**
     * Check history for one monitor, reusing ShowRequest for both the
     * ownership check and the same period vocabulary the web timeline uses.
     */
    public function checks(ShowRequest $request, Monitor $monitor)
    {
        $checks = $monitor->checks()
            ->where('checked_at', '>=', $request->from())
            ->orderByDesc('checked_at')
            ->paginate($this->perPage($request));

        return MonitorCheckResource::collection($checks);
    }

    private function perPage(IndexRequest|ShowRequest $request): int
    {
        return min((int) $request->integer('per_page', 30), self::MAX_PER_PAGE);
    }
}
