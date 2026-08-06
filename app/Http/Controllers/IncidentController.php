<?php

namespace App\Http\Controllers;

use App\Http\Requests\Incidents\IndexRequest;
use App\Http\Resources\IncidentResource;
use App\Models\Incident;
use App\Models\Monitor;
use App\Policies\IncidentPolicy;
use App\Support\SqlDialect;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

#[UsePolicy(IncidentPolicy::class)]
class IncidentController extends Controller
{
    public function index(IndexRequest $request)
    {
        $incidents = Incident::query()
            ->whereHas('monitor', fn (Builder $query) => $query->forUser(Auth::user()))
            ->with('monitor')
            ->when(
                $request->search(),
                fn (Builder $query, string $search) => $query->whereHas(
                    'monitor',
                    fn (Builder $monitor) => $monitor->where('name', SqlDialect::like(), "%{$search}%"),
                ),
            )
            ->when(
                $request->status() === 'ongoing',
                fn (Builder $query) => $query->whereNull('resolved_at'),
            )
            ->when(
                $request->status() === 'resolved',
                fn (Builder $query) => $query->whereNotNull('resolved_at'),
            )
            ->sort($request->sort(), $request->direction())
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('incidents/Index', [
            'incidents' => $incidents->toResourceCollection(),
            'filters' => [
                'search' => $request->search(),
                'status' => $request->status(),
                'sort' => $request->sort(),
                'direction' => $request->direction(),
            ],
            'summary' => $this->summary(),
        ]);
    }

    public function show(Incident $incident)
    {
        $this->authorize('view', $incident);

        $incident->load(['monitor', 'acknowledgedBy', 'updates.author']);

        return Inertia::render('incidents/Show', [
            'incident' => (new IncidentResource($incident))->resolve(),
        ]);
    }

    /**
     * Headline counts for the ledger strip, over the same window the
     * dashboard uses so the two never disagree.
     *
     * @return array<string, int>
     */
    private function summary(): array
    {
        $monitorIds = Monitor::query()->forUser(Auth::user())->select('id');

        $base = fn () => Incident::query()->whereIn('monitor_id', $monitorIds);

        return [
            'ongoing' => $base()->whereNull('resolved_at')->count(),
            'last_24h' => $base()->where('started_at', '>=', now()->subDay())->count(),
            'last_7d' => $base()->where('started_at', '>=', now()->subDays(7))->count(),
            'total' => $base()->count(),
        ];
    }
}
