<script setup lang="ts">
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import UsersTable from '@/components/tables/admin/UsersTable.vue';
import TableFilterBar from '@/components/tables/TableFilterBar.vue';
import { Input } from '@/components/ui/input';
import * as adminUsers from '@/routes/admin/users';
import type { Role } from '@/types/admin';
import type { User } from '@/types/auth';
import type { Pagination } from '@/types/pagination';
import debounce from '@/util/debounce';

type UserWithRoles = User & { roles: Role[] };
type UserPagination = Pagination<UserWithRoles>;

const props = defineProps<{
    users: UserPagination;
    roles: Role[];
}>();

setLayoutProps({
    breadcrumbs: [{ title: 'Users', href: adminUsers.index().url }],
});

const search = ref<string>('');

watch(search, debounce((value: string) => {
    const query = value.trim();

    router.get(adminUsers.index(), { search: query || undefined }, {
        preserveState: true,
        replace: true,
        only: ['users'],
    });
}, 300));
</script>

<template>
    <Head title="Users" />

    <TableFilterBar>
        <template #filters>
            <Input
                v-model="search"
                type="search"
                class="w-64"
                placeholder="Search users…"
            />
        </template>
    </TableFilterBar>

    <UsersTable :users="props.users" :roles="props.roles" />
</template>
