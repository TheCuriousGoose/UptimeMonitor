<?php

return [
    'nav' => [
        'features' => 'Features',
        'pricing' => 'Pricing',
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
    ],
    'footer' => [
        'tagline' => 'Uptime monitoring that tells you what broke, when, and for how long.',
        'product' => 'Product',
        'resources' => 'Resources',
        'company' => 'Company',
    ],
    'home' => [
        'eyebrow' => 'Uptime monitoring',
        'title' => 'Know before your users do.',
        'subtitle' => 'Vigil Watch checks your sites, APIs, ports and certificates around the clock, and tells the right person the moment something breaks.',
        'cta_primary' => 'Start monitoring',
        'cta_secondary' => 'Read the docs',
        'stats' => [
            'interval' => ['value' => '30s', 'label' => 'Minimum check interval'],
            'types' => ['value' => '6', 'label' => 'Check types'],
            'channels' => ['value' => '7', 'label' => 'Alert destinations'],
            'retention' => ['value' => '90d', 'label' => 'History retained'],
        ],
        'features_heading' => 'Built for the moment it breaks',
        'features' => [
            'checks' => [
                'title' => 'Six kinds of check',
                'body' => 'HTTP, keyword, TCP port, ping, DNS record and TLS certificate expiry — each with its own timeout and confirmation threshold.',
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
                'body' => 'Email, webhooks, Slack and Discord — plus PagerDuty, Opsgenie and Teams, which resolve the alert automatically on recovery.',
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
        'closing' => [
            'title' => 'Point it at something and see.',
            'body' => 'Create a monitor in under a minute. No agent to install.',
        ],
    ],
    'features' => [
        'title' => 'Features',
        'subtitle' => 'What Vigil Watch does, in detail.',
    ],
    'pricing' => [
        'title' => 'Pricing',
        'subtitle' => 'Self-hosted and open. Run as many monitors as your server can handle.',
        'plans' => [
            'self_hosted' => [
                'name' => 'Self-hosted',
                'price' => 'Free',
                'period' => 'forever',
                'description' => 'Run it on your own infrastructure.',
                'cta' => 'Get started',
                'features' => [
                    'Unlimited monitors',
                    'Unlimited status pages',
                    '30 second minimum interval',
                    'All alert integrations',
                    'Full REST API',
                    'Community support',
                ],
            ],
        ],
        'note' => 'Vigil Watch is self-hosted. There is no billing, no seat count, and no feature held back behind a plan.',
    ],
    'about' => [
        'title' => 'About',
        'subtitle' => 'Why this exists.',
        'body' => "Most uptime monitoring is either a black box that emails you \"site down\" with no detail, or an enterprise platform priced for a team of fifty.\n\nVigil Watch is the middle: it records every check, groups failures into incidents you can actually read, and routes alerts to whoever is on call — while staying something one person can run on a single server.",
    ],
    'contact' => [
        'title' => 'Contact',
        'subtitle' => 'Questions, bugs and feature requests.',
        'body' => 'Vigil Watch is developed in the open. The fastest way to reach us is to open an issue on the repository — bug reports, feature requests and questions are all welcome there.',
        'issues' => 'Report an issue',
        'security_heading' => 'Security',
        'security_body' => 'If you have found a security issue, please report it privately rather than opening a public issue.',
    ],
    'roadmap' => [
        'title' => 'Roadmap',
        'subtitle' => 'What is being worked on, and what is next.',
        'status' => [
            'shipped' => 'Shipped',
            'building' => 'In progress',
            'planned' => 'Planned',
        ],
        'items' => [
            'monitors' => ['title' => 'Six monitor types', 'status' => 'shipped'],
            'incidents' => ['title' => 'Incident timeline', 'status' => 'shipped'],
            'status_pages' => ['title' => 'Public status pages', 'status' => 'shipped'],
            'api' => ['title' => 'Scoped REST API', 'status' => 'shipped'],
            'integrations' => ['title' => 'PagerDuty, Opsgenie and Teams', 'status' => 'shipped'],
            'maintenance' => ['title' => 'Maintenance windows', 'status' => 'planned'],
            'regions' => ['title' => 'Multi-region checks', 'status' => 'planned'],
            'sla' => ['title' => 'SLA reporting', 'status' => 'planned'],
        ],
    ],
    'legal' => [
        'updated' => 'Last updated :date',
    ],
];
