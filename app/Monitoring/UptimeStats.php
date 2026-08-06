<?php

namespace App\Monitoring;

use App\Models\Incident;
use App\Models\Monitor;
use App\Models\MonitorCheck;
use App\Models\User;
use App\Support\SqlDialect;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class UptimeStats
{
    /**
     * Aggregate a single monitor's health over a window.
     *
     * @return array{
     *     uptime_percentage: float|null,
     *     total_checks: int,
     *     failed_checks: int,
     *     avg_response_ms: int|null,
     *     p95_response_ms: int|null,
     *     incidents: int,
     *     downtime_seconds: int
     * }
     */
    public function forMonitor(Monitor $monitor, CarbonInterface $since): array
    {
        $totals = MonitorCheck::query()
            ->where('monitor_id', $monitor->id)
            ->where('checked_at', '>=', $since)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_up = 1 THEN 1 ELSE 0 END) as up_count')
            ->selectRaw('AVG(CASE WHEN is_up = 1 THEN response_ms END) as avg_ms')
            ->first();

        $total = (int) ($totals->total ?? 0);
        $upCount = (int) ($totals->up_count ?? 0);

        return [
            'uptime_percentage' => $total > 0 ? round($upCount / $total * 100, 3) : null,
            'total_checks' => $total,
            'failed_checks' => $total - $upCount,
            'avg_response_ms' => $totals->avg_ms !== null ? (int) round((float) $totals->avg_ms) : null,
            'p95_response_ms' => $this->percentile($monitor, $since, 0.95),
            'incidents' => $this->incidentsIn($monitor, $since)->count(),
            'downtime_seconds' => $this->downtimeSeconds($monitor, $since),
        ];
    }

    /**
     * Headline numbers for the dashboard.
     *
     * @return array{
     *     total: int, up: int, down: int, paused: int, pending: int,
     *     ongoing_incidents: int, uptime_percentage: float|null, avg_response_ms: int|null
     * }
     */
    public function summaryForUser(User $user, CarbonInterface $since): array
    {
        $counts = Monitor::query()
            ->forUser($user)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as paused')
            ->selectRaw('SUM(CASE WHEN is_active = 1 AND latest_is_up = 1 THEN 1 ELSE 0 END) as up_count')
            ->selectRaw('SUM(CASE WHEN is_active = 1 AND latest_is_up = 0 THEN 1 ELSE 0 END) as down_count')
            ->selectRaw('SUM(CASE WHEN is_active = 1 AND latest_is_up IS NULL THEN 1 ELSE 0 END) as pending')
            ->first();

        $checks = MonitorCheck::query()
            ->whereIn('monitor_id', Monitor::query()->forUser($user)->select('id'))
            ->where('checked_at', '>=', $since)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_up = 1 THEN 1 ELSE 0 END) as up_count')
            ->selectRaw('AVG(CASE WHEN is_up = 1 THEN response_ms END) as avg_ms')
            ->first();

        $checkTotal = (int) ($checks->total ?? 0);
        $checkUp = (int) ($checks->up_count ?? 0);

        return [
            'total' => (int) ($counts->total ?? 0),
            'up' => (int) ($counts->up_count ?? 0),
            'down' => (int) ($counts->down_count ?? 0),
            'paused' => (int) ($counts->paused ?? 0),
            'pending' => (int) ($counts->pending ?? 0),
            'ongoing_incidents' => Incident::query()
                ->whereIn('monitor_id', Monitor::query()->forUser($user)->select('id'))
                ->ongoing()
                ->count(),
            'uptime_percentage' => $checkTotal > 0 ? round($checkUp / $checkTotal * 100, 3) : null,
            'avg_response_ms' => $checks->avg_ms !== null ? (int) round((float) $checks->avg_ms) : null,
        ];
    }

    /**
     * Response time series bucketed for charting, oldest first.
     *
     * @return array<int, array{bucket: string, avg_response_ms: int|null, failures: int, total: int}>
     */
    public function responseSeries(Monitor $monitor, CarbonInterface $since, int $buckets = 48): array
    {
        $windowSeconds = max(1, (int) $since->diffInSeconds(now(), true));
        $bucketSeconds = max(60, (int) ceil($windowSeconds / max(1, $buckets)));
        $startTimestamp = $since->getTimestamp();

        return MonitorCheck::query()
            ->where('monitor_id', $monitor->id)
            ->where('checked_at', '>=', $since)
            ->selectRaw(
                'FLOOR(('.SqlDialect::unixTimestamp('checked_at').' - ?) / ?) as bucket_index',
                [$startTimestamp, $bucketSeconds],
            )
            ->selectRaw('AVG(CASE WHEN is_up = 1 THEN response_ms END) as avg_ms')
            ->selectRaw('SUM(CASE WHEN is_up = 0 THEN 1 ELSE 0 END) as failures')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('bucket_index')
            ->orderBy('bucket_index')
            ->get()
            ->map(fn ($row) => [
                'bucket' => now()->setTimestamp(
                    $startTimestamp + ((int) $row->bucket_index * $bucketSeconds),
                )->toIso8601String(),
                // Null, not zero: a bucket where every check failed has no
                // successful sample to average, and plotting it as 0 ms would
                // draw a plunge to the baseline that reads as a real reading.
                'avg_response_ms' => $row->avg_ms !== null ? (int) round((float) $row->avg_ms) : null,
                'failures' => (int) $row->failures,
                'total' => (int) $row->total,
            ])
            ->all();
    }

    private function percentile(Monitor $monitor, CarbonInterface $since, float $percentile): ?int
    {
        $query = MonitorCheck::query()
            ->where('monitor_id', $monitor->id)
            ->where('checked_at', '>=', $since)
            ->where('is_up', true);

        $count = (clone $query)->count();

        if ($count === 0) {
            return null;
        }

        $offset = max(0, min($count - 1, (int) floor($count * $percentile) - 1));

        $value = $query->orderBy('response_ms')->offset($offset)->limit(1)->value('response_ms');

        return $value === null ? null : (int) $value;
    }

    private function incidentsIn(Monitor $monitor, CarbonInterface $since)
    {
        return Incident::query()
            ->where('monitor_id', $monitor->id)
            ->where(fn ($q) => $q->whereNull('resolved_at')->orWhere('resolved_at', '>=', $since));
    }

    /**
     * Sum the portion of each incident that falls inside the window.
     */
    private function downtimeSeconds(Monitor $monitor, CarbonInterface $since): int
    {
        $windowStart = $since->getTimestamp();
        $windowEnd = now()->getTimestamp();

        return (int) $this->incidentsIn($monitor, $since)
            ->get(['started_at', 'resolved_at'])
            ->sum(function (Incident $incident) use ($windowStart, $windowEnd): int {
                $start = max($incident->started_at->getTimestamp(), $windowStart);
                $end = min(($incident->resolved_at ?? now())->getTimestamp(), $windowEnd);

                return max(0, $end - $start);
            });
    }

    /**
     * Daily up/down rollup used by the status page uptime bars.
     *
     * @return array<int, array{date: string, uptime_percentage: float|null, total: int}>
     */
    public function dailyUptime(Monitor $monitor, int $days = 90): array
    {
        return MonitorCheck::query()
            ->where('monitor_id', $monitor->id)
            ->where('checked_at', '>=', now()->subDays($days)->startOfDay())
            ->selectRaw(SqlDialect::dateOf('checked_at').' as day')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_up = 1 THEN 1 ELSE 0 END) as up_count')
            ->groupBy(DB::raw(SqlDialect::dateOf('checked_at')))
            ->orderBy('day')
            ->get()
            ->map(fn ($row) => [
                'date' => (string) $row->day,
                'uptime_percentage' => (int) $row->total > 0
                    ? round((int) $row->up_count / (int) $row->total * 100, 2)
                    : null,
                'total' => (int) $row->total,
            ])
            ->all();
    }
}
