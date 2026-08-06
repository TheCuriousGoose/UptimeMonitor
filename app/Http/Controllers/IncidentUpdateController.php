<?php

namespace App\Http\Controllers;

use App\Http\Requests\Incidents\IncidentUpdateRequest;
use App\Models\Incident;
use App\Models\IncidentUpdate;
use Illuminate\Http\RedirectResponse;

class IncidentUpdateController extends Controller
{
    public function store(IncidentUpdateRequest $request, Incident $incident): RedirectResponse
    {
        $incident->updates()->create(
            $request->updateAttributes() + ['user_id' => $request->user()->id],
        );

        return back()->with('success', __('incidents.messages.update_added'));
    }

    public function update(IncidentUpdateRequest $request, IncidentUpdate $incidentUpdate): RedirectResponse
    {
        $incidentUpdate->update($request->updateAttributes());

        return back()->with('success', __('incidents.messages.update_saved'));
    }

    public function destroy(IncidentUpdate $incidentUpdate): RedirectResponse
    {
        $this->authorize('delete', $incidentUpdate);

        $incidentUpdate->delete();

        return back()->with('success', __('incidents.messages.update_deleted'));
    }
}
