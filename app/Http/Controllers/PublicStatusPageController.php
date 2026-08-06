<?php

namespace App\Http\Controllers;

use App\Content\MarkdownRenderer;
use App\Enums\MonitorStatus;
use App\Models\Incident;
use App\Models\StatusPage;
use App\Monitoring\UptimeStats;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The public, unauthenticated status page. Deliberately exposes only
 * aggregate health — never URLs, config, or error details.
 */
class PublicStatusPageController extends Controller
{
    public function show(string $slug, UptimeStats $stats)
    {
        $page = StatusPage::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->with('monitors')
            ->first();

        if (! $page) {
            throw new NotFoundHttpException;
        }

        $since = now()->subDays(90);

        $monitors = $page->monitors->map(fn ($monitor) => [
            'name' => $monitor->name,
            'status' => $monitor->status()->value,
            'uptime_percentage' => $stats->forMonitor($monitor, $since)['uptime_percentage'],
            'daily' => $stats->dailyUptime($monitor, 90),
        ])->values();

        $theme = $page->resolvedTheme();

        return Inertia::render('status/Show', [
            'page' => [
                'title' => $page->title,
                'description' => $page->description,
            ],
            'monitors' => $monitors,
            'incidents' => $page->show_incidents ? $this->publicIncidents($page) : null,
            'overall' => $this->overallStatus($monitors->pluck('status')->all()),
            'updatedAt' => now()->toIso8601String(),
            'theme' => $theme->toArray(),
            // Built here rather than in the browser so the page renders in the
            // owner's colours on the server-rendered pass, with no flash of the
            // default look while the client hydrates.
            'themeCss' => $theme->css(),
        ]);
    }

    /**
     * Only human-written text crosses this boundary.
     *
     * Never `cause` — that is the raw checker string ("Expected HTTP 200, got
     * 500"), the exact kind of detail this controller exists to withhold —
     * and never failed_checks, the URL, the acknowledger, or any author name.
     * An incident with no public update does not appear at all, so nothing is
     * published by accident.
     *
     * @return array<int, array<string, mixed>>
     */
    private function publicIncidents(StatusPage $page): array
    {
        $renderer = app(MarkdownRenderer::class);

        return Incident::query()
            ->whereIn('monitor_id', $page->monitors->modelKeys())
            ->where('is_maintenance', false)
            ->whereHas('updates', fn (Builder $query) => $query->public())
            ->with(['monitor:id,name', 'updates' => fn ($query) => $query->public()])
            ->orderByDesc('started_at')
            ->limit(20)
            ->get()
            ->map(fn (Incident $incident) => [
                'monitor' => $incident->monitor?->name,
                'started_at' => $incident->started_at->toIso8601String(),
                'resolved_at' => $incident->resolved_at?->toIso8601String(),
                'duration_seconds' => $incident->durationSeconds(),
                'is_resolved' => ! $incident->isOngoing(),
                'updates' => $incident->updates->map(fn ($update) => [
                    'status' => $update->status?->value,
                    'body_html' => $renderer->toHtml($update->body),
                    'published_at' => $update->created_at?->toIso8601String(),
                ])->all(),
            ])
            ->all();
    }

    /**
     * @param  array<int, string>  $statuses
     */
    private function overallStatus(array $statuses): string
    {
        if ($statuses === []) {
            return MonitorStatus::Pending->value;
        }

        if (in_array(MonitorStatus::Down->value, $statuses, true)) {
            return MonitorStatus::Down->value;
        }

        // Degraded outranks up: a page that says "all systems operational"
        // while something is crawling is worse than saying nothing.
        if (in_array(MonitorStatus::Degraded->value, $statuses, true)) {
            return MonitorStatus::Degraded->value;
        }

        return in_array(MonitorStatus::Up->value, $statuses, true)
            ? MonitorStatus::Up->value
            : MonitorStatus::Pending->value;
    }
}
