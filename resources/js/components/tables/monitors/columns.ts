import { Link } from '@inertiajs/vue3';
import type { ColumnDef } from '@tanstack/vue-table';
import { ClockIcon, EyeIcon } from 'lucide-vue-next';
import { h } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { trans } from '@/lib/i18n';
import * as monitorsRoute from '@/routes/monitors';
import type { Monitor } from '@/types/monitors';

export const columns: ColumnDef<Monitor>[] = [
    {
        accessorKey: 'name',
        header: () => trans('monitors.table.columns.name'),
        cell: ({ row }) =>
            h('div', { class: 'flex items-center gap-3' }, [
                h('span', {
                    class: [
                        'size-2 rounded-full shrink-0',
                        row.original.is_up ? 'bg-emerald-500' : 'bg-red-500',
                    ],
                }),
                h('div', { class: 'flex flex-col min-w-0' }, [
                    h('span', { class: 'font-medium leading-tight' }, row.original.name),
                    h('div', { class: 'flex items-center gap-1.5 mt-0.5' }, [
                        h(
                            Badge,
                            { variant: 'secondary', class: 'h-4 px-1.5 text-[10px] rounded' },
                            () => (row.original.type as string).toUpperCase(),
                        ),
                        h(
                            'span',
                            {
                                class: [
                                    'text-xs',
                                    row.original.is_up ? 'text-emerald-500' : 'text-destructive',
                                ],
                            },
                            row.original.is_up ? trans('monitors.is_up') : trans('monitors.is_down'),
                        ),
                    ]),
                ]),
            ]),
    },
    {
        accessorKey: 'interval',
        header: () => trans('monitors.table.columns.interval'),
        cell: ({ row }) =>
            h('div', { class: 'flex items-center gap-1.5 text-muted-foreground' }, [
                h(ClockIcon, { class: 'size-3.5 shrink-0' }),
                h('span', { class: 'text-sm' }, row.original.check_interval),
            ]),
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
