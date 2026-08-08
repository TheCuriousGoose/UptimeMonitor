<?php

namespace App\Checkers;

use App\Checkers\Support\OutboundGuard;
use App\Checkers\Support\ResolvedTarget;
use App\Checkers\Support\Stopwatch;
use App\Models\Monitor;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class HttpChecker extends BaseChecker
{
    public function __construct(protected readonly OutboundGuard $guard) {}

    protected function probe(Monitor $monitor, Stopwatch $timer): CheckResult
    {
        $config = $monitor->resolvedConfig();

        [$response, $url] = $this->send($monitor, $config);
        $ms = $timer->elapsedMs();

        $meta = [
            'status_code' => $response->status(),
            'checker' => $monitor->type->value,
        ];

        if ($url !== $monitor->url) {
            $meta['final_url'] = $url;
        }

        if ($statusError = $this->assertStatus($response, $config)) {
            return CheckResult::down($statusError, $ms, $meta);
        }

        if ($bodyError = $this->assertBody($response, $config)) {
            return CheckResult::down($bodyError, $ms, $meta);
        }

        return CheckResult::up($ms, $meta);
    }

    public function queue(): string
    {
        return 'checks-http';
    }

    /**
     * Follow the redirect chain by hand, vetting each hop.
     *
     * Guzzle's own follower would resolve and connect without the guard ever
     * seeing the intermediate URLs, so a public host that 302s to
     * 169.254.169.254 would walk straight through. Doing it here also makes
     * follow_redirects and max_redirects mean exactly what they say.
     *
     * @param  array<string, mixed>  $config
     * @return array{0: Response, 1: string}
     */
    private function send(Monitor $monitor, array $config): array
    {
        $carriesSecrets = $this->carriesSecrets($config);
        $follow = filter_var($config['follow_redirects'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $maxHops = (int) ($config['max_redirects'] ?? 5);

        $url = $monitor->url;
        $method = strtoupper($config['method'] ?? 'GET');

        for ($hop = 0; ; $hop++) {
            $target = $this->guard->resolve($url, requirePublic: $carriesSecrets);

            $response = $this->request($monitor, $config, $target)
                ->withoutRedirecting()
                ->send($method, $url);

            $location = $response->header('Location');

            if (! $follow || ! $response->redirect() || $location === '' || $hop >= $maxHops) {
                return [$response, $url];
            }

            $url = $this->absoluteUrl($location, $url);

            if (! in_array($response->status(), [307, 308], true)) {
                $method = $method === 'HEAD' ? 'HEAD' : 'GET';
            }
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function request(Monitor $monitor, array $config, ResolvedTarget $target): PendingRequest
    {
        $request = Http::timeout($monitor->timeout)
            ->withUserAgent($this->userAgent())
            ->withOptions(['curl' => [CURLOPT_RESOLVE => [$target->curlResolveEntry()]]]);

        if (! filter_var($config['verify_ssl'] ?? true, FILTER_VALIDATE_BOOLEAN)) {
            $request = $request->withoutVerifying();
        }

        $headers = array_filter(
            (array) ($config['headers'] ?? []),
            fn ($value, $name) => is_string($name) && is_string($value),
            ARRAY_FILTER_USE_BOTH,
        );

        if ($headers !== []) {
            $request = $request->withHeaders($headers);
        }

        $request = match ($config['auth_type'] ?? 'none') {
            'basic' => $request->withBasicAuth(
                (string) ($config['auth_username'] ?? ''),
                (string) ($config['auth_password'] ?? ''),
            ),
            'bearer' => $request->withToken((string) ($config['auth_token'] ?? '')),
            default => $request,
        };

        $body = $config['body'] ?? null;

        if (is_string($body) && $body !== '') {
            $request = $request->withBody($body, (string) ($config['content_type'] ?? 'text/plain'));
        }

        return $request;
    }

    private function carriesSecrets(array $config): bool
    {
        return ($config['headers'] ?? []) !== []
            || ($config['auth_type'] ?? 'none') !== 'none'
            || is_string($config['body'] ?? null) && $config['body'] !== '';
    }

    private function absoluteUrl(string $location, string $base): string
    {
        if (str_contains($location, '://')) {
            return $location;
        }

        $parts = parse_url($base);
        $origin = ($parts['scheme'] ?? 'http').'://'.($parts['host'] ?? '')
            .(isset($parts['port']) ? ':'.$parts['port'] : '');

        return str_starts_with($location, '/')
            ? $origin.$location
            : $origin.'/'.ltrim($location, '/');
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function assertStatus(Response $response, array $config): ?string
    {
        $matcher = StatusMatcher::fromConfig($config);

        if ($matcher->matches($response->status())) {
            return null;
        }

        return $matcher->isExplicit()
            ? "Expected {$matcher->describe()}, got {$response->status()}"
            : "HTTP {$response->status()}";
    }

    /**
     * Extension point for checkers that also inspect the response body.
     *
     * @param  array<string, mixed>  $config
     */
    protected function assertBody(Response $response, array $config): ?string
    {
        return null;
    }

    protected function explain(string $message): string
    {
        if (str_contains($message, 'unable to get local issuer certificate')) {
            return 'TLS verification failed: the server did not send its intermediate certificate. '
                .'Browsers hide this by fetching it themselves. Fix the chain on the server, '
                .'or turn off "Verify TLS certificate" for this monitor.';
        }

        if (str_contains($message, 'certificate has expired')) {
            return 'TLS verification failed: the certificate has expired.';
        }

        if (preg_match('/subject name does not match|no alternative certificate subject/i', $message)) {
            return 'TLS verification failed: the certificate does not cover this hostname.';
        }

        return $message;
    }

    protected function userAgent(): string
    {
        $agent = config('app.name', 'Laravel').' Uptime Monitor';
        $contact = config('monitoring.outbound.contact_url');

        return $contact ? "{$agent} (+{$contact})" : $agent;
    }
}
