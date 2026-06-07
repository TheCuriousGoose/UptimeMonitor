<?php

namespace App\Enums;

enum Permission: string
{
    // Users
    case UsersView   = 'users.view';
    case UsersEdit   = 'users.edit';

    // Roles
    case RolesView   = 'roles.view';
    case RolesCreate = 'roles.create';
    case RolesEdit   = 'roles.edit';
    case RolesDelete = 'roles.delete';

    // Monitors
    case MonitorsView   = 'monitors.view';
    case MonitorsCreate = 'monitors.create';
    case MonitorsEdit   = 'monitors.edit';
    case MonitorsDelete = 'monitors.delete';

    /**
     * All Permission cases for a given resource prefix.
     * Useful for seeding or syncing role permissions by resource.
     *
     * Example: Permission::forResource('monitors')
     *   => [Permission::MonitorsCreate]
     *
     * @return self[]
     */
    public static function forResource(string $resource): array
    {
        return array_values(array_filter(
            self::cases(),
            fn(self $p) => str_starts_with($p->value, $resource.'.')
        ));
    }
}
