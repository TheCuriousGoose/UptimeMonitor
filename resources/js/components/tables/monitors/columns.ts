import { Link } from '@inertiajs/vue3';
import type { ColumnDef } from '@tanstack/vue-table';
import { h } from 'vue';
import { Badge } from '@/components/ui/badge';
import { trans } from '@/lib/i18n';
import type { Monitor } from '@/types/monitors';
import TableRowActions from './TableRowActions.vue';

export const columns: ColumnDef<Monitor>[] = [
    {
        accessorKey: 'name',
        header: () => trans('monitors.table.columns.name'),
    },
    {
        accessorKey: 'is_up',
        header: () => trans('monitors.table.columns.is_up'),
        cell: ({ row }) =>
            h(
                Badge,
                { variant: row.original.is_up ? 'success' : 'destructive' },
                () =>
                    row.original.is_up
                        ? trans('monitors.is_up')
                        : trans('monitors.is_down'),
            ),
    },
    {
        accessorKey: 'url',
        header: () => trans('monitors.table.columns.url'),
        cell: ({ row }) =>
            h(
                'a',
                {
                    href: row.original.url,
                    target: '_blank',
                    rel: 'noopener noreferrer',
                    class: 'underline underline-offset-4 hover:no-underline',
                },
                row.original.url.substring(0, 50) +
                    (row.original.url.length >= 50 ? '...' : ''),
            ),
    },
    {
        accessorKey: 'type',
        header: () => trans('monitors.table.columns.type'),
    },
    {
        accessorKey: 'interval',
        header: () => trans('monitors.table.columns.interval'),
        cell: ({ row }) =>
            h(
                Badge,
                { variant: 'secondary' },
                () => row.original.check_interval,
            ),
    },
    {
        accessorKey: 'timeout',
        header: () => trans('monitors.table.columns.timeout'),
        cell: ({ row }) => `${row.original.timeout}s`,
    },
    {
        accessorKey: 'actions',
        header: () => trans('monitors.table.columns.actions'),
        cell: ({ row }) => h(TableRowActions, { monitor: row.original }),
    },
];
