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

watch(search, debounce((value: string) => {
    const query = value.trim();

    router.get(rolesRoute.index(), { search: query || undefined }, {
        preserveState: true,
        replace: true,
        only: ['roles'],
    });
}, 300));

// ---- Create ----
const createOpen = ref(false);
const createName = ref('');
const createPerms = ref<number[]>([]);

function submitCreate() {
    router.post(rolesRoute.store().url, { name: createName.value, permissions: createPerms.value }, {
        onSuccess: () => {
            createOpen.value = false;
            createName.value = '';
            createPerms.value = [];
        },
    });
}
</script>

<template>
    <Head title="Roles" />

    <TableFilterBar>
        <template #filters>
            <Input
                v-model="search"
                type="search"
                class="w-64"
                placeholder="Search roles…"
            />
        </template>
        <template #actions>
            <Dialog v-model:open="createOpen">
                <DialogTrigger as-child>
                    <Button size="sm"><PlusIcon class="size-4 mr-1.5" /> New Role</Button>
                </DialogTrigger>
                <DialogContent class="sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>Create Role</DialogTitle>
                    </DialogHeader>
                    <div class="space-y-4 py-2">
                        <div class="grid gap-1.5">
                            <Label for="create-name">Name</Label>
                            <Input id="create-name" v-model="createName" placeholder="e.g. Editor" />
                        </div>
                        <div class="grid gap-2">
                            <Label>Permissions</Label>
                            <div class="max-h-96 overflow-y-auto pr-1">
                                <RolePermissionPicker v-model="createPerms" :permissions="props.permissions" />
                            </div>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button @click="submitCreate">Create</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </template>
    </TableFilterBar>

    <RolesTable :roles="props.roles" :permissions="props.permissions" />
</template>
