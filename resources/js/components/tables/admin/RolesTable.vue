<script setup lang="ts">
import { computed } from 'vue';
import DataTable from '@/components/tables/DataTable.vue';
import type { Permission, Role } from '@/types/admin';
import type { Pagination } from '@/types/pagination';
import { createRoleColumns } from './roleColumns';

const props = defineProps<{
    roles: Pagination<Role>;
    permissions: Permission[];
}>();

const columns = computed(() => createRoleColumns(props.permissions));
</script>

<template>
    <DataTable
        :columns="columns"
        :pagination="roles"
        table-key="roles"
        :row-id="(row) => String(row.id)"
        empty-text="No roles found."
        item-label="Roles"
    />
</template>
