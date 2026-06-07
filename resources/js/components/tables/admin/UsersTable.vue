<script setup lang="ts">
import { computed } from 'vue';
import DataTable from '@/components/tables/DataTable.vue';
import type { Role } from '@/types/admin';
import type { User } from '@/types/auth';
import type { Pagination } from '@/types/pagination';
import { createColumns } from './columns';
import type { UserWithRoles } from './columns';

type UserPagination = Pagination<User> & { data: UserWithRoles[] };

const props = defineProps<{
    users: UserPagination;
    roles: Role[];
}>();

const columns = computed(() => createColumns(props.roles));
</script>

<template>
    <DataTable :columns="columns" :pagination="users" table-key="users" :row-id="(row) => String(row.id)"
        empty-text="No users found." item-label="Users" />
</template>
