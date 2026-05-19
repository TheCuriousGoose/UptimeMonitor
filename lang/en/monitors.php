<?php

return [
    'title' => 'Monitors',
    'table' => [
        'header' => 'Your monitors',
        'columns' => [
            'name' => 'Name',
            'is_up' => 'Is Up',
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
    'is_up' => 'Up',
    'is_down' => 'Down',
    'create' => 'New Monitor',
    'breadcrumbs' => [
        'index' => 'Monitors',
        'create' => 'Create' 
    ]
];