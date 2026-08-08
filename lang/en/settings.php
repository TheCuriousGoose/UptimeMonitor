<?php

return [
    'title' => 'Settings',
    'table' => [
        'setting' => 'Setting',
        'key' => 'Key',
        'value' => 'Value',
    ],
    'edit' => [
        'title' => 'Edit Setting',
        'secret_placeholder' => 'Leave blank to keep the current value',
    ],
    'value' => [
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
        'not_set' => 'Not set',
    ],
    'oauth' => [
        'incomplete' => 'Add a client ID and client secret before sign-in with this provider will work.',
    ],

    // The account pages, as opposed to the admin settings table above.
    'profile' => [
        'breadcrumb' => 'Profile settings',
        'heading' => 'Profile information',
        'description' => 'Update your name and email address',
        'name_label' => 'Name',
        'name_placeholder' => 'Full name',
        'email_label' => 'Email address',
        'email_placeholder' => 'Email address',
        'unverified' => 'Your email address is unverified.',
        'resend' => 'Click here to resend the verification email.',
        'verification_sent' => 'A new verification link has been sent to your email address.',
        'submit' => 'Save',
    ],

    'security' => [
        'breadcrumb' => 'Security settings',
        'password' => [
            'heading' => 'Update password',
            'description' => 'Ensure your account is using a long, random password to stay secure',
            'current_label' => 'Current password',
            'new_label' => 'New password',
            'confirmation_label' => 'Confirm password',
            'submit' => 'Save password',
        ],
        'two_factor' => [
            'heading' => 'Two-factor authentication',
            'description' => 'Manage your two-factor authentication settings',
            'disabled_hint' => 'When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.',
            'enabled_hint' => 'You will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.',
            'continue_setup' => 'Continue setup',
            'enable' => 'Enable 2FA',
            'disable' => 'Disable 2FA',

            'setup' => [
                'enabled_title' => 'Two-factor authentication enabled',
                'enabled_description' => 'Two-factor authentication is now enabled. Scan the QR code or enter the setup key in your authenticator app.',
                'verify_title' => 'Verify authentication code',
                'verify_description' => 'Enter the 6-digit code from your authenticator app',
                'enable_title' => 'Enable two-factor authentication',
                'enable_description' => 'To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app',
                'close' => 'Close',
                'continue' => 'Continue',
            ],

            'recovery' => [
                'title' => '2FA recovery codes',
                'description' => 'Recovery codes let you regain access if you lose your 2FA device. Store them in a secure password manager.',
                'view' => 'View recovery codes',
                'hide' => 'Hide recovery codes',
                'regenerate' => 'Regenerate codes',
                'note' => 'Each recovery code can be used once to access your account and will be removed after use. If you need more, click "Regenerate codes" above.',
            ],
        ],
    ],

    'appearance' => [
        'breadcrumb' => 'Appearance settings',
        'heading' => 'Appearance settings',
        'description' => 'Update your account\'s appearance settings',
    ],

    'domains' => [
        'breadcrumb' => 'Verified domains',
        'heading' => 'Verified domains',
        'description' => 'Prove you control a domain before this instance sends scheduled traffic to it.',
        'required_notice' => 'This instance requires verification. Monitors on unverified domains are limited to a slow interval, one monitor, and GET or HEAD requests with no body.',
        'optional_notice' => 'Verification is not required on this instance, but verified domains are exempt from the stricter unverified limits.',
        'add' => 'Add domain',
        'add_placeholder' => 'example.com',
        'verify' => 'Verify',
        'recheck' => 'Check again',
        'remove' => 'Remove',
        'verified' => 'Verified',
        'pending' => 'Not verified',
        'last_checked' => 'Last checked :time',
        'empty' => 'No domains added yet.',
        'how_to' => 'Publish either of these, then verify:',
        'dns_option' => 'A TXT record at :host.:domain with the value below.',
        'file_option' => 'A file at https://:domain:path containing the value below.',
        'token_label' => 'Verification value',
        'errors' => [
            'not_found' => 'The verification value was not found in DNS or at the well-known URL.',
        ],
        'messages' => [
            'added' => 'Added :domain. Publish the verification value, then verify it.',
            'verified' => ':domain is verified.',
            'not_found' => 'Could not verify :domain. DNS changes can take a while to propagate.',
            'removed' => 'Removed :domain.',
        ],
    ],

    'account' => [
        'heading' => 'Delete account',
        'description' => 'Delete your account and all of its resources',
        'warning' => 'Warning',
        'warning_description' => 'Please proceed with caution, this cannot be undone.',
        'delete' => 'Delete account',
        'confirm_title' => 'Are you sure you want to delete your account?',
        'confirm_description' => 'Once your account is deleted, all of its resources and data will also be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.',
        'password_label' => 'Password',
    ],
];
