import type { ColumnDef } from "@tanstack/vue-table";
import { trans } from "laravel-vue-i18n";
import { EyeIcon } from "lucide-vue-next";
import { h } from "vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import monitors from "@/routes/monitors";
import type { Monitor } from "@/types/monitors";

export const columns: ColumnDef<Monitor>[] = [
    {
        accessorKey: "name",
        header: () => trans('monitors.table.columns.name'),
    },
    {
        accessorKey: "url",
        header: () => trans('monitors.table.columns.url'),
        cell: ({ row }) =>
            h(
                "a",
                {
                    href: row.original.url,
                    target: "_blank",
                    rel: "noopener noreferrer",
                    class: "underline underline-offset-4 hover:no-underline",
                },
                row.original.url,
            ),
    },
    {
        accessorKey: "interval",
        header: () => trans('monitors.table.columns.interval'),
        cell: ({ row }) => h(Badge, { variant: "secondary" }, () => row.original.check_interval),
    },
    {
        accessorKey: "timeout",
        header: () => trans('monitors.table.columns.timeout'),
        cell: ({ row }) => `${row.original.timeout}s`,
    },
    {
        accessorKey: "actions",
        header: () => trans('monitors.table.columns.actions'),
        cell: ({ row }) =>
            h(
                Button,
                {
                    variant: "ghost",
                    size: "sm",
                    onClick: () => monitors.show(row.original.uuid),
                },
                () => h(
                    EyeIcon
                ),
            ),
    },
];