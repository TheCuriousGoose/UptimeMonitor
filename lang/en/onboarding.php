<?php

return [
    // The dashboard checklist, for accounts that skipped the guided setup or
    // are part-way through the remaining steps.
    'title' => 'Setup',
    'description' => 'Three steps to a working alert.',
    'progress' => ':done of :total done',
    'dismiss' => 'Hide this',
    'launch' => 'Guided setup',

    'steps' => [
        'monitor' => [
            'title' => 'Add a monitor',
            'description' => 'Give us a URL, host or port and we start checking it on a schedule.',
            'action' => 'Add a monitor',
        ],
        'channel' => [
            'title' => 'Connect an integration',
            'description' => 'Email, Slack, Discord, Google Chat, PagerDuty or a webhook. Without one, alerts go nowhere.',
            'action' => 'Connect an integration',
        ],
        'status_page' => [
            'title' => 'Publish a status page',
            'description' => 'Optional. A public page showing uptime, so users can check without asking you.',
            'action' => 'Create a status page',
        ],
    ],

    // The guided setup itself.
    'setup' => [
        'page_title' => 'Setup',
        'skip' => 'Skip, I know my way around',
        'back' => 'Back',
        'continue' => 'Continue',
        'step_of' => 'Step :current of :total',

        'rail' => [
            'target' => 'Target',
            'test' => 'Test',
            'schedule' => 'Schedule',
            'alerts' => 'Alerts',
        ],

        'welcome' => [
            'title' => 'Set up your first check',
            'description' => 'Four steps. At the end you will have a monitor running on a schedule and an integration to deliver its alerts.',
            'points' => [
                'Pick a check type and a target.',
                'Run it once against the real target to confirm the config works.',
                'Set the interval and the failure thresholds.',
                'Pick where alerts get delivered.',
            ],
            'start' => 'Start',
            'reassure' => 'Nothing is written until the final step. Everything here is editable afterwards.',
        ],

        'target' => [
            'title' => 'What are we checking?',
            'description' => 'Pick the check type, then the address to run it against.',
            'name_label' => 'Name',
            'name_hint' => 'Used in alert titles and throughout the UI.',
            'url_hint' => 'Requested from our servers, not your browser. It must be reachable from the public internet.',
        ],

        'test' => [
            'title' => 'Run it once',
            'description' => 'A single check against the real target, right now, before anything is saved.',
            'running' => 'Checking :target',
            'success_title' => 'Passed',
            'success_body' => 'Responded in :duration. This is the exact check that will run on your schedule.',
            'failure_title' => 'Failed',
            'failure_body' => 'The config is wrong, or the target is genuinely down. Fix the address and retry, or continue and sort it out later.',
            'retry' => 'Retry',
            'edit' => 'Edit target',
            'continue_anyway' => 'Continue anyway',
            'auto_advance' => 'Continuing',
        ],

        'schedule' => [
            'title' => 'How often, and when to alert',
            'description' => 'Each check costs the target a request, so pick the slowest interval you can live with.',
            'recommended' => 'Recommended',
            'interval_hint' => 'Worst-case detection time equals one interval plus the failure threshold below.',
            'confirm_label' => 'Failures before we open an incident',
            'confirm_hint' => 'One failed check is often packet loss or a deploy restarting. Requiring two cuts most false positives and costs you one interval of detection time.',
            'recover_label' => 'Successes before we close it',
            'recover_hint' => 'A flapping service can pass one check and fail the next. Requiring more than one success stops it being marked recovered too early.',
        ],

        'alerts' => [
            'title' => 'Where do alerts go?',
            'description' => 'A monitor with no integration attached detects outages and delivers them nowhere.',
            'email_option' => 'Email :email',
            'email_hint' => 'Fastest option. Slack, Discord, Google Chat, PagerDuty, Opsgenie and webhooks can be added later.',
            'existing' => 'Use an existing integration',
            'none' => 'Nothing for now',
            'none_warning' => 'The monitor will run, but failures will not reach you.',
            'channel_name' => 'My email',
        ],

        'done' => [
            'title' => 'Done. Watching :name',
            'description' => 'The first check is running now. Configuration:',
            'summary' => [
                'target' => 'Target',
                'interval' => 'Interval',
                'alerts' => 'Alerts to',
                'no_alerts' => 'Nothing',
            ],
            'whats_next' => 'The monitor page has every check, response times and incident history. All of this is editable there.',
            'finish' => 'Open the monitor',
            'saving' => 'Saving',
        ],
    ],

    'messages' => [
        'created' => 'Monitor created. First check is running.',
    ],
];
