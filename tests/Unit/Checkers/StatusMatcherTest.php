<?php

namespace Tests\Unit\Checkers;

use App\Checkers\StatusMatcher;
use PHPUnit\Framework\TestCase;

class StatusMatcherTest extends TestCase
{
    public function test_no_expectation_accepts_anything_below_400(): void
    {
        $matcher = StatusMatcher::fromConfig([]);

        $this->assertTrue($matcher->matches(200));
        $this->assertTrue($matcher->matches(301));
        $this->assertTrue($matcher->matches(399));
        $this->assertFalse($matcher->matches(400));
        $this->assertFalse($matcher->matches(500));
    }

    public function test_it_matches_exact_codes(): void
    {
        $matcher = StatusMatcher::fromConfig(['expected_status_codes' => ['204', '418']]);

        $this->assertTrue($matcher->matches(204));
        $this->assertTrue($matcher->matches(418));
        $this->assertFalse($matcher->matches(200));
    }

    public function test_it_matches_inclusive_ranges(): void
    {
        $matcher = StatusMatcher::fromConfig(['expected_status_codes' => ['200-204']]);

        $this->assertTrue($matcher->matches(200));
        $this->assertTrue($matcher->matches(204));
        $this->assertFalse($matcher->matches(205));
    }

    public function test_it_matches_status_classes(): void
    {
        $matcher = StatusMatcher::fromConfig(['expected_status_codes' => ['2xx', '3XX']]);

        $this->assertTrue($matcher->matches(200));
        $this->assertTrue($matcher->matches(299));
        $this->assertTrue($matcher->matches(301));
        $this->assertFalse($matcher->matches(404));
    }

    /**
     * Monitors saved before ranges existed, and API clients that still post a
     * single int, must keep working untouched.
     */
    public function test_it_falls_back_to_the_legacy_single_code(): void
    {
        $matcher = StatusMatcher::fromConfig(['expected_status' => 201]);

        $this->assertTrue($matcher->matches(201));
        $this->assertFalse($matcher->matches(200));
    }

    public function test_the_list_wins_over_the_legacy_code(): void
    {
        $matcher = StatusMatcher::fromConfig([
            'expected_status' => 201,
            'expected_status_codes' => ['200'],
        ]);

        $this->assertTrue($matcher->matches(200));
        $this->assertFalse($matcher->matches(201));
    }

    public function test_it_describes_the_expectation_for_the_error_message(): void
    {
        $this->assertSame('a status below 400', StatusMatcher::fromConfig([])->describe());

        $this->assertSame(
            'HTTP 200, 2xx',
            StatusMatcher::fromConfig(['expected_status_codes' => ['200', '2xx']])->describe(),
        );
    }
}
