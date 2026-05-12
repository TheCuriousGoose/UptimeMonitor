<?php

return [
    'title' => 'monitors',
    'table' => [
        'header' => 'Your monitors',
        'columns' => [
            'name' => 'Name',
            'url' => 'URL',
            'interval' => 'Interval',
            'timeout' => 'Timeout',
            'actions' => 'Actions'
        ],
        'filters' => [
            'search' => [
                'placeholder' => 'Search monitors...'
            ]
        ],
        'empty' => 'No monitors found'
    ],
    'create' => 'New Monitor'
];