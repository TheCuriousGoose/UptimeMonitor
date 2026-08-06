<?php

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

    ],

];
