<?php

namespace App\Checkers;

use App\Models\Monitor;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class HttpChecker implements Checker
{
    public function check(Monitor $monitor): CheckResult
    {
        $config = $monitor->resolvedConfig();
        $start = hrtime(true);

        try {
            $request = Http::timeout($monitor->timeout)->withUserAgent($this->userAgent());

            // Tolerate "0"/"false" from monitors saved before config values
            // were cast, so an existing opt-out is still honoured.
            if (! filter_var($config['verify_ssl'] ?? true, FILTER_VALIDATE_BOOLEAN)) {
                $request = $request->withoutVerifying();
            }

            $response = $request->send(strtoupper($config['method'] ?? 'GET'), $monitor->url);
            $ms = $this->elapsedMs($start);

            $meta = [
                'status_code' => $response->status(),
                'checker' => $monitor->type->value,
            ];

            if ($statusError = $this->assertStatus($response, $config)) {
                return CheckResult::down($statusError, $ms, $meta);
            }

            if ($bodyError = $this->assertBody($response, $config)) {
                return CheckResult::down($bodyError, $ms, $meta);
            }

            return CheckResult::up($ms, $meta);
        } catch (Throwable $e) {
            return CheckResult::down(
                $this->explain($e->getMessage()),
                $this->elapsedMs($start),
                ['checker' => $monitor->type->value],
            );
        }
    }

    public function queue(): string
    {
        return 'checks-http';
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function assertStatus(Response $response, array $config): ?string
    {
        $expected = $config['expected_status'] ?? null;

        if ($expected !== null) {
            return $response->status() === (int) $expected
                ? null
                : "Expected HTTP {$expected}, got {$response->status()}";
        }

        return $response->status() < 400 ? null : "HTTP {$response->status()}";
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

    /**
     * Raw cURL strings are not actionable. Translate the ones with a known
     * cause into something that says what to actually do about it.
     */
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

    protected function elapsedMs(float|int $start): int
    {
        return (int) ((hrtime(true) - $start) / 1_000_000);
    }

    protected function userAgent(): string
    {
        return config('app.name', 'Laravel').' Uptime Monitor';
    }
}
