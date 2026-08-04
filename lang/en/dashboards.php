<?php

return [
    'title' => 'Dashboard',
    'subtitle' => 'Health of everything you are watching, over the last 24 hours.',
    'cards' => [
        'up' => 'Up',
        'down' => 'Down',
        'paused' => 'Paused',
        'pending' => 'Awaiting first check',
        'uptime' => 'Uptime (24h)',
        'response' => 'Avg response (24h)',
        'incidents' => 'Ongoing incidents',
        'total' => 'Monitors',
    ],
    'attention' => [
        'title' => 'Needs attention',
        'empty' => 'Everything is up. Nothing needs your attention.',
        'down_since' => 'Down since :time',
    ],
    'incidents' => [
        'title' => 'Recent incidents',
        'empty' => 'No incidents recorded yet.',
        'ongoing' => 'Ongoing',
        'resolved' => 'Resolved',
    ],
    'empty' => [
        'title' => 'Add your first monitor',
        'description' => 'Point us at a URL, host or port and we will tell you the moment it stops responding.',
        'action' => 'Create a monitor',
    ],
];
