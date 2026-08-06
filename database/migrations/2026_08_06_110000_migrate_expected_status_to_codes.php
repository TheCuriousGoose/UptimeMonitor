<?php

use App\Models\Monitor;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * expected_status held a single code; expected_status_codes holds a list
     * that also accepts ranges and classes.
     *
     * The old key is deliberately left in place rather than removed. It is
     * still in MonitorType::defaultConfig(), so an API client that has not
     * been updated keeps working, and StatusMatcher falls back to it when the
     * list is empty. A null becomes an empty list, which the matcher reads as
     * "anything below 400" — byte-identical to the previous behaviour.
     *
     * Written through the model rather than the query builder because config
     * is encrypted; a raw UPDATE would store readable JSON.
     */
    public function up(): void
    {
        Monitor::query()->whereIn('type', ['http', 'keyword'])->cursor()
            ->each(function (Monitor $monitor): void {
                $config = $monitor->config ?? [];
                $legacy = $config['expected_status'] ?? null;

                if ($legacy === null || $legacy === '' || ! empty($config['expected_status_codes'])) {
                    return;
                }

                $config['expected_status_codes'] = [(string) (int) $legacy];

                $monitor->config = $config;
                $monitor->saveQuietly();
            });
    }

    public function down(): void
    {
        Monitor::query()->whereIn('type', ['http', 'keyword'])->cursor()
            ->each(function (Monitor $monitor): void {
                $config = $monitor->config ?? [];

                unset($config['expected_status_codes']);

                $monitor->config = $config;
                $monitor->saveQuietly();
            });
    }
};
