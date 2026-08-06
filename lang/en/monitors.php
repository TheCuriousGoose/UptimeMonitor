<?php

return [
    'title' => 'Monitors',
    'table' => [
        'header' => 'Your monitors',
        'columns' => [
            'name' => 'Name',
            'status' => 'Status',
            'type' => 'Type',
            'url' => 'Target',
            'interval' => 'Interval',
            'last_checked' => 'Last checked',
            'actions' => 'Actions',
        ],
        'filters' => [
            'search' => [
                'placeholder' => 'Search monitors...',
            ],
            'status' => [
                'label' => 'Status',
                'all' => 'All statuses',
            ],
        ],
        'empty' => 'No monitors found',
    ],
    'status' => [
        'up' => 'Up',
        'down' => 'Down',
        'paused' => 'Paused',
        'pending' => 'Awaiting first check',
    ],
    'empty' => [
        'title' => 'Add your first monitor',
        'description' => 'Point us at a URL, host or port and we will tell you the moment it stops responding.',
    ],
    'is_up' => 'Up',
    'is_down' => 'Down',
    'no_data' => 'No data',
    'never_checked' => 'Never checked',
    'create' => [
        'label' => 'New monitor',
        'form' => [
            'title' => 'New monitor',
            'subtitle' => 'Tell us what to watch and how often to check it.',
        ],
    ],
    'edit' => [
        'label' => 'Edit monitor',
        'form' => [
            'title' => 'Edit monitor',
            'subtitle' => 'Change what this monitor watches or how it alerts you.',
        ],
    ],
    'actions' => [
        'view' => 'View',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'pause' => 'Pause',
        'resume' => 'Resume',
        'check_now' => 'Check now',
        'confirm_delete' => 'Delete this monitor and all of its history? This cannot be undone.',
        'confirm_bulk_delete' => 'Delete :count monitors and all of their history? This cannot be undone.',
    ],
    'form' => [
        'submit' => 'Save monitor',
        'custom' => 'Custom',
        'name' => [
            'title' => 'Name',
            'description' => 'A friendly name for this monitor.',
            'placeholder' => 'Marketing site',
        ],
        'url' => [
            'title' => 'URL',
            'description' => 'The full URL to check, including https://.',
            'placeholder' => 'https://example.com',
            'host_title' => 'Host',
            'host_description' => 'A hostname or IP address, with no http:// prefix.',
            'host_placeholder' => 'example.com',
        ],
        'type' => [
            'title' => 'What should we check?',
            'description' => 'Pick the kind of check that fits this service.',
            'options' => [
                'http' => 'Website / API',
                'keyword' => 'Website contains keyword',
                'port' => 'TCP port',
                'ping' => 'Ping (ICMP)',
                'dns' => 'DNS record',
                'ssl' => 'TLS certificate expiry',
            ],
            'hints' => [
                'http' => 'Fails when the site is unreachable or returns an error status.',
                'keyword' => 'Also fails when the page loads but the text you expect is missing.',
                'port' => 'Checks that something is listening on a TCP port.',
                'ping' => 'Sends an ICMP echo request to the host.',
                'dns' => 'Checks a DNS record resolves, optionally to an expected value.',
                'ssl' => 'Fails before the certificate expires so you have time to renew.',
            ],
        ],
        'config' => [
            'keyword' => [
                'title' => 'Keyword',
                'description' => 'Text that must appear in the response body.',
                'placeholder' => 'All systems operational',
            ],
            'invert' => [
                'title' => 'Fail when found instead',
                'description' => 'Useful for catching error text such as "Fatal error".',
            ],
            'method' => [
                'title' => 'HTTP method',
            ],
            'expected_status' => [
                'title' => 'Expected status code',
                'description' => 'Leave empty to accept any successful response.',
                'placeholder' => 'Any success',
            ],
            'verify_ssl' => [
                'title' => 'Verify TLS certificate',
                'description' => 'Turn off for internal services using self-signed certificates.',
            ],
            'port' => [
                'title' => 'Port',
                'description' => 'The TCP port that should accept connections.',
            ],
            'record_type' => [
                'title' => 'Record type',
            ],
            'expected' => [
                'title' => 'Expected value',
                'description' => 'Optional. Fails if the record does not contain this value.',
                'placeholder' => '93.184.216.34',
            ],
            'warn_days' => [
                'title' => 'Warn this many days before expiry',
                'description' => 'The monitor goes down once the certificate has fewer days left.',
            ],
        ],
        'timeout' => [
            'title' => 'Timeout',
            'description' => 'How long to wait before treating the check as failed.',
            'custom_placeholder' => 'Seconds, e.g. 45',
            'options' => [
                '5s' => '5 seconds',
                '10s' => '10 seconds',
                '30s' => '30 seconds',
                '60s' => '1 minute',
            ],
        ],
        'check_interval' => [
            'title' => 'Check every',
            'description' => 'How often the monitor runs.',
            'custom_placeholder' => 'Seconds, e.g. 45',
            'options' => [
                '30s' => '30 seconds',
                '1m' => '1 minute',
                '5m' => '5 minutes',
                '10m' => '10 minutes',
                '30m' => '30 minutes',
                '1h' => '1 hour',
            ],
        ],
        'confirmation_threshold' => [
            'title' => 'Confirm failures',
            'description' => 'How many failures in a row before we call it an outage. Higher values ignore brief blips.',
            'options' => [
                '1' => 'Alert on the first failure',
                '2' => 'After 2 failures',
                '3' => 'After 3 failures',
                '5' => 'After 5 failures',
            ],
        ],
        'recovery_threshold' => [
            'title' => 'Confirm recoveries',
            'description' => 'How many successes in a row before we call it resolved. Higher values stop a flapping service announcing itself fixed too early.',
            'options' => [
                '1' => 'Resolve on the first success',
                '2' => 'After 2 successes',
                '3' => 'After 3 successes',
                '5' => 'After 5 successes',
            ],
        ],
        'is_active' => [
            'title' => 'Start checking right away',
            'description' => 'Turn off to create the monitor paused.',
        ],
        'channels' => [
            'title' => 'Alert these integrations',
            'description' => 'Who to notify when this monitor goes down and recovers.',
            'empty' => 'No integrations yet. Add one to get alerted.',
            'manage' => 'Manage integrations',
            'covers_all' => 'alerts on all monitors',
        ],
        'sections' => [
            'what' => 'What to check',
            'schedule' => 'Schedule',
            'alerts' => 'Alerts',
        ],
    ],
    'breadcrumbs' => [
        'index' => 'Monitors',
        'create' => 'Create',
        'show' => ':name',
        'edit' => 'Edit :name',
    ],
    'periods' => [
        '1h' => 'Last hour',
        '24h' => 'Last 24 hours',
        '7d' => 'Last 7 days',
        '30d' => 'Last 30 days',
        '90d' => 'Last 90 days',
    ],
    'stats' => [
        'uptime' => 'Uptime',
        'avg_response' => 'Avg response',
        'p95_response' => 'p95 response',
        'incidents' => 'Incidents',
        'downtime' => 'Downtime',
        'checks' => 'Checks',
        'none' => '—',
    ],
    'show' => [
        'timeline' => 'Uptime timeline',
        'response_chart' => 'Response time',
        'incidents' => 'Incident history',
        'no_incidents' => 'No incidents in this period. Nice.',
        'details' => 'Details',
        'last_checked' => 'Last checked',
        'next_check' => 'Next check',
        'ongoing' => 'Ongoing since :time',
        'resolved_after' => 'Resolved after :duration',
    ],
    'messages' => [
        'created' => [
            'success' => 'Your monitor has been created',
            'error' => 'Something went wrong while creating your monitor',
        ],
        'updated' => [
            'success' => 'Your monitor has been updated',
        ],
        'deleted' => [
            'success' => 'Your monitor has been deleted',
        ],
        'paused' => [
            'success' => 'Monitor paused',
        ],
        'resumed' => [
            'success' => 'Monitor resumed',
        ],
        'check_queued' => [
            'success' => 'Check queued — results appear in a moment',
        ],
        'bulk' => [
            'pause' => ':count monitors paused',
            'resume' => ':count monitors resumed',
            'delete' => ':count monitors deleted',
            'none' => 'Nothing was changed — you cannot act on the selected monitors',
        ],
    ],
    'uptime_timeline_for' => 'Uptime timeline for :name',
];
