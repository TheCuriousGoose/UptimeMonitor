<?php

/*
 * Copy for the shared error screen. Keys are names rather than status codes
 * so the same wording can be reused if a status is ever remapped, and so the
 * front end never has to build a translation key out of a number.
 */
return [
    'default' => [
        'title' => 'Something went wrong',
        'description' => 'That request could not be completed. Try again, and let us know if it keeps happening.',
    ],

    'forbidden' => [
        'title' => 'Forbidden',
        'description' => 'You do not have permission to view this page. If you think you should, ask an administrator to check your role.',
    ],

    'not_found' => [
        'title' => 'Page not found',
        'description' => 'The page you are looking for does not exist, or it has moved somewhere else.',
    ],

    'expired' => [
        'title' => 'Page expired',
        'description' => 'This page sat idle long enough for its session to expire. Reload it and try again.',
    ],

    'rate_limited' => [
        'title' => 'Too many requests',
        'description' => 'You have made too many requests in a short period. Give it a moment and try again.',
    ],

    'server' => [
        'title' => 'Server error',
        'description' => 'Something broke on our side. The failure has been logged and we are looking into it.',
    ],

    'unavailable' => [
        'title' => 'Under maintenance',
        'description' => 'The dashboard is briefly unavailable while we deploy an update. Your monitors keep running in the background.',
    ],

    'actions' => [
        'back' => 'Go back',
        'home' => 'Go home',
        'dashboard' => 'Go to dashboard',
        'reload' => 'Reload page',
    ],

    'retry_in' => 'You can try again in :seconds seconds.',
];
