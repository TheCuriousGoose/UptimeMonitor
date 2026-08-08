<?php

namespace Tests\Unit;

use App\Monitoring\TargetIdentity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The budget is only as good as the normalisation: every case here is a way to
 * aim more traffic at one host while looking like separate targets.
 */
class TargetIdentityTest extends TestCase
{
    public static function targets(): array
    {
        return [
            'plain url' => ['https://example.com', 'example.com', 'example.com'],
            'path is not identity' => ['https://example.com/status/1', 'example.com', 'example.com'],
            'query is not identity' => ['https://example.com/?a=1', 'example.com', 'example.com'],
            'case folded' => ['https://EXAMPLE.com', 'example.com', 'example.com'],
            'trailing dot stripped' => ['https://example.com./x', 'example.com', 'example.com'],
            'port stripped' => ['https://example.com:8443/x', 'example.com', 'example.com'],
            'subdomains roll up' => ['https://a.b.example.com', 'a.b.example.com', 'example.com'],
            'bare hostname' => ['example.com', 'example.com', 'example.com'],
            'bare host with port' => ['example.com:8080', 'example.com', 'example.com'],
            'multi label suffix' => ['https://shop.example.co.uk', 'shop.example.co.uk', 'example.co.uk'],
            'saas suffix is not shared' => ['https://app.vercel.app', 'app.vercel.app', 'app.vercel.app'],
            'saas subdomain keeps its own budget' => [
                'https://one.two.vercel.app', 'one.two.vercel.app', 'two.vercel.app',
            ],
            'ipv4 literal' => ['http://203.0.113.9/x', '203.0.113.9', '203.0.113.9'],
        ];
    }

    #[DataProvider('targets')]
    public function test_it_normalises_targets(string $input, string $host, string $domain): void
    {
        $identity = TargetIdentity::fromTarget($input);

        $this->assertNotNull($identity);
        $this->assertSame($host, $identity->host);
        $this->assertSame($domain, $identity->domain);
    }

    public function test_it_rejects_empty_targets(): void
    {
        $this->assertNull(TargetIdentity::fromTarget(null));
        $this->assertNull(TargetIdentity::fromTarget('   '));
    }

    public function test_it_flags_address_targets(): void
    {
        $this->assertTrue(TargetIdentity::fromTarget('http://203.0.113.9')->isAddress());
        $this->assertFalse(TargetIdentity::fromTarget('https://example.com')->isAddress());
    }

    public function test_subdomain_spraying_lands_in_one_budget(): void
    {
        $domains = array_map(
            fn (string $host) => TargetIdentity::fromTarget($host)->domain,
            ['https://a.victim.com', 'https://b.victim.com', 'https://c.victim.com/deep/path'],
        );

        $this->assertSame(['victim.com'], array_unique($domains));
    }
}
