<?php

return [
    'title' => 'Integrations',
    'heading' => 'Integrations',
    'subtitle' => 'Send alerts to the on-call tools your team already uses.',
    'breadcrumbs' => [
        'index' => 'Integrations',
    ],
    'available' => 'Available',
    'connected' => 'Connected',
    'empty' => [
        'title' => 'Nothing connected yet',
        'description' => 'Connect an on-call tool to route outages to whoever is on duty.',
    ],
    'providers' => [
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
        'edit' => 'Edit integration',
        'submit' => 'Save integration',
        'name' => [
            'title' => 'Name',
            'placeholder' => 'Primary on-call',
        ],
        'is_active' => [
            'title' => 'Active',
            'description' => 'Inactive integrations are skipped when alerting.',
        ],
    ],
    'actions' => [
        'test' => 'Send test alert',
        'edit' => 'Edit',
        'disconnect' => 'Disconnect',
        'confirm_disconnect' => 'Disconnect this integration? Monitors using it will stop alerting here.',
    ],
    'attached' => ':count monitor|:count monitors',
    'messages' => [
        'connected' => ['success' => 'Integration connected'],
        'updated' => ['success' => 'Integration updated'],
        'disconnected' => ['success' => 'Integration disconnected'],
    ],
];
