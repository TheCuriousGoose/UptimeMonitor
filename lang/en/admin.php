<?php

return [
    'users' => [
        'title' => 'Users',
        'search' => 'Search users…',
        'edit' => 'Edit :name',
        'tabs' => [
            'details' => 'Details',
            'roles' => 'Roles',
            'password' => 'Password',
        ],
        'name' => 'Name',
        'email' => 'Email',
        'current_roles' => 'Current roles:',
        'no_roles' => 'No roles assigned',
        'search_roles' => 'Search roles…',
        'no_roles_match' => 'No roles match ":search".',
        'save' => 'Save changes',

        'password' => [
            'heading' => 'Set new password',
            'description' => 'Directly assign a new password for this user.',
            'new' => 'New password',
            'confirmation' => 'Confirm password',
            'submit' => 'Set password',
            'reset_heading' => 'Email reset link',
            'reset_description' => 'Send :email an email with a link to reset their password themselves.',
            'reset_submit' => 'Send reset link',
        ],
    ],

    'roles' => [
        'title' => 'Roles',
        'search' => 'Search roles…',
        'create' => 'New role',
        'create_title' => 'Create role',
        'name_placeholder' => 'e.g. Editor',
        'edit' => 'Edit role: :name',
        'name' => 'Name',
        'permissions' => 'Permissions',
        'search_permissions' => 'Search permissions…',
        'submit_create' => 'Create',
        'save' => 'Save',
        'impersonate' => 'Impersonate :name',
        'cannot_impersonate' => 'Cannot impersonate Super Admin',
        'edit_role' => 'Edit :name',
        'delete_role' => 'Delete :name',
        'cannot_delete' => 'Cannot delete Super Admin',
        'confirm_delete' => 'Delete role ":name"?',
        'confirm_delete_description' => 'Users assigned to this role will lose its permissions. This cannot be undone.',
        'super_admin_locked' => 'Super Admin has all permissions implicitly and cannot be changed.',
    ],
];
