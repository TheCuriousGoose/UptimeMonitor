<template>
    <div class="flex flex-col gap-4">
        <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                        <TableHead v-for="header in headerGroup.headers" :key="header.id">
                            <FlexRender v-if="!header.isPlaceholder" :render="header.column.columnDef.header"
                                :props="header.getContext()" />
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <template v-if="table.getRowModel().rows.length">
                        <TableRow v-for="row in table.getRowModel().rows" :key="row.id">
                            <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                                <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                            </TableCell>
                        </TableRow>
                    </template>
                    <TableEmpty v-else :colspan="columns.length">
                        {{ emptyText }}
                    </TableEmpty>
                </TableBody>
            </Table>
        </div>

        <div class="flex items-center justify-between text-sm text-muted-foreground">
            <p>
                {{ $t('pagination.showing', {
                    from: String(pagination.meta.from ?? 0),
                    to: String(pagination.meta.to ?? 0),
                    total: String(pagination.meta.total),
                    type: itemLabel,
                }) }}
            </p>
            <Pagination
                v-slot="{ page }"
                :total="pagination.meta.total"
                :items-per-page="pagination.meta.per_page"
                :page="pagination.meta.current_page"
                :sibling-count="1"
                show-edges
                @update:page="navigateTo"
            >
                <PaginationContent v-slot="{ items }">
                    <PaginationPrevious />
                    <template v-for="(item, index) in items" :key="index">
                        <PaginationItem
                            v-if="item.type === 'page'"
                            :value="item.value"
                            :is-active="item.value === page"
                        >
                            {{ item.value }}
                        </PaginationItem>
                        <PaginationEllipsis v-else :index="index" />
                    </template>
                    <PaginationNext />
                </PaginationContent>
            </Pagination>
        </div>
    </div>
</template>

<script setup lang="ts" generic="T">
import { router } from "@inertiajs/vue3";
import { useVueTable, getCoreRowModel, FlexRender } from "@tanstack/vue-table";
import type { ColumnDef } from "@tanstack/vue-table";
import { computed, onMounted } from "vue";
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { useColumnPreferences } from "@/composables/useColumnPreferences";
import type { Pagination as PaginationData } from "@/types/pagination";
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from "../ui/pagination";

const props = defineProps<{
    columns: ColumnDef<T>[];
    pagination: PaginationData<any>;
    tableKey: string;
    defaultVisibility?: Record<string, boolean>;
    rowId: (row: T) => string;
    emptyText: string;
    itemLabel: string;
}>();

const resolvedVisibility = props.defaultVisibility ??
    Object.fromEntries(
        props.columns
            .filter((col) => 'accessorKey' in col)
            .map((col) => [(col as { accessorKey: string }).accessorKey, true]),
    );

const { columns: columnVisibility, load, toggle } = useColumnPreferences(
    props.tableKey,
    resolvedVisibility,
);

onMounted(load);

const table = computed(() => useVueTable({
    get data() {
        return props.pagination.data;
    },
    get columns() {
        return props.columns;
    },
    getCoreRowModel: getCoreRowModel(),
    getRowId: props.rowId,
    manualPagination: true,
    rowCount: props.pagination.meta.total,
    state: { columnVisibility: columnVisibility.value },
    onColumnVisibilityChange: (updater) => {
        const current = columnVisibility.value ?? {};
        const next = typeof updater === 'function' ? updater(current) : updater;
        Object.keys(next).forEach((col) => {
            if (next[col] !== current[col]) {
                toggle(col);
            }
        });
    },
}));

function navigateTo(page: number) {
    const url = new URL(window.location.href);
    url.searchParams.set('page', String(page));
    router.visit(url.toString(), { preserveScroll: true });
}
</script>