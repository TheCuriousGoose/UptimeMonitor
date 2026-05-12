<script setup lang="ts">
import DataTable from "@/components/tables/DataTable.vue";
import type { Monitor } from "@/types/monitors";
import type { Pagination } from "@/types/pagination";
import { columns } from "./columns";

defineProps<{
    monitors: Pagination<Monitor>;
}>();
</script>

<template>
    <DataTable
        :columns="columns"
        :pagination="monitors"
        table-key="monitors"
        :row-id="(row) => row.uuid"
        :empty-text="$t('monitors.table.empty')"
        :item-label="$t('monitors.title')"
    />
</template>
