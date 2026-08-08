<?php

// An unset abuse limit means "no ceiling", which is not the same as zero.
$optional = fn (?string $value): ?int => ($value === null || $value === '') ? null : (int) $value;

return [

    /*
    |--------------------------------------------------------------------------
    | Check Retention
    |--------------------------------------------------------------------------
    |
    | How many days of individual check results to keep. Incidents are kept
    | indefinitely, so shortening this only reduces per-check granularity.
    |
    */

    'retention_days' => (int) env('MONITORING_RETENTION_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | Queues
    |--------------------------------------------------------------------------
    |
    | Checks and alerts all share a single queue by default, so one worker is
    | enough to run the whole app. Enable "separate_queues" only if you want
    | to scale check throughput independently of alert delivery.
    |
    */

    'queue' => env('MONITORING_QUEUE', 'default'),

    'separate_queues' => (bool) env('MONITORING_SEPARATE_QUEUES', false),

    /*
    |--------------------------------------------------------------------------
    | Monitor Limits
    |--------------------------------------------------------------------------
    |
    | Guard rails applied when creating or updating monitors.
    |
    */

    'min_interval_seconds' => (int) env('MONITORING_MIN_INTERVAL', 30),

    'max_interval_seconds' => 86400,

    'max_timeout_seconds' => 300,

    /*
    |--------------------------------------------------------------------------
    | Outbound Requests
    |--------------------------------------------------------------------------
    |
    | Checks fetch a URL the user supplied, which makes them a server-side
    | request forgery primitive. Self-hosted installs legitimately monitor
    | their own private network, so private targets are allowed by default —
    | set this to false on a shared or multi-tenant deployment.
    |
    | Regardless of this setting, a monitor carrying credentials (request
    | headers, a body, or auth) is never permitted to point at a private
    | address. That is enforced at save time and again at check time.
    |
    */

    'outbound' => [

        'allow_private_targets' => (bool) env('MONITORING_ALLOW_PRIVATE_TARGETS', true),

        'denied_hosts' => array_filter(
            explode(',', (string) env('MONITORING_DENIED_HOSTS', '')),
        ),

        'contact_url' => env('MONITORING_CONTACT_URL'),

    ],

    /*
    |--------------------------------------------------------------------------
    | Abuse Controls
    |--------------------------------------------------------------------------
    |
    | Scheduled checks are dispatched by cron, so the HTTP rate limiters never
    | see them. Without a ceiling here, one account pointing many monitors at
    | one third party turns the instance into a traffic amplifier aimed at a
    | host nobody involved owns.
    |
    | Every limit is null by default: a self-hosted install monitoring its own
    | estate should not trip over any of this. Set them on a shared or
    | multi-tenant deployment.
    |
    */

    'abuse' => [

        // Aggregate requests per minute allowed against one registrable
        // domain, summed across every active monitor in the instance.
        'max_requests_per_minute_per_domain' => $optional(env('MONITORING_MAX_RPM_PER_DOMAIN')),

        // The same ceiling applied to a single account, so one tenant cannot
        // consume the whole instance budget for a domain.
        'max_requests_per_minute_per_domain_per_user' => $optional(
            env('MONITORING_MAX_RPM_PER_DOMAIN_PER_USER'),
        ),

        'max_monitors_per_user' => $optional(env('MONITORING_MAX_MONITORS_PER_USER')),

        // Consecutive 429/403 responses before a monitor is paused. The target
        // is refusing the traffic; continuing to send it is the abuse.
        'refusals_before_pause' => (int) env('MONITORING_REFUSALS_BEFORE_PAUSE', 10),

        /*
        | Domain ownership verification. With this on, a target whose domain
        | nobody on the instance has proven they own is held to the unverified
        | limits below: slow, few, and read-only.
        */
        'require_domain_verification' => (bool) env('MONITORING_REQUIRE_DOMAIN_VERIFICATION', false),

        'unverified' => [
            'min_interval_seconds' => (int) env('MONITORING_UNVERIFIED_MIN_INTERVAL', 300),
            'max_monitors_per_domain_per_user' => (int) env('MONITORING_UNVERIFIED_MAX_MONITORS', 1),
            'allowed_methods' => ['GET', 'HEAD'],
        ],

    ],

];
