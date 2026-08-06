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
    'actions' => [
        'acknowledge' => 'Acknowledge',
        'unacknowledge' => 'Un-acknowledge',
    ],
    'updates' => [
        'title' => 'Timeline',
        'add' => 'Add an update',
        'body' => 'Update',
        'is_public' => 'Show this on the status page',
        'empty' => 'Nothing has been noted yet.',
        'public' => 'Public',
        'internal' => 'Internal',
        'events' => [
            'started' => 'Went down',
            'acknowledged' => 'Acknowledged by :name',
            'resolved' => 'Recovered',
            'ongoing' => 'Still down',
        ],
        'status' => [
            'investigating' => 'Investigating',
            'identified' => 'Identified',
            'monitoring' => 'Monitoring',
            'resolved' => 'Resolved',
        ],
    ],
    'messages' => [
        'acknowledged' => 'Incident acknowledged',
        'unacknowledged' => 'Acknowledgement removed',
        'update_added' => 'Update added',
        'update_saved' => 'Update saved',
        'update_deleted' => 'Update deleted',
    ],
];
