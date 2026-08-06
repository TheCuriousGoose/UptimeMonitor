<template>
    <div class="flex flex-col gap-4">
        <!-- The selection toolbar replaces nothing; it appears above the table
             so the row checkboxes stay where the user left them. -->
        <div
            v-if="selectable && selectedIds.length > 0"
            class="flex flex-wrap items-center justify-between gap-2 rounded-md border bg-muted/40 px-3 py-2"
        >
            <p class="text-sm">
                {{
                    $t('tables.selection.count', {
                        count: String(selectedIds.length),
                    })
                }}
            </p>
            <div class="flex flex-wrap items-center gap-2">
                <slot
                    name="selection"
                    :selected="selectedIds"
                    :clear="clearSelection"
                />
                <Button variant="ghost" size="sm" @click="clearSelection">
                    {{ $t('base.clear') }}
                </Button>
            </div>
        </div>

        <div class="overflow-x-auto rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow
                        v-for="headerGroup in table.getHeaderGroups()"
                        :key="headerGroup.id"
                    >
                        <TableHead
                            v-if="selectable"
                            class="w-10"
                            :aria-label="$t('tables.selection.select_all')"
                        >
                            <Checkbox
                                :model-value="allOnPageSelected"
                                :aria-label="$t('tables.selection.select_all')"
                                @update:model-value="toggleAllOnPage"
                            />
                        </TableHead>
                        <TableHead
                            v-for="header in headerGroup.headers"
                            :key="header.id"
                            :class="columnClass(header.column.id)"
                            :aria-sort="ariaSort(header.column.id)"
                        >
                            <SortableHeader
                                v-if="isSortable(header.column.id)"
                                :label="headerLabel(header)"
                                :active="sort === header.column.id"
                                :direction="direction"
                                @sort="applySort(header.column.id)"
                            />
                            <FlexRender
                                v-else-if="!header.isPlaceholder"
                                :render="header.column.columnDef.header"
                                :props="header.getContext()"
                            />
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <!-- Skeletons only while the user is waiting on their own
                         action. A background refresh must not blank the table
                         out from under them. -->
                    <template v-if="loading">
                        <TableRow
                            v-for="n in skeletonRows"
                            :key="`skeleton-${n}`"
                        >
                            <TableCell v-if="selectable"
                                ><Skeleton class="size-4"
                            /></TableCell>
                            <TableCell
                                v-for="column in visibleColumnIds"
                                :key="column"
                                :class="columnClass(column)"
                            >
                                <Skeleton class="h-4 w-full max-w-32" />
                            </TableCell>
                        </TableRow>
                    </template>
                    <template v-else-if="table.getRowModel().rows.length">
                        <TableRow
                            v-for="row in table.getRowModel().rows"
                            :key="row.id"
                            :data-state="
                                row.getIsSelected() ? 'selected' : undefined
                            "
                        >
                            <TableCell v-if="selectable">
                                <Checkbox
                                    :model-value="row.getIsSelected()"
                                    :aria-label="
                                        $t('tables.selection.select_row')
                                    "
                                    @update:model-value="
                                        row.toggleSelected(!row.getIsSelected())
                                    "
                                />
                            </TableCell>
                            <TableCell
                                v-for="cell in row.getVisibleCells()"
                                :key="cell.id"
                                :class="columnClass(cell.column.id)"
                            >
                                <FlexRender
                                    :render="cell.column.columnDef.cell"
                                    :props="cell.getContext()"
                                />
                            </TableCell>
                        </TableRow>
                    </template>
                    <TableEmpty
                        v-else
                        :colspan="columns.length + (selectable ? 1 : 0)"
                    >
                        {{ emptyText }}
                    </TableEmpty>
                </TableBody>
            </Table>
        </div>

        <div
            class="flex flex-wrap items-center justify-between gap-2 text-sm text-muted-foreground"
        >
            <!-- Announced so that filtering and sorting, which change this
                 line without moving focus, are not silent. -->
            <p aria-live="polite">
                {{
                    $t('pagination.showing', {
                        from: String(pagination.meta.from ?? 0),
                        to: String(pagination.meta.to ?? 0),
                        total: String(pagination.meta.total),
                        type: itemLabel,
                    })
                }}
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
import { router } from '@inertiajs/vue3';
import { useVueTable, getCoreRowModel, FlexRender } from '@tanstack/vue-table';
import type { ColumnDef, Header, RowSelectionState } from '@tanstack/vue-table';
import { computed, onMounted, ref, watch } from 'vue';
import SortableHeader from '@/components/tables/SortableHeader.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useColumnPreferences } from '@/composables/useColumnPreferences';
import type { Pagination as PaginationData } from '@/types/pagination';
import type { ColumnMeta } from '@/types/tables';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from '../ui/pagination';

