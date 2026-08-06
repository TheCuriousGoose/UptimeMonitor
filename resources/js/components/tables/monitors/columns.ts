import type { ColumnDef } from '@tanstack/vue-table';
import { ClockIcon } from 'lucide-vue-next';
import { h } from 'vue';
import MonitorStatusBadge from '@/components/monitors/MonitorStatusBadge.vue';
import { formatInterval, formatRelative } from '@/lib/format';
import { trans } from '@/lib/i18n';
import type { Monitor } from '@/types/monitors';
import type { ColumnMeta } from '@/types/tables';
import TableRowActions from './TableRowActions.vue';

export const columns: ColumnDef<Monitor>[] = [
    {
        accessorKey: 'name',
        meta: { sortable: true } satisfies ColumnMeta,
        header: () => trans('monitors.table.columns.name'),
        cell: ({ row }) =>
            h('div', { class: 'flex min-w-0 flex-col' }, [
                h(
                    'span',
                    { class: 'font-medium leading-tight' },
                    row.original.name,
                ),
                h(
                    'span',
                    { class: 'mt-0.5 truncate text-xs text-muted-foreground' },
                    row.original.url,
                ),
            ]),
    },
    {
        accessorKey: 'status',
        meta: { sortable: true } satisfies ColumnMeta,
        header: () => trans('monitors.table.columns.status'),
        cell: ({ row }) =>
            h(MonitorStatusBadge, { status: row.original.status }),
    },
    {
        accessorKey: 'type',
        meta: { sortable: true, hideOnMobile: true } satisfies ColumnMeta,
        header: () => trans('monitors.table.columns.type'),
        cell: ({ row }) =>
            h(
                'span',
                { class: 'text-sm text-muted-foreground' },
                trans(`monitors.form.type.options.${row.original.type}`),
            ),
    },
    {
        accessorKey: 'interval',
        meta: { sortable: true, hideOnMobile: true } satisfies ColumnMeta,
        header: () => trans('monitors.table.columns.interval'),
        cell: ({ row }) =>
            h(
                'div',
                { class: 'flex items-center gap-1.5 text-muted-foreground' },
                [
                    h(ClockIcon, { class: 'size-3.5 shrink-0' }),
                    h(
                        'span',
                        { class: 'font-mono text-sm tabular-nums' },
                        formatInterval(row.original.interval_seconds),
                    ),
                ],
            ),
    },
    {
        accessorKey: 'last_checked',
        meta: { sortable: true, hideOnMobile: true } satisfies ColumnMeta,
        header: () => trans('monitors.table.columns.last_checked'),
        cell: ({ row }) =>
            h(
                'span',
                {
                    class: 'font-mono text-sm tabular-nums text-muted-foreground',
                },
                row.original.last_checked_at
                    ? formatRelative(row.original.last_checked_at)
                    : trans('monitors.never_checked'),
            ),
    },
    {
        accessorKey: 'actions',
        header: () => trans('monitors.table.columns.actions'),
        cell: ({ row }) => h(TableRowActions, { monitor: row.original }),
    },
];
