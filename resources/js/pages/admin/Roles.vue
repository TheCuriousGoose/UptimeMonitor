<script setup lang="ts">
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import { PlusIcon } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import RolePermissionPicker from '@/components/tables/admin/RolePermissionPicker.vue';
import RolesTable from '@/components/tables/admin/RolesTable.vue';
import TableFilterBar from '@/components/tables/TableFilterBar.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import * as rolesRoute from '@/routes/admin/roles';
import type { Permission, Role } from '@/types/admin';
import type { Pagination } from '@/types/pagination';
import debounce from '@/util/debounce';

const props = defineProps<{
    roles: Pagination<Role>;
    permissions: Permission[];
}>();

setLayoutProps({
    breadcrumbs: [{ title: 'Roles', href: rolesRoute.index().url }],
});

const search = ref<string>('');

watch(
    search,
    debounce((value: string) => {
        const query = value.trim();

        router.get(
            rolesRoute.index(),
            { search: query || undefined },
            {
                preserveState: true,
                replace: true,
                only: ['roles'],
            },
        );
    }, 300),
);

// ---- Create ----
const createOpen = ref(false);
const createName = ref('');
const createPerms = ref<number[]>([]);

function submitCreate() {
    router.post(
        rolesRoute.store().url,
        { name: createName.value, permissions: createPerms.value },
        {
            onSuccess: () => {
                createOpen.value = false;
                createName.value = '';
                createPerms.value = [];
            },
        },
    );
}
</script>

<template>
    <Head :title="$t('admin.roles.title')" />

    <TableFilterBar>
        <template #filters>
            <Input
                v-model="search"
                type="search"
                class="w-64"
                :placeholder="$t('admin.roles.search')"
            />
        </template>
        <template #actions>
            <Dialog v-model:open="createOpen">
                <DialogTrigger as-child>
                    <Button size="sm"
                        ><PlusIcon class="mr-1.5 size-4" />
                        {{ $t('admin.roles.create') }}</Button
                    >
                </DialogTrigger>
                <DialogContent class="sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>{{
                            $t('admin.roles.create_title')
                        }}</DialogTitle>
                    </DialogHeader>
                    <div class="space-y-4 py-2">
                        <div class="grid gap-1.5">
                            <Label for="create-name">{{
                                $t('admin.roles.name')
                            }}</Label>
                            <Input
                                id="create-name"
                                v-model="createName"
                                :placeholder="
                                    $t('admin.roles.name_placeholder')
                                "
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label>{{ $t('admin.roles.permissions') }}</Label>
                            <div class="max-h-96 overflow-y-auto pr-1">
                                <RolePermissionPicker
                                    v-model="createPerms"
                                    :permissions="props.permissions"
                                />
                            </div>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button @click="submitCreate">{{
                            $t('admin.roles.submit_create')
                        }}</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </template>
    </TableFilterBar>

    <RolesTable :roles="props.roles" :permissions="props.permissions" />
</template>
