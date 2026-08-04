<?php

namespace App\Checkers\Support;

class StreamCertificateReader implements CertificateReader
{
    public function read(string $host, int $port, float $timeout): ?array
    {
        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                // We want to inspect expiry ourselves, so accept the cert first
                // and report on what we find rather than failing the handshake.
                'verify_peer' => false,
                'verify_peer_name' => false,
                'SNI_enabled' => true,
                'peer_name' => $host,
            ],
        ]);

        $client = @stream_socket_client(
            "ssl://{$host}:{$port}",
            $errorNumber,
            $errorMessage,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context,
        );

        if ($client === false) {
            return null;
        }

        $params = stream_context_get_params($client);
        fclose($client);

        $certificate = $params['options']['ssl']['peer_certificate'] ?? null;

        if (! $certificate) {
            return null;
        }

        $parsed = openssl_x509_parse($certificate);

        if (! is_array($parsed)) {
            return null;
        }

        return [
            'valid_from' => (int) ($parsed['validFrom_time_t'] ?? 0),
            'valid_to' => (int) ($parsed['validTo_time_t'] ?? 0),
            'issuer' => (string) ($parsed['issuer']['CN'] ?? $parsed['issuer']['O'] ?? ''),
            'subject' => (string) ($parsed['subject']['CN'] ?? ''),
        ];
    }
}
