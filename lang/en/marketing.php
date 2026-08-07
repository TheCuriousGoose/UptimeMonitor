<?php

return [
    'nav' => [
        'menu' => 'Menu',
        'features' => 'Features',
        'docs' => 'Documentation',
        'blog' => 'Blog',
        'changelog' => 'Changelog',
        'roadmap' => 'Roadmap',
        'about' => 'About',
        'contact' => 'Contact',
        'privacy' => 'Privacy',
        'terms' => 'Terms',
        'login' => 'Log in',
        'register' => 'Get started',
        'dashboard' => 'Dashboard',
    ],
    'footer' => [
        'tagline' => 'Uptime monitoring that tells you what broke, when, and for how long.',
        'product' => 'Product',
        'resources' => 'Resources',
        'company' => 'Company',
    ],
    'support' => [
        'title' => 'Support the project',
        'description' => 'Checks run around the clock and the servers behind them are not free. If this saved you an outage, it helps.',
        'action' => 'Buy me a coffee',
        'nav' => 'Support the project',
    ],
    'home' => [
        'eyebrow' => 'Uptime monitoring',
        'title' => 'Know before your users do.',
        'subtitle' => 'Vigil Watch checks your sites, APIs, ports and certificates around the clock, and tells the right person the moment something breaks.',
        'cta_primary' => 'Start monitoring',
        'cta_secondary' => 'Read the docs',
        'cta_note' => 'Self-hosted and open source. No agent to install, no card to enter.',

        'preview' => [
            'label' => 'Monitors',
            'columns' => [
                'monitor' => 'Monitor',
                'latency' => 'Latency',
                'uptime' => 'Uptime 90d',
            ],
            'rows' => [
                'api' => ['name' => 'api.example.com', 'type' => 'HTTP', 'latency' => '142 ms', 'uptime' => '99.98%'],
                'web' => ['name' => 'example.com', 'type' => 'Keyword', 'latency' => '318 ms', 'uptime' => '99.95%'],
                'db' => ['name' => 'db.internal:5432', 'type' => 'Port', 'latency' => 'timeout', 'uptime' => '99.41%'],
                'cert' => ['name' => 'example.com', 'type' => 'TLS', 'latency' => '41 d left', 'uptime' => '100%'],
            ],
            'status' => [
                'up' => 'Up',
                'down' => 'Down',
            ],
            'incident' => 'Incident opened 4m ago. Connection refused after 3 consecutive failures. Paged on-call via PagerDuty.',
        ],

        'stats' => [
            'interval' => ['value' => '30s', 'label' => 'Minimum check interval'],
            'types' => ['value' => '6', 'label' => 'Check types'],
            'channels' => ['value' => '7', 'label' => 'Alert destinations'],
            'retention' => ['value' => '90d', 'label' => 'History retained'],
        ],

        'how' => [
            'eyebrow' => 'How it works',
            'heading' => 'Three steps, then it stays out of your way',
            'lead' => 'There is no agent, no sidecar and no DNS change. Vigil Watch calls your endpoint the same way a user would, from wherever you run it.',
            'steps' => [
                'add' => [
                    'title' => 'Point it at a target',
                    'body' => 'Paste a URL, a hostname or a host and port. Pick the check type and an interval between 30 seconds and a day.',
                ],
                'tune' => [
                    'title' => 'Set the failure threshold',
                    'body' => 'Choose how many consecutive failed checks count as down, and how long a single check may take before it is treated as a timeout.',
                ],
                'route' => [
                    'title' => 'Route the alert',
                    'body' => 'Attach channels to the monitor. When an incident opens, every attached channel fires; when it recovers, they are notified again.',
                ],
            ],
        ],

        'features_heading' => 'Built for the moment it breaks',
        'features_lead' => 'The interesting part of monitoring is not the green checkmark. It is what happens in the ninety seconds after something stops responding.',
        'features' => [
            'checks' => [
                'title' => 'Six kinds of check',
                'body' => 'HTTP, keyword, TCP port, ping, DNS record and TLS certificate expiry. Each with its own timeout and confirmation threshold.',
            ],
            'confirmation' => [
                'title' => 'No alerts for blips',
                'body' => 'A monitor only flips to down after it fails the number of consecutive checks you choose, so a single dropped packet never pages anyone.',
            ],
            'incidents' => [
                'title' => 'Incidents, not just pings',
                'body' => 'Failures group into incidents with a start, a cause and a duration, backdated to the first failure rather than the one that crossed the threshold.',
            ],
            'alerts' => [
                'title' => 'Alerts where you work',
                'body' => 'Email, webhooks, Slack, Discord and Google Chat, plus PagerDuty, Opsgenie and Teams, which resolve the alert automatically on recovery.',
            ],
            'status' => [
                'title' => 'Status pages',
                'body' => 'Publish a public page with 90 days of daily uptime so customers can check for themselves.',
            ],
            'api' => [
                'title' => 'A real API',
                'body' => 'Scoped API keys and a versioned REST API, so monitors can be managed from CI instead of a browser.',
            ],
        ],

        'types' => [
            'eyebrow' => 'Check types',
            'heading' => 'Six ways to ask "is it actually working?"',
            'lead' => 'A 200 response is not the same as a working page, and a working page is not the same as a certificate that will still be valid next month. Each type answers a different question.',
            'columns' => [
                'type' => 'Type',
                'target' => 'Target',
                'catches' => 'What it catches',
            ],
            'items' => [
                'http' => [
                    'name' => 'HTTP',
                    'target' => 'URL',
                    'catches' => 'Non-2xx responses, timeouts and TLS failures. Set the method, the exact status code you expect, and whether to verify the certificate.',
                ],
                'keyword' => [
                    'name' => 'Keyword',
                    'target' => 'URL',
                    'catches' => 'Pages that return 200 while rendering an error. Fails when a string is missing from the body, or present if you invert the match.',
                ],
                'port' => [
                    'name' => 'Port',
                    'target' => 'Host and port',
                    'catches' => 'Databases, queues, mail and SSH that stop accepting connections, without exposing them to HTTP.',
                ],
                'ping' => [
                    'name' => 'Ping',
                    'target' => 'Host',
                    'catches' => 'Hosts that have gone off the network entirely. The cheapest way to separate "the box is gone" from "the app is broken".',
                ],
                'dns' => [
                    'name' => 'DNS',
                    'target' => 'Hostname',
                    'catches' => 'Records that vanish or quietly change. Checks A, AAAA, CNAME, MX, TXT or NS, and can assert the value you expect.',
                ],
                'ssl' => [
                    'name' => 'TLS',
                    'target' => 'URL',
                    'catches' => 'Certificates about to expire. Warns a configurable number of days ahead, so renewal is a task rather than an outage.',
                ],
            ],
        ],

        'alerts' => [
            'eyebrow' => 'Alerting',
            'heading' => 'The right person, on the channel they already watch',
            'lead' => 'Channels are attached per monitor, so a marketing site and a payments API do not have to wake the same people. Every channel is tested from the UI before you rely on it.',
            'channels' => [
                'email' => ['name' => 'Email', 'detail' => 'Plain, threaded per incident'],
                'slack' => ['name' => 'Slack', 'detail' => 'Incoming webhook, colour-coded'],
                'discord' => ['name' => 'Discord', 'detail' => 'Webhook with embed'],
                'teams' => ['name' => 'Microsoft Teams', 'detail' => 'Auto-resolves on recovery'],
                'pagerduty' => ['name' => 'PagerDuty', 'detail' => 'Events API v2, deduplicated'],
                'opsgenie' => ['name' => 'Opsgenie', 'detail' => 'Closes the alert on recovery'],
                'webhook' => ['name' => 'Webhook', 'detail' => 'JSON POST to any endpoint'],
            ],
            'points' => [
                'dedupe' => [
                    'title' => 'One incident, one page',
                    'body' => 'Repeated failures inside an open incident do not re-alert. PagerDuty and Opsgenie receive a stable deduplication key, so the incident updates in place rather than stacking.',
                ],
                'resolve' => [
                    'title' => 'Recovery closes the loop',
                    'body' => 'When the monitor passes again, the incident closes with its total duration and the same channels are notified, including the on-call tools, which resolve the alert on their side.',
                ],
            ],
        ],

        'transparency' => [
            'eyebrow' => 'Status pages and API',
            'heading' => 'Everything it knows, available to everyone who needs it',
            'status' => [
                'title' => 'Public status pages',
                'body' => 'Group monitors onto a page at your own slug, publish 90 days of daily uptime, and let customers answer "is it just me?" without opening a ticket.',
                'points' => [
                    'A 90-day uptime bar per monitor, with incident detail on hover',
                    'Choose which monitors appear. Internal checks stay private',
                    'Light, dark or visitor preference, fixed by you rather than inherited',
                    'No login, no app chrome, no JavaScript required to read it',
                ],
            ],
            'api' => [
                'title' => 'Versioned REST API',
                'body' => 'Create monitors from Terraform, pause them during a deploy, or pull incident history into your own reporting. Keys are scoped, and a key can never do more than the person who created it.',
                // The request body and the scope names themselves are literal
                // API syntax, not copy: they live in the component so they are
                // never translated, and never reach the message compiler —
                // which reads `{` as a placeholder and `:read` as one too.
                'sample_label' => 'Create a monitor from CI',
                'abilities_label' => 'Scopes',
            ],
        ],

        'open' => [
            'eyebrow' => 'Open source',
            'heading' => 'Self-hosted, and priced accordingly',
            'body' => 'Vigil Watch runs on your own infrastructure. No billing, no seat count, no feature held back behind a plan. Run as many monitors as your server can handle.',
            'points' => [
                'unlimited' => ['title' => 'Unlimited monitors and status pages', 'body' => 'The only ceiling is your hardware.'],
                'data' => ['title' => 'Your data stays yours', 'body' => 'Checks, incidents and contacts live in your database. Nothing is sent anywhere else.'],
                'source' => ['title' => 'Readable source', 'body' => 'Laravel, Vue and a queue. Fork it, audit it, or run it exactly as shipped.'],
            ],
            'cta' => 'View the source',
        ],

        'faq' => [
            'eyebrow' => 'Questions',
            'heading' => 'Before you install it',
            'items' => [
                'agent' => [
                    'question' => 'Does anything need to be installed on the servers I monitor?',
                    'answer' => 'No. Checks are made from the outside over the same protocols a user would use, so a monitored host needs no agent, no package and no inbound rule beyond what it already serves.',
                ],
                'noise' => [
                    'question' => 'How do I stop it paging me over a single blip?',
                    'answer' => 'Every monitor has a confirmation threshold. A failure only opens an incident after that many consecutive checks fail, and the incident is then backdated to the first failure so the recorded duration is still honest.',
                ],
                'scale' => [
                    'question' => 'How many monitors can one instance handle?',
                    'answer' => 'Checks run on a queue, so throughput is a function of your worker count rather than a licence. A single modest server comfortably handles hundreds of monitors at a one-minute interval.',
                ],
                'history' => [
                    'question' => 'How long is history kept?',
                    'answer' => 'Individual check results are retained for 90 days, which is what the uptime bars and status pages read from. Incidents are kept indefinitely.',
                ],
                'existing' => [
                    'question' => 'Can I manage it without the UI?',
                    'answer' => 'Yes. The REST API covers creating, updating, pausing and deleting monitors, triggering a check on demand, and reading incident history, all under scoped keys.',
                ],
                'cost' => [
                    'question' => 'What does it cost?',
                    'answer' => 'Nothing beyond the server you run it on. It is open source and self-hosted, with no paid tier holding features back.',
                ],
            ],
        ],

        'closing' => [
            'title' => 'Point it at something and see.',
            'body' => 'Create a monitor in under a minute. No agent to install.',
        ],
    ],

    'features' => [
        'title' => 'Features',
        'subtitle' => 'What Vigil Watch does, in detail.',
        'spec_label' => 'Specifics',
        'sections' => [
            'checks' => [
                'title' => 'Checks',
                'body' => 'Six check types, each with its own configuration, timeout and schedule. A monitor is a target plus a question. The type decides which question gets asked.',
                'points' => [
                    'HTTP with a chosen method, expected status code and optional certificate verification',
                    'Keyword matching on the response body, invertible so a string can be required to be absent',
                    'TCP port connectivity for databases, brokers, mail and SSH',
                    'ICMP ping for hosts that serve nothing over HTTP',
                    'DNS lookups for A, AAAA, CNAME, MX, TXT and NS records, with an optional expected value',
                    'TLS certificate expiry with a configurable warning window',
                ],
            ],
            'confirmation' => [
                'title' => 'Confirmation and timeouts',
                'body' => 'Transient failures are the normal state of the internet. A monitor is only considered down once it has failed the number of consecutive checks you set, which is what keeps a dropped packet from becoming a phone call.',
                'points' => [
                    'Per-monitor confirmation threshold, from one failure up to ten',
                    'Per-monitor request timeout, separate from the check interval',
                    'Intervals from 30 seconds to a day, set per monitor rather than per account',
                    'Monitors can be paused, so a planned deploy does not generate an incident',
                    'A check can be triggered manually from the UI or the API without waiting for the schedule',
                ],
            ],
            'incidents' => [
                'title' => 'Incidents',
                'body' => 'A run of failures becomes one incident with a beginning, a cause and an end. The record is what you read afterwards, so it is written to be read: no scrolling through a log of identical failed pings to work out when something started.',
                'points' => [
                    'Opened at the first failure, not the one that crossed the threshold, so duration is accurate',
                    'Carries the error that caused it: status code, timeout, connection refused, missing keyword',
                    'Closed automatically on recovery, with total downtime recorded',
                    'Full check history behind each incident, at the resolution it was captured',
                    'Filterable by monitor and state, and readable over the API',
                ],
            ],
            'alerts' => [
                'title' => 'Alerting',
                'body' => 'Channels are configured once and attached to whichever monitors should use them, so severity is expressed by routing rather than by a priority field nobody sets.',
                'points' => [
                    'Email, Slack, Discord, Microsoft Teams, PagerDuty, Opsgenie and outbound webhooks',
                    'PagerDuty and Opsgenie receive a stable deduplication key and resolve automatically on recovery',
                    'Webhooks deliver structured JSON describing the monitor, the incident and the triggering check',
                    'Channels can be tested before you depend on them',
                    'Credentials such as routing keys are masked in the UI and never returned in full to the browser',
                ],
            ],
            'status' => [
                'title' => 'Status pages',
                'body' => 'A public page per audience, with only the monitors you choose to show. Customers get an answer without opening a ticket, and you get a link to paste when they ask anyway.',
                'points' => [
                    'Custom slug, title and description per page',
                    '90 days of daily uptime, per monitor, with incident detail',
                    'Light, dark or visitor-preference theme, chosen by the page owner',
                    'Monitors are opt-in per page, so internal checks stay internal',
                    'Rendered server-side, with no login and no app chrome',
                ],
            ],
            'api' => [
                'title' => 'API and access control',
                'body' => 'Everything the UI does to a monitor, the API can do too. Access is scoped twice over: by the abilities on the key, and by the permissions of the person who created it.',
                'points' => [
                    'Versioned under /api/v1, authenticated with bearer tokens',
                    'Scopes for reading monitors, writing monitors, reading incidents and triggering checks',
                    'A key can only ever narrow its owner\'s permissions, never widen them',
                    'Rate limited, with a tighter limit on on-demand checks',
                    'Role-based permissions in the app itself, with an audited impersonation path for support',
                ],
            ],
        ],
    ],

    'about' => [
        'title' => 'About',
        'subtitle' => 'Why this exists.',
        'body' => "Most uptime monitoring is either a black box that emails you \"site down\" with no detail, or an enterprise platform priced for a team of fifty.\n\nVigil Watch is the middle: it records every check, groups failures into incidents you can actually read, and routes alerts to whoever is on call, while staying something one person can run on a single server.",
        'principles_heading' => 'How it is built',
        'principles' => [
            'honest' => [
                'title' => 'The record should be honest',
                'body' => 'An incident is backdated to the first failed check, not the one that tripped the threshold. Reporting that flatters the tool is worse than no reporting.',
            ],
            'quiet' => [
                'title' => 'Quiet by default',
                'body' => 'A tool that pages you for a dropped packet gets muted, and a muted monitor is worse than none. Confirmation thresholds exist so the alerts that do arrive are worth reading.',
            ],
            'boring' => [
                'title' => 'Boring technology',
                'body' => 'Laravel, Vue, a relational database and a queue. Nothing exotic to operate, and nothing that needs a specialist to keep running.',
            ],
            'yours' => [
                'title' => 'Your data, your server',
                'body' => 'Self-hosted from the start. Check results, incidents and alert credentials never leave the instance you run.',
            ],
        ],
        'stack_heading' => 'Under the hood',
        'stack' => [
            'Laravel',
            'Vue 3 and Inertia',
            'Queued checks',
            'Sanctum-scoped API',
            'MySQL or PostgreSQL',
        ],
        'cta_heading' => 'See it for yourself',
        'cta_body' => 'The fastest way to judge a monitoring tool is to point it at something and wait for it to break.',
    ],

    'contact' => [
        'title' => 'Contact',
        'subtitle' => 'Questions, bugs and feature requests.',
        'body' => 'Vigil Watch is developed in the open. The fastest way to reach us is to open an issue on the repository. Bug reports, feature requests and questions are all welcome there.',
        'issues' => 'Report an issue',
        'security_heading' => 'Security',
        'security_body' => 'If you have found a security issue, please report it privately rather than opening a public issue.',
        'routes_heading' => 'Where to send what',
        'routes' => [
            'bug' => [
                'title' => 'Something is broken',
                'body' => 'Open an issue with the check type, the monitor configuration and what you expected instead. Logs from the queue worker help more than a screenshot.',
            ],
            'feature' => [
                'title' => 'Something is missing',
                'body' => 'Check the roadmap first, it may already be planned. If not, describe the situation you are trying to handle rather than the feature you have in mind.',
            ],
            'docs' => [
                'title' => 'The docs are wrong',
                'body' => 'Documentation lives alongside the code. Corrections are welcome as issues or as pull requests.',
            ],
            'security' => [
                'title' => 'You found a vulnerability',
                'body' => 'Report it privately rather than in a public issue, and allow time for a fix before disclosure.',
            ],
        ],
    ],

    'roadmap' => [
        'title' => 'Roadmap',
        'subtitle' => 'What is being worked on, and what is next.',
        'note' => 'Planned work is ordered by how often it is asked for, not by how interesting it is to build. Dates are deliberately absent: this is a list of intent, not a delivery schedule.',
        'status' => [
            'shipped' => 'Shipped',
            'building' => 'In progress',
            'planned' => 'Planned',
        ],
        'groups' => [
            'shipped' => 'Already in the product',
            'building' => 'Being built now',
            'planned' => 'Next up',
        ],
        'items' => [
            'monitors' => [
                'title' => 'Six monitor types',
                'body' => 'HTTP, keyword, port, ping, DNS and TLS expiry, each with its own configuration and timeout.',
            ],
            'incidents' => [
                'title' => 'Incident timeline',
                'body' => 'Failures grouped into incidents with a cause, a duration and the check history behind them.',
            ],
            'status_pages' => [
                'title' => 'Public status pages',
                'body' => 'Per-audience pages with 90 days of daily uptime and a theme fixed by the page owner.',
            ],
            'api' => [
                'title' => 'Scoped REST API',
                'body' => 'Versioned endpoints for monitors, checks and incidents, behind ability-scoped keys.',
            ],
            'integrations' => [
                'title' => 'PagerDuty, Opsgenie and Teams',
                'body' => 'On-call routing with deduplication keys and automatic resolution on recovery.',
            ],
            'maintenance' => [
                'title' => 'Maintenance windows',
                'body' => 'Scheduled periods where checks still run but incidents and alerts are suppressed, and uptime is reported both with and without them.',
            ],
            'regions' => [
                'title' => 'Multi-region checks',
                'body' => 'Run the same monitor from more than one location and require agreement before an incident opens, so a single bad network path is not an outage.',
            ],
            'sla' => [
                'title' => 'SLA reporting',
                'body' => 'Uptime measured against a target over a billing period, exportable for the people who ask about it once a month.',
            ],
        ],
    ],

    'legal' => [
        'updated' => 'Last updated :date',
    ],
];
