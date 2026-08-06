<?php

namespace App\Http\Controllers;

use App\Http\Requests\Incidents\AcknowledgeRequest;
use App\Models\Incident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class IncidentAcknowledgementController extends Controller
{
    public function store(AcknowledgeRequest $request, Incident $incident): RedirectResponse
    {
        DB::transaction(function () use ($request, $incident): void {
            $incident->update([
                'acknowledged_at' => now(),
                'acknowledged_by' => $request->user()->id,
            ]);

            if ($note = trim((string) $request->input('note'))) {
                $incident->updates()->create([
                    'user_id' => $request->user()->id,
                    'body' => $note,
                    'is_public' => false,
                ]);
            }
        });

        return back()->with('success', __('incidents.messages.acknowledged'));
    }

    public function destroy(Incident $incident): RedirectResponse
    {
        $this->authorize('acknowledge', $incident);

        $incident->update(['acknowledged_at' => null, 'acknowledged_by' => null]);

        return back()->with('success', __('incidents.messages.unacknowledged'));
    }
}
