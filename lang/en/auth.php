<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    'successful' => 'You\'ve succesfully been authenticated',

    'login' => [
        'title' => 'Log in to your account',
        'description' => 'Enter your email and password below to log in',
        'page_title' => 'Log in',
        'email_label' => 'Email address',
        'email_placeholder' => 'email{\'@\'}example.com',
        'password_label' => 'Password',
        'password_placeholder' => 'Password',
        'remember_me' => 'Remember me',
        'forgot_password' => 'Forgot password?',
        'submit' => 'Log in',
        'no_account' => 'Don\'t have an account?',
        'sign_up' => 'Sign up',
        'or' => 'Or',
    ],

    'register' => [
        'title' => 'Create an account',
        'description' => 'Enter your details below to create your account',
        'page_title' => 'Register',
        'name_label' => 'Name',
        'name_placeholder' => 'Full name',
        'email_label' => 'Email address',
        'email_placeholder' => 'email{\'@\'}example.com',
        'password_label' => 'Password',
        'password_placeholder' => 'Password',
        'password_confirmation_label' => 'Confirm password',
        'password_confirmation_placeholder' => 'Confirm password',
        'submit' => 'Create account',
        'have_account' => 'Already have an account?',
        'log_in' => 'Log in',
    ],

    'forgot' => [
        'title' => 'Forgot password',
        'description' => 'Enter your email to receive a password reset link',
        'page_title' => 'Forgot password',
        'email_label' => 'Email address',
        'email_placeholder' => 'email{\'@\'}example.com',
        'submit' => 'Email password reset link',
        'return_to' => 'Or, return to',
        'log_in' => 'log in',
    ],

    'reset' => [
        'title' => 'Reset password',
        'description' => 'Please enter your new password below',
        'page_title' => 'Reset password',
        'email_label' => 'Email',
        'password_label' => 'Password',
        'password_placeholder' => 'Password',
        'password_confirmation_label' => 'Confirm password',
        'password_confirmation_placeholder' => 'Confirm password',
        'submit' => 'Reset password',
    ],

    'confirm' => [
        'title' => 'Confirm your password',
        'description' => 'This is a secure area of the application. Please confirm your password before continuing.',
        'page_title' => 'Confirm password',
        'password_label' => 'Password',
        'password_placeholder' => 'Password',
        'submit' => 'Confirm password',
    ],

    'verify' => [
        'title' => 'Verify email',
        'description' => 'Please verify your email address by clicking on the link we just emailed to you.',
        'page_title' => 'Email verification',
        'sent' => 'A new verification link has been sent to the email address you provided during registration.',
        'resend' => 'Resend verification email',
        'log_out' => 'Log out',
    ],

    'two_factor' => [
        'page_title' => 'Two-factor authentication',
        'continue' => 'Continue',
        'or_you_can' => 'or you can',
        'code' => [
            'title' => 'Authentication code',
            'description' => 'Enter the authentication code provided by your authenticator application.',
            'toggle' => 'login using a recovery code',
        ],
        'recovery' => [
            'title' => 'Recovery code',
            'description' => 'Please confirm access to your account by entering one of your emergency recovery codes.',
            'toggle' => 'login using an authentication code',
            'label' => 'Recovery code',
            'placeholder' => 'Enter recovery code',
        ],
    ],

    'oauth' => [
        'google' => 'Continue with Google',
        'github' => 'Continue with Github',
    ],

    // Surfaced on the login form when a social sign-in does not complete.
    // These were referenced by OAuthController before they existed, so a
    // failed sign-in showed the user the key instead of the reason.
    'oauth_invalid_state' => 'That sign-in attempt expired. Please try again.',
    'oauth_failed' => 'We could not complete that sign-in. Please try again.',
    'oauth_no_email' => 'That account did not share an email address, which we need to identify you.',
];
