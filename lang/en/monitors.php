<?php

return [
    'title' => 'Monitors',
    'table' => [
        'header' => 'Your monitors',
        'columns' => [
            'name' => 'Name',
            'is_up' => 'Is Up',
            'type' => 'Type',
            'url' => 'URL',
            'interval' => 'Interval',
            'timeout' => 'Timeout',
            'actions' => 'Actions',
        ],
        'filters' => [
            'search' => [
                'placeholder' => 'Search monitors...',
            ],
        ],
        'empty' => 'No monitors found',
    ],
    'is_up' => 'Up',
    'is_down' => 'Down',
    'no_data' => 'No data',
    'wizard' => [
        'back' => 'Back',
        'next' => 'Continue',
        'step1' => [
            'label' => 'Basics',
            'title' => 'Name your monitor',
            'description' => 'Give your monitor a name and choose the type of check to perform.',
        ],
        'step2' => [
            'label' => 'Target',
            'title' => 'Configure the target',
            'description' => 'Provide the details the checker needs to run.',
        ],
        'step3' => [
            'label' => 'Schedule',
            'title' => 'Set the schedule',
            'description' => 'Control how often the monitor runs and how long to wait for a response.',
        ],
        'step4' => [
            'label' => 'Review',
            'title' => 'Review & create',
            'description' => 'Confirm the details below, then create your monitor.',
        ],
    ],
    'create' => [
        'label' => 'New Monitor',
        'form' => [
            'title' => 'New monitor',
            'subtitle' => 'Add a URL you want to keep an eye on.',
        ],
    ],
    'form' => [
        'submit' => 'Save Monitor',
        'custom' => 'Custom',
        'name' => [
            'title' => 'Name',
            'description' => 'A friendly name for this monitor.',
        ],
        'url' => [
            'title' => 'URL',
            'description' => 'The full URL to monitor, including https://.',
        ],
        'type' => [
            'title' => 'Check type',
            'description' => 'The kind of check this monitor will perform.',
            'options' => [
                'http' => 'HTTP / HTTPS',
            ],
        ],
        'timeout' => [
            'title' => 'Timeout',
            'description' => 'How long to wait before marking the check as failed.',
            'custom_placeholder' => 'Seconds, e.g. 45',
            'options' => [
                '10s' => '10 seconds',
                '30s' => '30 seconds',
                '1m' => '1 minute',
                '2m' => '2 minutes',
                '5m' => '5 minutes',
            ],
        ],
        'check_interval' => [
            'title' => 'Check interval',
            'description' => 'How often the monitor should run.',
            'custom_placeholder' => 'Cron expression, e.g. */45 * * * *',
            'options' => [
                'every_minute' => 'Every minute',
                'every_5_minutes' => 'Every 5 minutes',
                'every_10_minutes' => 'Every 10 minutes',
                'every_15_minutes' => 'Every 15 minutes',
                'every_30_minutes' => 'Every 30 minutes',
                'every_hour' => 'Every hour',
            ],
        ],
        'is_active' => [
            'title' => 'Active',
            'description' => 'Monitor will start running checks immediately.',
        ],
    ],
    'breadcrumbs' => [
        'index' => 'Monitors',
        'create' => 'Create',
        'show' => ':name',
    ],
    'periods' => [
        '1h' => 'Last 1 hour',
        '24h' => 'Last 24 hours',
        '7d' => 'Last 7 days',
        '30d' => 'Last 30 days',
    ],
    'messages' => [
        'created' => [
            'success' => 'Your monitor has successfully been created',
            'error' => 'Something went wrong while creating your monitor',
        ],
    ],
    'uptime_timeline_for' => 'Uptime timeline for :name',
];
