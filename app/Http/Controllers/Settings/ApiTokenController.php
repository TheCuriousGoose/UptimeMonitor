<?php

namespace App\Http\Controllers\Settings;

use App\Enums\ApiAbility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreApiTokenRequest;
use App\Http\Resources\ApiTokenResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApiTokenController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('settings/ApiKeys', [
            'tokens' => ApiTokenResource::collection(
                $request->user()->tokens()->orderByDesc('created_at')->get(),
            )->resolve(),
            'abilities' => ApiAbility::values(),
        ]);
    }

    public function store(StoreApiTokenRequest $request): RedirectResponse
    {
        $days = $request->integer('expires_in_days');

        $token = $request->user()->createToken(
            $request->string('name')->toString(),
            $request->array('abilities'),
            $days ? now()->addDays($days) : null,
        );

        // The plaintext value exists only in this one flash — Sanctum stores
        // just its hash, so this is the only moment it can ever be shown.
        Inertia::flash('apiToken', [
            'name' => $token->accessToken->name,
            'token' => $token->plainTextToken,
        ]);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('api_tokens.messages.created.success')]);

        return to_route('api-tokens.index');
    }

    public function destroy(Request $request, int $token): RedirectResponse
    {
        // Scoped to the acting user's own tokens: findOrFail 404s rather than
        // let one account revoke another's key by guessing a sequential id.
        $request->user()->tokens()->findOrFail($token)->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('api_tokens.messages.revoked.success')]);

        return back();
    }
}