const props = withDefaults(
    defineProps<{
        columns: ColumnDef<T>[];
        pagination: PaginationData<any>;
        tableKey: string;
        defaultVisibility?: Record<string, boolean>;
        rowId: (row: T) => string;
        emptyText: string;
        itemLabel: string;
        /** Currently sorted column id, from the server. */
        sort?: string | null;
        direction?: 'asc' | 'desc';
        selectable?: boolean;
        loading?: boolean;
    }>(),
    { sort: null, direction: 'asc', selectable: false, loading: false },
);

const resolvedVisibility =
    props.defaultVisibility ??
    Object.fromEntries(
        props.columns
            .filter((col) => 'accessorKey' in col)
            .map((col) => [(col as { accessorKey: string }).accessorKey, true]),
    );

const {
    columns: columnVisibility,
    load,
    toggle,
} = useColumnPreferences(props.tableKey, resolvedVisibility);

onMounted(load);

const rowSelection = ref<RowSelectionState>({});

const table = computed(() =>
    useVueTable({
        get data() {
            return props.pagination.data;
        },
        get columns() {
            return props.columns;
        },
        getCoreRowModel: getCoreRowModel(),
        getRowId: props.rowId,
        manualPagination: true,
        // The server orders the rows; the table only reports what was asked
        // for, so it must not re-sort the page it was handed.
        manualSorting: true,
        enableRowSelection: props.selectable,
        rowCount: props.pagination.meta.total,
        state: {
            columnVisibility: columnVisibility.value,
            rowSelection: rowSelection.value,
        },
        onRowSelectionChange: (updater) => {
            rowSelection.value =
                typeof updater === 'function'
                    ? updater(rowSelection.value)
                    : updater;
        },
        onColumnVisibilityChange: (updater) => {
            const current = columnVisibility.value ?? {};
            const next =
                typeof updater === 'function' ? updater(current) : updater;
            Object.keys(next).forEach((col) => {
                if (next[col] !== current[col]) {
                    toggle(col);
                }
            });
        },
    }),
);

const selectedIds = computed(() => Object.keys(rowSelection.value));

function clearSelection() {
    rowSelection.value = {};
}

// A selection is a list of ids on a page the server just replaced. Keeping it
// across a filter or page change would act on rows the user can no longer see.
watch(
    () => props.pagination.data,
    () => clearSelection(),
);

const allOnPageSelected = computed(() => {
    const rows = table.value.getRowModel().rows;

    return rows.length > 0 && rows.every((row) => row.getIsSelected());
});

function toggleAllOnPage(value: boolean | 'indeterminate') {
    table.value
        .getRowModel()
        .rows.forEach((row) => row.toggleSelected(value === true));
}

function metaFor(columnId: string): ColumnMeta | undefined {
    return props.columns.find(
        (col) => 'accessorKey' in col && col.accessorKey === columnId,
    )?.meta as ColumnMeta | undefined;
}

function isSortable(columnId: string): boolean {
    return metaFor(columnId)?.sortable === true;
}

/** Low-priority columns fold away rather than forcing a horizontal scroll. */
function columnClass(columnId: string): string | undefined {
    return metaFor(columnId)?.hideOnMobile ? 'hidden md:table-cell' : undefined;
}

function ariaSort(
    columnId: string,
): 'ascending' | 'descending' | 'none' | undefined {
    if (!isSortable(columnId)) {
        return undefined;
    }

    if (props.sort !== columnId) {
        return 'none';
    }

    return props.direction === 'asc' ? 'ascending' : 'descending';
}

/**
 * Column headers are declared as render functions returning a translated
 * string, so the label can be read back out for the sortable variant.
 */
function headerLabel(header: Header<T, unknown>): string {
    const template = header.column.columnDef.header;

    return typeof template === 'function'
        ? String(template(header.getContext()))
        : String(template ?? '');
}

const visibleColumnIds = computed(() =>
    table.value.getVisibleLeafColumns().map((column) => column.id),
);

const skeletonRows = computed(() =>
    Math.max(1, Math.min(props.pagination.data.length || 5, 10)),
);

/** asc on first click, desc on second, back to the default order on third. */
function applySort(columnId: string) {
    const url = new URL(window.location.href);

    if (props.sort !== columnId) {
        url.searchParams.set('sort', columnId);
        url.searchParams.set('direction', 'asc');
    } else if (props.direction === 'asc') {
        url.searchParams.set('sort', columnId);
        url.searchParams.set('direction', 'desc');
    } else {
        url.searchParams.delete('sort');
        url.searchParams.delete('direction');
    }

    // A re-sort invalidates the page number: row 16 is a different row now.
    url.searchParams.delete('page');

    router.visit(url.toString(), { preserveScroll: true, preserveState: true });
}

function navigateTo(page: number) {
    const url = new URL(window.location.href);
    url.searchParams.set('page', String(page));
    router.visit(url.toString(), { preserveScroll: true });
}
</script>
