<?php

namespace App\Monitoring;

use App\Checkers\Support\DnsResolver;
use App\Checkers\Support\OutboundGuard;
use App\Models\VerifiedDomain;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Proves a user controls a domain before the instance will aim traffic at it
 * on their behalf.
 *
 * Two routes, because the one a given user can actually use depends on what
 * they administer: a TXT record, or a file under /.well-known.
 */
class DomainVerifier
{
    public const WELL_KNOWN_PATH = '/.well-known/vigil-verification.txt';

    public const DNS_PREFIX = '_vigil-verify';

    private const TIMEOUT = 10;

    public function __construct(
        private readonly DnsResolver $resolver,
        private readonly OutboundGuard $guard,
    ) {}

    public function verify(VerifiedDomain $domain): bool
    {
        $found = $this->checkDns($domain->domain, $domain->token)
            || $this->checkWellKnown($domain->domain, $domain->token);

        $domain->forceFill([
            'verified_at' => $found ? now() : null,
            'last_attempted_at' => now(),
            'last_error' => $found ? null : __('settings.domains.errors.not_found'),
        ])->save();

        return $found;
    }

    private function checkDns(string $domain, string $token): bool
    {
        foreach ([self::DNS_PREFIX.'.'.$domain, $domain] as $host) {
            foreach ($this->resolver->resolve($host, 'TXT') as $record) {
                if (str_contains(trim($record), $token)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function checkWellKnown(string $domain, string $token): bool
    {
        foreach (['https', 'http'] as $scheme) {
            $url = $scheme.'://'.$domain.self::WELL_KNOWN_PATH;

            try {
                $target = $this->guard->resolve($url, requirePublic: true);

                $response = Http::timeout(self::TIMEOUT)
                    ->withOptions(['curl' => [CURLOPT_RESOLVE => [$target->curlResolveEntry()]]])
                    ->get($url);

                if ($response->successful() && str_contains(trim($response->body()), $token)) {
                    return true;
                }
            } catch (Throwable) {
                continue;
            }
        }

        return false;
    }
}
