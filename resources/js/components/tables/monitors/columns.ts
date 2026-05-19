import { Link } from '@inertiajs/vue3';
import type { ColumnDef } from '@tanstack/vue-table';
import { trans } from 'laravel-vue-i18n';
import { EyeIcon } from 'lucide-vue-next';
import { h } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import * as monitorsRoute from '@/routes/monitors';
import type { Monitor } from '@/types/monitors';

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
        cell: ({ row }) =>
            h(
                Button,
                {
                    as: Link,
                    variant: 'ghost',
                    size: 'sm',
                    href: monitorsRoute.show(row.original).url,
                },
                () => h(EyeIcon),
            ),
    },
];
