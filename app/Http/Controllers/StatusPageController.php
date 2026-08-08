<?php

namespace App\Http\Controllers;

use App\Http\Requests\StatusPages\StoreStatusPageRequest;
use App\Http\Requests\StatusPages\UpdateStatusPageRequest;
use App\Http\Resources\MonitorResource;
use App\Http\Resources\StatusPageResource;
use App\Models\StatusPage;
use App\Policies\StatusPagePolicy;
use App\Queries\OwnedMonitors;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

#[UsePolicy(StatusPagePolicy::class)]
class StatusPageController extends Controller
{
    public function __construct(private readonly OwnedMonitors $monitors) {}

    public function index()
    {
        $this->authorize('viewAny', StatusPage::class);

        $pages = StatusPage::query()
            ->where('user_id', Auth::id())
            ->withCount('monitors')
            ->with('monitors')
            ->orderBy('title')
            ->get();

        return Inertia::render('status-pages/Index', [
            'pages' => StatusPageResource::collection($pages)->resolve(),
            'monitors' => MonitorResource::collection($this->monitors->listFor(Auth::user()))->resolve(),
        ]);
    }

    public function store(StoreStatusPageRequest $request)
    {
        $page = $request->user()->statusPages()->create($request->pageAttributes());

        $this->syncMonitors($page, $request->monitorUuids());

        return to_route('status-pages.index')
            ->with('success', __('status_pages.messages.created.success'));
    }

    public function update(UpdateStatusPageRequest $request, StatusPage $statusPage)
    {
        $statusPage->update($request->pageAttributes());

        if ($request->has('monitors')) {
            $this->syncMonitors($statusPage, $request->monitorUuids());
        }

        return to_route('status-pages.index')
            ->with('success', __('status_pages.messages.updated.success'));
    }

    public function destroy(StatusPage $statusPage)
    {
        $this->authorize('delete', $statusPage);

        $statusPage->delete();

        return to_route('status-pages.index')
            ->with('success', __('status_pages.messages.deleted.success'));
    }

    /**
     * @param  array<int, string>  $uuids
     */
    private function syncMonitors(StatusPage $page, array $uuids): void
    {
        $idsByUuid = $this->monitors->idsByUuid($page->user_id, $uuids);

        // Walk the submitted uuids so the page keeps the order the user chose.
        $sync = [];
        $position = 0;

        foreach ($uuids as $uuid) {
            if (isset($idsByUuid[$uuid])) {
                $sync[$idsByUuid[$uuid]] = ['sort_order' => $position++];
            }
        }

        $page->monitors()->sync($sync);
    }
}
