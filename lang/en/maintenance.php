<?php

return [
    'title' => 'Maintenance windows',
    'subtitle' => 'Silence alerts while you are working on something on purpose.',

    'empty' => [
        'title' => 'No maintenance windows',
        'description' => 'Schedule one to stop planned work paging everyone.',
    ],

    'recurrence' => [
        'once' => 'One off',
        'recurring' => 'Repeating',
    ],

    'form' => [
        'name' => 'Name',
        'timezone' => 'Timezone',
        'starts_at' => 'Starts',
        'ends_at' => 'Ends',
        'cron' => 'Schedule',
        'duration_minutes' => 'Duration in minutes',
        'monitors' => 'Silence these monitors',
        'is_active' => 'Active',
    ],

    'active_now' => 'In progress',
    'next_occurrence' => 'Next: :time',
    'paused' => 'Paused',
    'window' => ':start — :end',
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
