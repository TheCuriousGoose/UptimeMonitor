<?php

return [
    'heading' => 'API keys',
    'subtitle' => 'Manage keys for programmatic access to the API.',
    'empty' => [
        'title' => 'No API keys yet',
        'description' => 'Create a key to authenticate requests to the API.',
    ],
    'reveal' => [
        'created' => ':name created',
        'copy_now' => 'Copy this key now — it will not be shown again.',
    ],
    'form' => [
        'create' => 'New key',
        'submit' => 'Create key',
        'name' => [
            'title' => 'Name',
            'placeholder' => 'CI pipeline',
        ],
        'abilities' => [
            'title' => 'Abilities',
        ],
        'expires' => [
            'title' => 'Expires',
            'never' => 'Never',
            'in_30_days' => 'In 30 days',
            'in_90_days' => 'In 90 days',
            'in_1_year' => 'In 1 year',
        ],
    ],
    // Keyed by the raw App\Enums\ApiAbility value, same pattern as
    // monitors.form.type.options — the enum ships values, this ships labels.
    'abilities' => [
        'monitors:read' => 'View monitors',
        'monitors:write' => 'Create, edit, and delete monitors',
        'incidents:read' => 'View incidents',
        'checks:trigger' => 'Trigger a check now',
    ],
    'actions' => [
        'revoke' => 'Revoke',
        'confirm_revoke' => [
            'title' => 'Revoke this key?',
            'description' => 'Anything using it will stop working immediately. This cannot be undone.',
        ],
    ],
    'status' => [
        'last_used' => 'Last used :time',
        'never_used' => 'Never used',
        'expires' => 'Expires :date',
        'never_expires' => 'Never expires',
    ],
    'messages' => [
        'created' => ['success' => 'API key created'],
        'revoked' => ['success' => 'API key revoked'],
    ],
];
