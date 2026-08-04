<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Monitors\IndexRequest;
use App\Http\Requests\Monitors\ShowRequest;
use App\Http\Requests\Monitors\StoreRequest;
use App\Http\Requests\Monitors\UpdateRequest;
use App\Http\Resources\MonitorCheckResource;
use App\Http\Resources\MonitorResource;
use App\Models\Monitor;
use App\Models\NotificationChannel;
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
            ->status($request->status())
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return MonitorResource::collection($monitors);
    }

    public function store(StoreRequest $request)
    {
        $monitor = $request->user()->monitors()->create($request->monitorAttributes());

        $this->syncChannels($monitor, $request->channelUuids());

        return (new MonitorResource($monitor->load('notificationChannels')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(ShowRequest $request, Monitor $monitor)
    {
        return new MonitorResource($monitor->load('notificationChannels'));
    }

    public function update(UpdateRequest $request, Monitor $monitor)
    {
        $monitor->update($request->monitorAttributes());

        if ($request->has('notification_channels')) {
            $this->syncChannels($monitor, $request->channelUuids());
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

    /**
     * Channels always belong to the acting user, never the monitor owner, so
     * an admin editing someone else's monitor cannot attach their own
     * endpoints. Mirrors the web MonitorController exactly.
     *
     * @param  array<int, string>  $uuids
     */
    private function syncChannels(Monitor $monitor, array $uuids): void
    {
        $ids = NotificationChannel::query()
            ->where('user_id', $monitor->created_by)
            ->whereIn('uuid', $uuids)
            ->pluck('id');

        $monitor->notificationChannels()->sync($ids);
    }

    private function perPage(IndexRequest|ShowRequest $request): int
    {
        return min((int) $request->integer('per_page', 30), self::MAX_PER_PAGE);
    }
}
