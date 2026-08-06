<script setup lang="ts">
import DataTable from '@/components/tables/DataTable.vue';
import type { Incident } from '@/types/monitors';
import type { Pagination } from '@/types/pagination';
import type { SortDirection } from '@/types/tables';
import { columns } from './columns';

defineProps<{
    incidents: Pagination<Incident>;
    sort?: string | null;
    direction?: SortDirection;
    loading?: boolean;
}>();
</script>

<template>
    <DataTable
        :columns="columns"
        :pagination="incidents"
        table-key="incidents"
        :row-id="(row) => row.uuid"
        :empty-text="$t('incidents.table.empty')"
        :item-label="$t('incidents.title')"
        :sort="sort"
        :direction="direction"
        :loading="loading"
    />
</template>
