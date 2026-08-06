<?php

namespace Tests\Feature\Monitors;

use App\Enums\ChannelType;
use App\Models\Monitor;
use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * notification_channels.config carries credentials that are enough to post as
 * the user, so a database dump must not hand them over in readable form.
 */
class ConfigEncryptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function user(): User
    {
        return User::factory()->withRole('User')->create();
    }

    public function test_a_channel_secret_is_not_readable_in_the_database(): void
    {
        $secret = 'https://hooks.slack.com/services/T000/B000/super-secret';

        $channel = NotificationChannel::factory()->for($this->user(), 'user')->create([
            'type' => ChannelType::Slack,
            'config' => ['url' => $secret],
        ]);

        $stored = DB::table('notification_channels')->where('id', $channel->id)->value('config');

        $this->assertStringNotContainsString($secret, $stored);
        // The envelope is base64, so the column no longer parses as JSON at all.
        $this->assertNull(json_decode($stored, true));
    }

    public function test_a_channel_config_round_trips_through_the_cast(): void
    {
        $config = ['url' => 'https://example.test/hook', 'nested' => ['a' => 1]];

        $channel = NotificationChannel::factory()->for($this->user(), 'user')->create([
            'type' => ChannelType::Webhook,
            'config' => $config,
        ]);

        $this->assertSame($config, $channel->fresh()->config);
    }

    public function test_a_monitor_config_round_trips_through_the_cast(): void
    {
        $monitor = Monitor::factory()->forUser($this->user())->create([
            'config' => ['method' => 'GET', 'verify_ssl' => true, 'expected_status' => null],
        ]);

        $fresh = $monitor->fresh();

        $this->assertSame('GET', $fresh->config['method']);
        $this->assertTrue($fresh->config['verify_ssl']);
        $this->assertNull($fresh->config['expected_status']);
    }

    public function test_a_null_config_stays_null(): void
    {
        $monitor = Monitor::factory()->forUser($this->user())->create(['config' => null]);

        $this->assertNull($monitor->fresh()->config);
    }

    /**
     * The compatibility path that makes a zero-downtime deploy possible: a row
     * written before the backfill reached it is still plaintext, and a worker
     * reading it must not throw. See App\Casts\EncryptedJson.
     */
    public function test_a_row_left_in_plaintext_is_still_readable(): void
    {
        $monitor = Monitor::factory()->forUser($this->user())->create();

        DB::table('monitors')
            ->where('id', $monitor->id)
            ->update(['config' => json_encode(['method' => 'HEAD'])]);

        $this->assertSame(['method' => 'HEAD'], $monitor->fresh()->config);
    }

    /**
     * RefreshDatabase runs the migration against empty tables, so the backfill
     * — the part that actually touches a live install's existing secrets — is
     * never exercised by the suite. Drive it directly.
     */
    public function test_the_migration_backfill_encrypts_rows_written_before_it_ran(): void
    {
        $channel = NotificationChannel::factory()->for($this->user(), 'user')->create();

        $plaintext = json_encode(['url' => 'https://hooks.example.test/legacy-secret']);

        DB::table('notification_channels')->where('id', $channel->id)->update(['config' => $plaintext]);

        (require database_path('migrations/2026_08_06_100000_encrypt_config_columns.php'))->up();

        $stored = DB::table('notification_channels')->where('id', $channel->id)->value('config');

        $this->assertNotSame($plaintext, $stored);
        $this->assertStringNotContainsString('legacy-secret', $stored);
        $this->assertSame(
            ['url' => 'https://hooks.example.test/legacy-secret'],
            $channel->fresh()->config,
        );

        // Re-running after a partial failure must not encrypt a second time:
        // the cast peels one layer and the credential would be unrecoverable.
        (require database_path('migrations/2026_08_06_100000_encrypt_config_columns.php'))->up();

        $this->assertSame(
            ['url' => 'https://hooks.example.test/legacy-secret'],
            $channel->fresh()->config,
        );
    }

    public function test_the_destination_helper_still_reads_through_the_cast(): void
    {
        $channel = NotificationChannel::factory()->for($this->user(), 'user')->create([
            'type' => ChannelType::Slack,
            'config' => ['url' => 'https://hooks.slack.com/services/abcd1234'],
        ]);

        $this->assertSame(
            'https://hooks.slack.com/services/abcd1234',
            $channel->fresh()->destination(),
        );
    }
}
