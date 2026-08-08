<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\VerifiedDomain;
use App\Monitoring\DomainVerifier;
use App\Monitoring\TargetIdentity;
use App\Rules\Hostname;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class VerifiedDomainController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('settings/Domains', [
            'domains' => $request->user()->verifiedDomains()
                ->orderBy('domain')
                ->get()
                ->map(fn (VerifiedDomain $domain) => [
                    'uuid' => $domain->uuid,
                    'domain' => $domain->domain,
                    'token' => $domain->token,
                    'verified_at' => $domain->verified_at?->toIso8601String(),
                    'last_error' => $domain->last_error,
                    'last_attempted_at' => $domain->last_attempted_at?->toIso8601String(),
                ]),
            'instructions' => [
                'dns_host' => DomainVerifier::DNS_PREFIX,
                'well_known_path' => DomainVerifier::WELL_KNOWN_PATH,
            ],
            'required' => (bool) config('monitoring.abuse.require_domain_verification'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'domain' => ['required', 'string', 'max:255', new Hostname],
        ]);

        $identity = TargetIdentity::fromTarget($data['domain']);

        if ($identity === null || $identity->isAddress()) {
            throw ValidationException::withMessages([
                'domain' => __('validation.verifiable_domain'),
            ]);
        }

        $request->user()->verifiedDomains()->firstOrCreate(['domain' => $identity->domain]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('settings.domains.messages.added', ['domain' => $identity->domain]),
        ]);

        return back();
    }

    public function verify(Request $request, VerifiedDomain $domain, DomainVerifier $verifier): RedirectResponse
    {
        abort_unless($domain->user_id === $request->user()->id, 404);

        $verified = $verifier->verify($domain);

        Inertia::flash('toast', [
            'type' => $verified ? 'success' : 'error',
            'message' => $verified
                ? __('settings.domains.messages.verified', ['domain' => $domain->domain])
                : __('settings.domains.messages.not_found', ['domain' => $domain->domain]),
        ]);

        return back();
    }

    public function destroy(Request $request, VerifiedDomain $domain): RedirectResponse
    {
        abort_unless($domain->user_id === $request->user()->id, 404);

        $domain->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('settings.domains.messages.removed', ['domain' => $domain->domain]),
        ]);

        return back();
    }
}
