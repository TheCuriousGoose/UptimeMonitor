import type { ColumnDef } from '@tanstack/vue-table';
import { h } from 'vue';
import { Badge } from '@/components/ui/badge';
import type { Role } from '@/types/admin';
import type { User } from '@/types/auth';
import TableRowActions from './TableRowActions.vue';

export type UserWithRoles = User & { roles: Role[] };

export const createColumns = (roles: Role[]): ColumnDef<UserWithRoles>[] => [
    {
        accessorKey: 'name',
        header: () => 'Name',
        cell: ({ row }) =>
            h('div', { class: 'flex flex-col' }, [
                h('span', { class: 'font-medium' }, row.original.name),
                h('span', { class: 'text-xs text-muted-foreground' }, row.original.email),
            ]),
    },
    {
        accessorKey: 'roles',
        header: () => 'Roles',
        cell: ({ row }) => {
            const userRoles = row.original.roles || [];

            if (userRoles.length === 0) {
                return h('span', { class: 'text-xs text-muted-foreground' }, 'No roles');
            }

            return h('div', { class: 'flex flex-wrap gap-1' }, [
                ...userRoles.map((role) =>
                    h(
                        Badge,
                        { 
                            key: role.id, 
                            variant: 
                                role.name === 'Super Admin' 
                                    ? 'default' 
                                    : role.name === 'Admin' 
                                    ? 'secondary' 
                                    : 'outline',
                            class: 'text-xs'
                        },
                        () => role.name,
                    )
                ),
            ]);
        },
    },
    {
        accessorKey: 'created_at',
        header: () => 'Created',
        cell: ({ row }) =>
            h('span', { class: 'text-sm text-muted-foreground' },
                new Date(row.original.created_at).toLocaleDateString()
            ),
    },
    {
        accessorKey: 'actions',
        header: () => 'Actions',
        cell: ({ row }) => h(TableRowActions, { user: row.original, allRoles: roles }),
    },
];
