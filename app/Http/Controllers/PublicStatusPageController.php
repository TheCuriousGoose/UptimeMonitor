<?php

namespace App\Http\Controllers;

use App\Enums\MonitorStatus;
use App\Models\StatusPage;
use App\Monitoring\UptimeStats;
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
