<?php

namespace App\Monitoring;

use App\Models\Monitor;

/**
 * The canonical identity of whatever a monitor points at.
 *
 * Abuse controls budget by registrable domain, not by URL: without that,
 * example.com/1 and example.com/2 are separate targets, and so are
 * EXAMPLE.com, example.com. and example.com:443.
 */
final readonly class TargetIdentity
{
    /**
     * Public suffixes of more than one label.
     *
     * Approximates the Public Suffix List. The SaaS entries matter as much as
     * the country ones: without them every *.vercel.app in the instance shares
     * a single budget and unrelated tenants starve each other.
     *
     * @var array<int, string>
     */
    private const MULTI_LABEL_SUFFIXES = [
        'co.uk', 'org.uk', 'me.uk', 'ac.uk', 'gov.uk', 'net.uk', 'sch.uk', 'ltd.uk', 'plc.uk',
        'com.au', 'net.au', 'org.au', 'edu.au', 'gov.au', 'id.au', 'asn.au',
        'co.nz', 'net.nz', 'org.nz', 'govt.nz', 'ac.nz', 'geek.nz',
        'co.za', 'org.za', 'net.za', 'web.za', 'gov.za',
        'com.br', 'net.br', 'org.br', 'gov.br', 'edu.br',
        'co.jp', 'ne.jp', 'or.jp', 'ac.jp', 'go.jp', 'lg.jp',
        'com.cn', 'net.cn', 'org.cn', 'gov.cn', 'edu.cn',
        'co.in', 'net.in', 'org.in', 'gen.in', 'firm.in', 'gov.in',
        'com.mx', 'com.ar', 'com.tr', 'com.sg', 'com.hk', 'com.tw',
        'com.my', 'com.ua', 'com.pl', 'com.ru', 'com.co', 'com.ph', 'com.vn',
        'co.kr', 'or.kr', 'ne.kr', 'go.kr',
        'co.il', 'org.il', 'net.il', 'ac.il', 'gov.il',
        'co.id', 'or.id', 'web.id', 'ac.id', 'go.id',
        'co.th', 'in.th', 'ac.th', 'go.th',
        'github.io', 'gitlab.io', 'pages.dev', 'workers.dev', 'vercel.app',
        'netlify.app', 'herokuapp.com', 'azurewebsites.net', 'cloudfront.net',
        'amazonaws.com', 'elb.amazonaws.com', 'fly.dev', 'onrender.com',
        'ondigitalocean.app', 'firebaseapp.com', 'web.app', 'appspot.com',
    ];

    private function __construct(
        public string $host,
        public string $domain,
    ) {}

    public static function forMonitor(Monitor $monitor): ?self
    {
        return self::fromTarget((string) $monitor->url);
    }

    /**
     * Accepts either a full URL or a bare hostname, since monitor types differ
     * in which they store.
     */
    public static function fromTarget(?string $value): ?self
    {
        $host = self::hostFrom($value);

        if ($host === null) {
            return null;
        }

        return new self($host, self::registrableDomain($host));
    }

    /**
     * IP literals are their own domain — there is nothing above them to group
     * by, and an attacker naming the address directly must still be budgeted.
     */
    public function isAddress(): bool
    {
        return (bool) filter_var($this->host, FILTER_VALIDATE_IP);
    }

    private static function hostFrom(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $host = str_contains($value, '://')
            ? (string) parse_url($value, PHP_URL_HOST)
            : (string) preg_replace('#[/?\#].*$#', '', $value);

        $host = trim($host);

        // Bare "host:port" only — an IPv6 literal keeps its colons.
        if (! str_starts_with($host, '[') && substr_count($host, ':') === 1) {
            $host = (string) strstr($host, ':', before_needle: true);
        }

        $host = strtolower(rtrim(trim($host, '[]'), '.'));

        if ($host === '') {
            return null;
        }

        if (! filter_var($host, FILTER_VALIDATE_IP) && function_exists('idn_to_ascii')) {
            $ascii = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);

            if (is_string($ascii) && $ascii !== '') {
                $host = $ascii;
            }
        }

        return $host;
    }

    private static function registrableDomain(string $host): string
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $host;
        }

        $labels = explode('.', $host);

        if (count($labels) <= 2) {
            return $host;
        }

        foreach (self::MULTI_LABEL_SUFFIXES as $suffix) {
            if (str_ends_with($host, '.'.$suffix)) {
                $depth = substr_count($suffix, '.') + 2;

                return implode('.', array_slice($labels, -$depth));
            }
        }

        return implode('.', array_slice($labels, -2));
    }
}
