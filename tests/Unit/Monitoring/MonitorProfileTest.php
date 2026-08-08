<?php

namespace Tests\Unit\Monitoring;

use App\Checkers\Checker;
use App\Enums\MonitorType;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The profiles exist so that a type's rules, defaults, casts and checker
 * cannot drift apart. These assert that they haven't.
 */
class MonitorProfileTest extends TestCase
{
    public static function types(): array
    {
        return array_map(fn (MonitorType $type) => [$type], MonitorType::cases());
    }

    #[DataProvider('types')]
    public function test_every_type_resolves_a_checker_implementing_the_interface(MonitorType $type): void
    {
        $checker = $type->profile()->checker();

        $this->assertTrue(class_exists($checker), "{$checker} does not exist");
        $this->assertContains(Checker::class, class_implements($checker));
    }

    /**
     * A key with no default is silently discarded on save, because
     * MonitorRequest intersects the submitted config against the defaults.
     */
    #[DataProvider('types')]
    public function test_every_validated_config_key_has_a_default(MonitorType $type): void
    {
        $defaults = $type->defaultConfig();
        $this->assertIsArray($defaults);

        foreach (array_keys($type->configRules()) as $rule) {
            // 'config.headers.*' describes entries of the 'headers' key.
            $key = explode('.', str_replace('config.', '', $rule))[0];

            $this->assertArrayHasKey(
                $key,
                $defaults,
                "[{$type->value}] validates '{$rule}' but declares no default for '{$key}', so it is dropped on save",
            );
        }
    }

    /**
     * A key with no cast falls through as whatever the form posted — which is
     * how a "0" checkbox became a truthy string.
     */
    #[DataProvider('types')]
    public function test_every_default_config_key_declares_a_cast(MonitorType $type): void
    {
        $casts = $type->configCasts();
        $this->assertIsArray($casts);

        foreach (array_keys($type->defaultConfig()) as $key) {
            $this->assertArrayHasKey(
                $key,
                $casts,
                "[{$type->value}] defaults '{$key}' but declares no cast for it",
            );
        }
    }

    #[DataProvider('types')]
    public function test_no_cast_is_declared_for_a_key_the_type_does_not_have(MonitorType $type): void
    {
        $defaults = $type->defaultConfig();
        $this->assertIsArray($defaults);

        foreach (array_keys($type->configCasts()) as $key) {
            $this->assertArrayHasKey(
                $key,
                $defaults,
                "[{$type->value}] casts '{$key}', which is not one of its config keys",
            );
        }
    }

    public function test_the_checker_map_covers_every_type(): void
    {
        $this->assertSame(MonitorType::values(), array_keys(MonitorType::checkerMap()));
    }

    public function test_secret_config_keys_belong_to_the_types_that_declare_them(): void
    {
        $httpKeys = array_keys(MonitorType::Http->defaultConfig());

        foreach (MonitorType::SECRET_KEYS as $key) {
            $this->assertContains($key, $httpKeys);
        }
    }
}
