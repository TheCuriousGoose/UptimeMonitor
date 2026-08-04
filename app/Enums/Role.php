<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'Super Admin';
    case Admin = 'Admin';
    case User = 'User';

    /**
     * The permissions assigned to this role on seeding.
     * Super Admin is granted all permissions via User::can(), so none are stored.
     *
     * @return Permission[]
     */
    public function permissions(): array
    {
        return match ($this) {
            self::SuperAdmin => [],
            self::Admin => [
                ...Permission::forResource('users'),
                ...self::monitoringPermissions(),
            ],
            self::User => self::monitoringPermissions(),
        };
    }

    /**
     * Everything an ordinary user needs to run their own monitoring.
     *
     * @return Permission[]
     */
    private static function monitoringPermissions(): array
    {
        return [
            ...Permission::forResource('monitors'),
            ...Permission::forResource('channels'),
            ...Permission::forResource('status_pages'),
        ];
    }
}
