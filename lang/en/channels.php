<?php

return [
    'title' => 'Alerts',
    'heading' => 'Notification channels',
    'subtitle' => 'Where we send alerts when a monitor goes down or recovers.',
    'empty' => [
        'title' => 'No channels yet',
        'description' => 'Add an email address or webhook so you hear about outages.',
    ],
    'types' => [
        'email' => 'Email',
        'webhook' => 'Webhook',
        'slack' => 'Slack',
        'discord' => 'Discord',
    ],
    'hints' => [
        'email' => 'We send a plain email to this address.',
        'webhook' => 'We POST a JSON payload to this URL.',
        'slack' => 'Paste a Slack incoming webhook URL.',
        'discord' => 'Paste a Discord webhook URL.',
    ],
    'form' => [
        'create' => 'Add channel',
        'edit' => 'Edit channel',
        'submit' => 'Save channel',
        'name' => [
            'title' => 'Name',
            'placeholder' => 'Ops email',
        ],
        'type' => [
            'title' => 'Type',
        ],
        'email' => [
            'title' => 'Email address',
            'placeholder' => 'ops@example.com',
        ],
        'url' => [
            'title' => 'Webhook URL',
            'placeholder' => 'https://hooks.example.com/uptime',
        ],
        'is_active' => [
            'title' => 'Active',
            'description' => 'Inactive channels are skipped when alerting.',
        ],
    ],
    'actions' => [
        'test' => 'Send test alert',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'confirm_delete' => 'Delete this channel? Monitors using it will stop alerting here.',
    ],
    'attached' => ':count monitor|:count monitors',
    'test' => [
        'sample_monitor' => 'Example monitor',
        'sample_error' => 'This is a test alert — nothing is actually down.',
    ],
    'messages' => [
        'created' => ['success' => 'Channel added'],
        'updated' => ['success' => 'Channel updated'],
        'deleted' => ['success' => 'Channel deleted'],
        'tested' => ['success' => 'Test alert sent'],
    ],
];
