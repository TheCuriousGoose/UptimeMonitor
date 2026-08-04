<?php

namespace Tests\Unit\Monitoring;

use App\Monitoring\QueueResolver;
use Tests\TestCase;

class QueueResolverTest extends TestCase
{
    public function test_everything_shares_the_default_queue_out_of_the_box(): void
    {
        config(['monitoring.separate_queues' => false, 'monitoring.queue' => 'default']);

        $resolver = new QueueResolver;

        $this->assertSame('default', $resolver->for('checks-http'));
        $this->assertSame('default', $resolver->for('checks-network'));
        $this->assertSame('default', $resolver->for(QueueResolver::LANE_ALERTS));
    }

    public function test_the_shared_queue_name_is_configurable(): void
    {
        config(['monitoring.separate_queues' => false, 'monitoring.queue' => 'monitoring']);

        $this->assertSame('monitoring', (new QueueResolver)->for('checks-http'));
    }

    public function test_lanes_are_kept_apart_when_separation_is_enabled(): void
    {
        config(['monitoring.separate_queues' => true]);

        $resolver = new QueueResolver;

        $this->assertSame('checks-http', $resolver->for('checks-http'));
        $this->assertSame('alerts', $resolver->for(QueueResolver::LANE_ALERTS));
    }

    public function test_all_lists_every_queue_horizon_must_cover(): void
    {
        config(['monitoring.queue' => 'default']);

        $this->assertSame(
            ['default', 'alerts', 'checks-http', 'checks-network'],
            (new QueueResolver)->all(),
        );
    }

    public function test_horizon_covers_every_queue_the_app_dispatches_to(): void
    {
        $covered = config('horizon.defaults.supervisor-1.queue');

        foreach ((new QueueResolver)->all() as $queue) {
            $this->assertContains($queue, $covered, "Horizon does not consume the [{$queue}] queue.");
        }
    }
}
