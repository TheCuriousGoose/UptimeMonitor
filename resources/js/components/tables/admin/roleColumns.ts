import type { ColumnDef } from '@tanstack/vue-table';
import { h } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import type { Permission, Role } from '@/types/admin';
import RoleRowActions from './RoleRowActions.vue';

export const createRoleColumns = (permissions: Permission[]): ColumnDef<Role>[] => [
    {
        accessorKey: 'name',
        header: () => 'Name',
        cell: ({ row }) => h('span', { class: 'font-medium' }, row.original.name),
    },
    {
        accessorKey: 'permissions',
        header: () => 'Permissions',
        cell: ({ row }) => {
            const role = row.original;

            if (role.name === 'Super Admin') {
                return h('span', { class: 'text-xs text-muted-foreground italic' }, 'all permissions');
            }

            if (role.permissions.length === 0) {
                return h('span', { class: 'text-xs text-muted-foreground' }, 'none');
            }

            const names = role.permissions.map((p) => p.name);
            const count = names.length;

            return h(TooltipProvider, { delayDuration: 0 }, () =>
                h(Tooltip, () => [
                    h(TooltipTrigger, () =>
                        h(Badge, { variant: 'secondary', class: 'text-xs cursor-default' }, () =>
                            `${count} permission${count === 1 ? '' : 's'}`),
                    ),
                    h(TooltipContent, () => names.map((name) => h('p', { key: name }, name))),
                ]),
            );
        },
    },
    {
        accessorKey: 'users_count',
        header: () => 'Users',
        cell: ({ row }) => h('span', { class: 'text-sm text-muted-foreground' }, String(row.original.users_count ?? 0)),
    },
    {
        accessorKey: 'actions',
        header: () => 'Actions',
        cell: ({ row }) => h(RoleRowActions, { role: row.original, allPermissions: permissions }),
    },
];
