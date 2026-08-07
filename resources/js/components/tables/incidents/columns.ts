import type { ColumnDef } from '@tanstack/vue-table';
import { h } from 'vue';
import IncidentStatusBadge from '@/components/incidents/IncidentStatusBadge.vue';
import { formatDateTime, formatDuration } from '@/lib/format';
import { trans } from '@/lib/i18n';
import type { Incident } from '@/types/monitors';
import type { ColumnMeta } from '@/types/tables';

export const columns: ColumnDef<Incident>[] = [
    {
        accessorKey: 'monitor',
        meta: { sortable: true } satisfies ColumnMeta,
        header: () => trans('incidents.table.columns.monitor'),
        cell: ({ row }) =>
            h('div', { class: 'flex min-w-0 flex-col' }, [
                h(
                    'span',
                    { class: 'font-medium leading-tight' },
                    row.original.monitor?.name ?? '-',
                ),
                h(
                    'span',
                    { class: 'mt-0.5 truncate text-xs text-muted-foreground' },
                    row.original.monitor?.url ?? '',
                ),
            ]),
    },
    {
        accessorKey: 'status',
        meta: { sortable: true } satisfies ColumnMeta,
        header: () => trans('incidents.table.columns.status'),
        cell: ({ row }) =>
            h(IncidentStatusBadge, { isOngoing: row.original.is_ongoing }),
    },
    {
        accessorKey: 'cause',
        meta: { sortable: true, hideOnMobile: true } satisfies ColumnMeta,
        header: () => trans('incidents.table.columns.cause'),
        cell: ({ row }) =>
            h(
                'span',
                { class: 'text-sm text-muted-foreground' },
                row.original.cause ?? '-',
            ),
    },
    {
        accessorKey: 'started',
        meta: { sortable: true, hideOnMobile: true } satisfies ColumnMeta,
        header: () => trans('incidents.table.columns.started'),
        cell: ({ row }) =>
            h(
                'span',
                {
                    class: 'font-mono text-sm tabular-nums text-muted-foreground',
                },
                formatDateTime(row.original.started_at),
            ),
    },
    {
        accessorKey: 'duration',
        meta: { sortable: true } satisfies ColumnMeta,
        header: () => trans('incidents.table.columns.duration'),
        cell: ({ row }) =>
            h(
                'span',
                {
                    class: row.original.is_ongoing
                        ? 'font-mono text-sm font-medium tabular-nums text-red-600 dark:text-red-400'
                        : 'font-mono text-sm tabular-nums text-muted-foreground',
                },
                formatDuration(row.original.duration_seconds),
            ),
    },
    {
        accessorKey: 'failed_checks',
        meta: { sortable: true, hideOnMobile: true } satisfies ColumnMeta,
        header: () => trans('incidents.table.columns.failed_checks'),
        cell: ({ row }) =>
            h(
                'span',
                {
                    class: 'font-mono text-sm tabular-nums text-muted-foreground',
                },
                String(row.original.failed_checks),
            ),
    },
];
