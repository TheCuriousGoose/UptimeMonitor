<?php

namespace App\Enums;

enum Permission: string
{
    // Users
    case UsersView = 'users.view';
    case UsersEdit = 'users.edit';

    // Roles
    case RolesView = 'roles.view';
    case RolesCreate = 'roles.create';
    case RolesEdit = 'roles.edit';
    case RolesDelete = 'roles.delete';

    // Monitors
    case MonitorsView = 'monitors.view';
    case MonitorsCreate = 'monitors.create';
    case MonitorsEdit = 'monitors.edit';
    case MonitorsDelete = 'monitors.delete';

    // Notification channels
    case ChannelsView = 'channels.view';
    case ChannelsCreate = 'channels.create';
    case ChannelsEdit = 'channels.edit';
    case ChannelsDelete = 'channels.delete';

    // Status pages
    case StatusPagesView = 'status_pages.view';
    case StatusPagesCreate = 'status_pages.create';
    case StatusPagesEdit = 'status_pages.edit';
    case StatusPagesDelete = 'status_pages.delete';

    // Content (docs, blog, changelog)
    case ContentView = 'content.view';
    case ContentCreate = 'content.create';
    case ContentEdit = 'content.edit';
    case ContentDelete = 'content.delete';

    // Incidents
    case IncidentsView = 'incidents.view';
    case IncidentsAcknowledge = 'incidents.acknowledge';
    case IncidentsComment = 'incidents.comment';

    // Maintenance windows
    case MaintenanceView = 'maintenance.view';
    case MaintenanceCreate = 'maintenance.create';
    case MaintenanceEdit = 'maintenance.edit';
    case MaintenanceDelete = 'maintenance.delete';

    // Instance settings
    case SettingsView = 'settings.view';
    case SettingsEdit = 'settings.edit';

    /**
     * All Permission cases for a given resource prefix.
     * Useful for seeding or syncing role permissions by resource.
     *
     * Example: Permission::forResource('monitors')
     *
     * @return self[]
     */
    public static function forResource(string $resource): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $p) => str_starts_with($p->value, $resource.'.'),
        ));
    }
}
