<?php

namespace App\Checkers\Support;

use RuntimeException;

/**
 * Decides whether the app is allowed to make a request to a given URL, and
 * pins the address it resolved to.
 *
 * The checkers fetch a URL the user supplied, which makes them a
 * server-side-request-forgery primitive: without this, a monitor pointed at
 * http://169.254.169.254/ or an internal admin panel turns the app into a
 * probe for its own network. The reply is narrow (a status code, a duration,
 * an error string) but a keyword monitor widens it to a readable bit per
 * request.
 *
 * Self-hosted installs legitimately monitor their own private network, so
 * private targets stay allowed by default. What is never allowed is pointing
 * *credentials* at them — see MonitorRequest, which refuses headers, a body
 * or auth on a private URL regardless of that setting.
 */
final class OutboundGuard
{
    public function __construct(private readonly DnsResolver $resolver) {}

    /**
     * Resolve the host and return the address to pin to, or throw.
     *
     * @param  bool  $requirePublic  Set when the request carries credentials.
     */
    public function resolve(string $url, bool $requirePublic): ResolvedTarget
    {
        $parts = parse_url($url);
        $host = $parts['host'] ?? '';
        $scheme = strtolower($parts['scheme'] ?? 'http');

        if ($host === '' || ! in_array($scheme, ['http', 'https'], true)) {
            throw new RuntimeException("Refusing to check an unsupported URL: {$url}");
        }

        if ($this->isDenied($host)) {
            throw new RuntimeException("Refusing to check a denied host: {$host}");
        }

        $addresses = $this->addressesFor($host);

        if ($addresses === []) {
            throw new RuntimeException("Could not resolve {$host}");
        }

        $public = array_values(array_filter($addresses, fn (string $ip) => $this->isPublic($ip)));

        if ($requirePublic && $public === []) {
            throw new RuntimeException(
                "Refusing to send credentials to a private address ({$host})",
            );
        }

        if (! $requirePublic && ! config('monitoring.outbound.allow_private_targets', true) && $public === []) {
            throw new RuntimeException("Refusing to check a private address ({$host})");
        }

        // Pin to a vetted address. Resolving once here and reusing the answer
        // is what closes the window in which DNS could be re-pointed at a
        // private address between this check and the connection itself.
        $address = ($requirePublic ? $public : $addresses)[0];

        return new ResolvedTarget(
            host: $host,
            address: $address,
            port: (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80)),
        );
    }

    public function isPublic(string $ip): bool
    {
        return (bool) filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        );
    }

    /**
     * @return array<int, string>
     */
    private function addressesFor(string $host): array
    {
        // An IP literal needs no lookup, and dns_get_record would not find it.
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        return array_values(array_filter([
            ...$this->resolver->resolve($host, 'A'),
            ...$this->resolver->resolve($host, 'AAAA'),
        ]));
    }

    private function isDenied(string $host): bool
    {
        $denied = array_map('strtolower', (array) config('monitoring.outbound.denied_hosts', []));

        return in_array(strtolower($host), $denied, true);
    }
}
