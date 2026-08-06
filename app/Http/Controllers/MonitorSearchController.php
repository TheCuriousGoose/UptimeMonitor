<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonitorSearchController extends Controller
{
    /**
     * Typeahead source for the command palette.
     *
     * A dedicated endpoint rather than shipping the monitor list into every
     * page's props: the palette lives in the app shell, so that list would be
     * paid for on every navigation whether or not it was ever opened.
     *
     * Only the three fields the palette renders are returned — this is a
     * navigation aid, not a second read API.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim((string) ($validated['q'] ?? ''));

        $monitors = Monitor::query()
            ->forUser($request->user())
            // Reuses the same wildcard-escaping search scope as the index, so
            // a literal % cannot widen the query.
            ->search($search === '' ? null : str_replace(['%', '_'], ['\%', '\_'], $search))
            ->orderBy('name')
            ->limit(10)
            ->get(['uuid', 'name', 'is_active', 'latest_is_up']);

        return response()->json(
            $monitors->map(fn (Monitor $monitor) => [
                'uuid' => $monitor->uuid,
                'name' => $monitor->name,
                'status' => $monitor->status()->value,
            ])->all(),
        );
    }
}
