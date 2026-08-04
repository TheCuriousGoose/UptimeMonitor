<?php

return [
    'title' => 'Incidents',
    'heading' => 'Incidents',
    'subtitle' => 'Every outage we have recorded, newest first.',
    'breadcrumbs' => [
        'index' => 'Incidents',
    ],
    'stats' => [
        'ongoing' => 'Ongoing',
        'last_24h' => 'Last 24 hours',
        'last_7d' => 'Last 7 days',
        'total' => 'All time',
    ],
    'empty' => [
        'title' => 'No incidents',
        'description' => 'Nothing has gone down yet. This page fills up when a monitor fails.',
        'filtered' => 'No incidents match these filters.',
    ],
    'table' => [
        'header' => 'Incidents',
        'empty' => 'No incidents found.',
        'columns' => [
            'monitor' => 'Monitor',
            'status' => 'Status',
            'cause' => 'Cause',
            'started' => 'Started',
            'duration' => 'Duration',
            'failed_checks' => 'Failed checks',
        ],
        'filters' => [
            'search' => [
                'placeholder' => 'Search by monitor...',
            ],
            'status' => [
                'label' => 'Status',
                'all' => 'All statuses',
            ],
        ],
    ],
    'status' => [
        'ongoing' => 'Ongoing',
        'resolved' => 'Resolved',
    ],
];
