<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\IncidentResource;
use App\Models\Incident;
use App\Policies\IncidentPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

#[UsePolicy(IncidentPolicy::class)]
class IncidentController extends Controller
{
    private const MAX_PER_PAGE = 100;

    public function index(Request $request)
    {
        $incidents = Incident::query()
            ->whereHas('monitor', fn ($query) => $query->forUser(Auth::user()))
            ->with('monitor')
            ->when($request->boolean('ongoing'), fn ($query) => $query->ongoing())
            ->orderByDesc('started_at')
            ->paginate(min((int) $request->integer('per_page', 30), self::MAX_PER_PAGE));

        return IncidentResource::collection($incidents);
    }

    public function show(Incident $incident)
    {
        $this->authorize('view', $incident);

        return new IncidentResource($incident->load('monitor'));
    }
}
