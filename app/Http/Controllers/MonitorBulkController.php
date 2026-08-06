<?php

namespace App\Http\Controllers;

use App\Http\Requests\Monitors\BulkRequest;
use App\Models\Monitor;
use App\Policies\MonitorPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

#[UsePolicy(MonitorPolicy::class)]
class MonitorBulkController extends Controller
{
    /**
     * Apply one action to a set of monitors.
     *
     * Every monitor is authorised individually rather than once for the batch:
     * "delete" and "update" are separate permissions, and a user who may pause
     * a monitor is not necessarily allowed to delete it. Anything they may not
     * touch is skipped rather than failing the whole request, so a stale
     * selection does not lose the work on the rest of it.
     */
    public function store(BulkRequest $request): RedirectResponse
    {
        $action = $request->action();
        $ability = $action === 'delete' ? 'delete' : 'update';

        $allowed = $request->monitors()->filter(
            fn (Monitor $monitor) => $request->user()->can($ability, $monitor),
        );

        if ($allowed->isEmpty()) {
            return back()->with('error', __('monitors.messages.bulk.none'));
        }

        DB::transaction(fn () => match ($action) {
            'pause' => $this->setActive($allowed, false),
            'resume' => $this->setActive($allowed, true),
            'delete' => Monitor::query()->whereKey($allowed->modelKeys())->delete(),
        });

        return back()->with('success', __(
            "monitors.messages.bulk.{$action}",
            ['count' => $allowed->count()],
        ));
    }

    /**
     * @param  Collection<int, Monitor>  $monitors
     */
    private function setActive($monitors, bool $active): void
    {
        // Mirrors MonitorStateController: resuming schedules the next check
        // immediately so the list stops showing stale data, and both
        // directions reset the streaks so a pause does not carry a half
        // confirmed outage across it.
        Monitor::query()->whereKey($monitors->modelKeys())->update([
            'is_active' => $active,
            'failure_streak' => 0,
            'success_streak' => 0,
            ...$active ? ['next_check_at' => now()] : [],
        ]);
    }
}
