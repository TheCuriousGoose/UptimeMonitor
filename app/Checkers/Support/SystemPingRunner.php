<?php

namespace App\Checkers\Support;

use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

/**
 * Shells out to the platform `ping` binary. ICMP sockets normally require
 * elevated privileges, whereas the system binary is setuid nearly everywhere.
 */
class SystemPingRunner implements PingRunner
{
    public function ping(string $host, float $timeout): array
    {
        $process = new Process($this->command($host, $timeout));
        $process->setTimeout($timeout + 5);

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            return ['reachable' => false, 'latency_ms' => null, 'output' => 'Ping timed out'];
        }

        $output = trim($process->getOutput()."\n".$process->getErrorOutput());

        return [
            'reachable' => $process->isSuccessful() && $this->sawReply($output),
            'latency_ms' => $this->parseLatency($output),
            'output' => $output,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function command(string $host, float $timeout): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // -n count, -w timeout in milliseconds
            return ['ping', '-n', '1', '-w', (string) (int) ($timeout * 1000), $host];
        }

        // -c count, -W timeout in seconds
        return ['ping', '-c', '1', '-W', (string) max(1, (int) $timeout), $host];
    }

    /**
     * A zero exit code is not enough on Windows, where "Destination host
     * unreachable" replies still exit successfully.
     */
    private function sawReply(string $output): bool
    {
        if (preg_match('/unreachable|100% (packet )?loss|100% loss/i', $output)) {
            return false;
        }

        return (bool) preg_match('/ttl[=\s]/i', $output);
    }

    private function parseLatency(string $output): ?int
    {
        if (preg_match('/time[=<]\s*([\d.]+)\s*ms/i', $output, $matches)) {
            return (int) round((float) $matches[1]);
        }

        return null;
    }
}
