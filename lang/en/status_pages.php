<?php

return [
    'title' => 'Status pages',
    'heading' => 'Status pages',
    'subtitle' => 'Public pages that show your uptime without exposing any URLs or errors.',
    'empty' => [
        'title' => 'No status pages yet',
        'description' => 'Publish a page so your users can check availability themselves.',
    ],
    'form' => [
        'create' => 'New status page',
        'edit' => 'Edit status page',
        'submit' => 'Save status page',
        'title_field' => [
            'title' => 'Title',
            'placeholder' => 'Acme Status',
        ],
        'slug' => [
            'title' => 'Address',
            'description' => 'Lowercase letters, numbers and dashes.',
            'placeholder' => 'acme',
        ],
        'description' => [
            'title' => 'Description',
            'placeholder' => 'Live availability for our public services.',
        ],
        'is_published' => [
            'title' => 'Published',
            'description' => 'Unpublished pages return a 404 to visitors.',
        ],
        'monitors' => [
            'title' => 'Monitors to show',
            'description' => 'Only uptime is shown publicly — never the target or error details.',
            'empty' => 'Create a monitor first, then add it to this page.',
        ],
        'tabs' => [
            'general' => 'General',
            'branding' => 'Branding',
            'layout' => 'Layout',
        ],
    ],
    'theme' => [
        'preview' => 'Preview',
        'preview_system' => 'Shown in your own scheme — visitors see theirs.',
        'reset' => 'Reset to defaults',
        'sample' => [
            'title' => 'Acme Status',
            'api' => 'API',
            'website' => 'Website',
        ],
        'mode' => [
            'title' => 'Colour scheme',
            'description' => 'Visitors see the scheme you pick here, not their own app preference.',
            'light' => 'Light',
            'dark' => 'Dark',
            'system' => 'Match visitor',
        ],
        'brand_color' => [
            'title' => 'Brand colour',
            'description' => 'Used for links and accents.',
        ],
        'background' => [
            'title' => 'Background',
            'description' => 'Leave empty to follow the colour scheme. A custom background applies to both schemes.',
        ],
        'foreground' => [
            'title' => 'Text',
            'description' => 'Leave empty to pick automatically for contrast against the background.',
        ],
        'status_colors' => [
            'title' => 'Status colours',
            'description' => 'Every status also carries an icon and a label, so these never have to be told apart by colour alone.',
            'up' => 'Operational',
            'down' => 'Outage',
            'warning' => 'Degraded',
        ],
        'logo_url' => [
            'title' => 'Logo URL',
            'description' => 'Shown above the title, scaled to 40px tall.',
            'placeholder' => 'https://acme.com/logo.svg',
        ],
        'favicon_url' => [
            'title' => 'Favicon URL',
            'placeholder' => 'https://acme.com/favicon.ico',
        ],
        'font_family' => [
            'title' => 'Font stack',
            'description' => 'Any CSS font stack. Falls through to the next family if a visitor does not have the first.',
            'placeholder' => "'Acme Grotesk', Helvetica, sans-serif",
        ],
        'font_url' => [
            'title' => 'Webfont file',
            'description' => 'Optional .woff2, .woff, .ttf or .otf to load for the first family above. Stylesheet URLs are not accepted.',
            'placeholder' => 'https://acme.com/fonts/acme-grotesk.woff2',
        ],
        'radius' => [
            'title' => 'Corner rounding',
            'description' => 'Anything from square to fully rounded panels.',
        ],
        'width' => [
            'title' => 'Content width',
            'description' => 'How wide the page column runs.',
        ],
        'footer_text' => [
            'title' => 'Footer note',
            'description' => 'A line of your own under the page — a company name, a support address.',
            'placeholder' => '© Acme B.V.',
        ],
        'links' => [
            'title' => 'Links',
            'description' => 'Up to :count links back to your own site, shown under the title.',
            'label' => 'Label',
            'url' => 'URL',
            'add' => 'Add link',
            'remove' => 'Remove link',
        ],
    ],
    'actions' => [
        'edit' => 'Edit',
        'delete' => 'Delete',
        'visit' => 'Open page',
        'copy' => 'Copy link',
        'confirm_delete' => 'Delete this status page? The public link will stop working.',
    ],
    'monitors_count' => ':count monitor|:count monitors',
    'validation' => [
        'slug' => 'The address may only contain lowercase letters, numbers and dashes.',
        'color' => 'Enter a hex colour, such as #4f46e5.',
        'font_url' => 'Link directly to a font file (:formats), not to a stylesheet.',
    ],
    'public' => [
        'all_operational' => 'All systems operational',
        'degraded' => 'Some systems are down',
        'pending' => 'Awaiting first checks',
        'uptime_90d' => '90-day uptime',
        'updated' => 'Updated :time',
        'no_monitors' => 'Nothing is being reported on this page yet.',
        'legend_up' => 'Operational',
        'legend_down' => 'Outage',
        'legend_empty' => 'No data',
    ],
    'messages' => [
        'created' => ['success' => 'Status page created'],
        'updated' => ['success' => 'Status page updated'],
        'deleted' => ['success' => 'Status page deleted'],
    ],
];
