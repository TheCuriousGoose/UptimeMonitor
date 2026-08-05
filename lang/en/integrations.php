<?php

return [
    'title' => 'Alerts',
    'heading' => 'Integrations',
    'subtitle' => 'Where we send alerts when a monitor goes down or recovers.',
    'breadcrumbs' => [
        'index' => 'Integrations',
    ],
    'available' => 'Available',
    'connected' => 'Connected',
    'empty' => [
        'title' => 'Nothing connected yet',
        'description' => 'Add an email address, a webhook, or an on-call tool so you hear about outages.',
    ],
    'types' => [
        'email' => 'Email',
        'webhook' => 'Webhook',
        'slack' => 'Slack',
        'discord' => 'Discord',
        'pagerduty' => 'PagerDuty',
        'opsgenie' => 'Opsgenie',
        'teams' => 'Microsoft Teams',
    ],
    'providers' => [
        'email' => [
            'name' => 'Email',
            'description' => 'Sends a plain email when a monitor goes down or recovers.',
            'field' => 'Email address',
            'hint' => 'We send a plain email to this address.',
            // vue-i18n reads a bare "@" as linked-message syntax, so it must
            // be escaped or the whole message fails to compile at render time.
            'placeholder' => 'ops{\'@\'}example.com',
        ],
        'webhook' => [
            'name' => 'Webhook',
            'description' => 'POSTs a JSON payload, so anything that accepts a webhook can consume alerts.',
            'field' => 'Webhook URL',
            'hint' => 'We POST a JSON payload to this URL.',
            'placeholder' => 'https://hooks.example.com/uptime',
        ],
        'slack' => [
            'name' => 'Slack',
            'description' => 'Posts a message to a Slack channel on failure and recovery.',
            'field' => 'Webhook URL',
            'hint' => 'Paste a Slack incoming webhook URL.',
            'placeholder' => 'https://hooks.slack.com/services/...',
        ],
        'discord' => [
            'name' => 'Discord',
            'description' => 'Posts an embed to a Discord channel on failure and recovery.',
            'field' => 'Webhook URL',
            'hint' => 'Paste a Discord webhook URL.',
            'placeholder' => 'https://discord.com/api/webhooks/...',
        ],
        'pagerduty' => [
            'name' => 'PagerDuty',
            'description' => 'Opens an incident when a monitor fails and resolves it on recovery.',
            'field' => 'Integration key',
            // vue-i18n reads a bare "@" as linked-message syntax.
            'hint' => 'Events API v2 integration key from your PagerDuty service.',
            'placeholder' => '32-character integration key',
        ],
        'opsgenie' => [
            'name' => 'Opsgenie',
            'description' => 'Raises an alert on failure and closes it when the monitor recovers.',
            'field' => 'API key',
            'hint' => 'An API key from an Opsgenie API integration.',
            'placeholder' => 'Opsgenie API key',
        ],
        'teams' => [
            'name' => 'Microsoft Teams',
            'description' => 'Posts an alert card to a channel when a monitor goes down or recovers.',
            'field' => 'Webhook URL',
            'hint' => 'An incoming webhook URL for the Teams channel.',
            'placeholder' => 'https://outlook.office.com/webhook/...',
        ],
    ],
    'form' => [
        'connect' => 'Connect',
        'create' => 'Add integration',
        'edit' => 'Edit integration',
        'submit' => 'Save integration',
        'name' => [
            'title' => 'Name',
            'placeholder' => 'Primary on-call',
        ],
        'type' => [
            'title' => 'Type',
        ],
        'is_active' => [
            'title' => 'Active',
            'description' => 'Inactive integrations are skipped when alerting.',
        ],
        'tabs' => [
            'setup' => 'Setup',
            'scope' => 'Alerts on',
            'message' => 'Message',
        ],
        'scope' => [
            'title' => 'Alerts on',
            'description' => 'Which monitors send their alerts here.',
            'all' => 'All monitors',
            'all_hint' => 'Includes monitors you create later.',
            'selected' => 'Selected monitors',
            'selected_hint' => 'Only the monitors you tick.',
            'empty' => 'You have no monitors yet.',
            'select_all' => 'Select all',
            'clear' => 'Clear',
        ],
        'templates' => [
            'description' => 'Leave a field blank to send the default wording below.',
            'down' => 'When a monitor goes down',
            'recovered' => 'When a monitor recovers',
            'placeholders' => 'Placeholders',
            'insert_hint' => 'click one to insert it where you last typed',
            'preview' => 'Preview',
            'presets' => [
                'default' => 'Default',
                'detailed' => 'Detailed',
                'short' => 'Short',
                'clear' => 'Clear',
            ],
            'unsupported' => 'PagerDuty and Opsgenie use the title as the alert summary. A resolve carries no text.',
        ],
    ],
    'actions' => [
        'test' => 'Send test alert',
        'edit' => 'Edit',
        'disconnect' => 'Disconnect',
        'confirm_disconnect' => 'Disconnect this integration? Monitors using it will stop alerting here.',
    ],
    'attached' => ':count monitor|:count monitors',
    'all_monitors' => 'All monitors',
    'test' => [
        'sample_monitor' => 'Example monitor',
        'sample_error' => 'This is a test alert — nothing is actually down.',
    ],
    'validation' => [
        'unknown_placeholder' => 'Unknown placeholder: :placeholders',
    ],
    'messages' => [
        'connected' => ['success' => 'Integration connected'],
        'updated' => ['success' => 'Integration updated'],
        'disconnected' => ['success' => 'Integration disconnected'],
        'tested' => ['success' => 'Test alert sent'],
    ],
];
