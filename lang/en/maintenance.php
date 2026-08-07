<?php

return [
    'title' => 'Maintenance windows',
    'subtitle' => 'Suppress alerts during planned work.',
    'create' => 'New window',

    'empty' => [
        'title' => 'No maintenance windows',
        'description' => 'Schedule one so a deploy or migration does not page anybody.',
    ],

    'schedule' => [
        'title' => 'Repeats',
        'description' => 'Pick a pattern, or write the cron expression directly.',
        'time' => 'Starting at',
        'weekdays' => 'On these days',
        'weekdays_required' => 'Pick at least one day.',
        'day_of_month' => 'On day',
        'day_of_month_description' => 'Months without this day are skipped.',
        'cron' => 'Cron expression',
        'cron_description' => 'Five fields: minute, hour, day of month, month, day of week.',
        'cron_invalid' => 'A cron expression has five fields, such as "0 2 * * 0".',

        'modes' => [
            'once' => 'Does not repeat',
            'daily' => 'Every day',
            'weekly' => 'Every week',
            'monthly' => 'Every month',
            'custom' => 'Custom schedule (cron)',
        ],

        'summary' => [
            'daily' => 'Every day at :time',
            'weekly' => 'Every :days at :time',
            'monthly' => 'Day :day of every month at :time',
        ],
    ],

    'form' => [
        'create_title' => 'New maintenance window',
        'edit_title' => 'Edit maintenance window',
        'name' => 'Name',
        'name_placeholder' => 'Sunday deploy',
        'timezone' => 'Timezone',
        'starts_at' => 'Starts',
        'ends_at' => 'Ends',
        'monitors' => 'Silence these monitors',
        'search_monitors' => 'Search monitors...',
        'select_all' => 'Select all',
        'no_monitors' => 'No monitors yet. Add one first and it will show up here.',
        'no_matches' => 'No monitors match that search.',
        'is_active' => 'Active',

        'duration' => [
            'title' => 'For how long',
            'custom' => 'Custom',
            'custom_placeholder' => 'Minutes, e.g. 90',
            'options' => [
                '15' => '15 minutes',
                '30' => '30 minutes',
                '60' => '1 hour',
                '120' => '2 hours',
                '240' => '4 hours',
                '480' => '8 hours',
                '1440' => '24 hours',
            ],
        ],
    ],

    'confirm_delete' => 'Delete ":name"? The monitors it suppresses will alert normally again.',
    'active_now' => 'In progress',
    'next_occurrence' => 'Next: :time',
    'paused' => 'Paused',
    'window' => ':start to :end',
    'duration' => 'for :duration',
    // Zero is worth calling out rather than rendering as "0 monitors": a
    // window with nothing attached silences nothing, which is rarely intended.
    'silences' => '{0} Silences nothing yet|{1} Silences 1 monitor|[2,*] Silences :count monitors',

    'messages' => [
        'created' => 'Maintenance window created',
        'updated' => 'Maintenance window updated',
        'deleted' => 'Maintenance window deleted',
    ],
];
