<?php

return [
    'title' => 'Status pages',
    'heading' => 'Status pages',
    'subtitle' => 'Public pages that show your uptime without exposing any URLs or errors.',
    'empty' => [
        'title' => 'No status pages yet',
        'description' => 'Publish a page so your users can check availability themselves.',
    ],
    'form' => [
        'create' => 'New status page',
        'edit' => 'Edit status page',
        'submit' => 'Save status page',
        'title_field' => [
            'title' => 'Title',
            'placeholder' => 'Acme Status',
        ],
        'slug' => [
            'title' => 'Address',
            'description' => 'Lowercase letters, numbers and dashes.',
            'placeholder' => 'acme',
        ],
        'description' => [
            'title' => 'Description',
            'placeholder' => 'Live availability for our public services.',
        ],
        'is_published' => [
            'title' => 'Published',
            'description' => 'Unpublished pages return a 404 to visitors.',
        ],
        'monitors' => [
            'title' => 'Monitors to show',
            'description' => 'Only uptime is shown publicly — never the target or error details.',
            'empty' => 'Create a monitor first, then add it to this page.',
        ],
    ],
    'actions' => [
        'edit' => 'Edit',
        'delete' => 'Delete',
        'visit' => 'Open page',
        'copy' => 'Copy link',
        'confirm_delete' => 'Delete this status page? The public link will stop working.',
    ],
    'monitors_count' => ':count monitor|:count monitors',
    'validation' => [
        'slug' => 'The address may only contain lowercase letters, numbers and dashes.',
    ],
    'public' => [
        'all_operational' => 'All systems operational',
        'degraded' => 'Some systems are down',
        'pending' => 'Awaiting first checks',
        'uptime_90d' => '90-day uptime',
        'updated' => 'Updated :time',
        'no_monitors' => 'Nothing is being reported on this page yet.',
        'legend_up' => 'Operational',
        'legend_down' => 'Outage',
        'legend_empty' => 'No data',
    ],
    'messages' => [
        'created' => ['success' => 'Status page created'],
        'updated' => ['success' => 'Status page updated'],
        'deleted' => ['success' => 'Status page deleted'],
    ],
];
