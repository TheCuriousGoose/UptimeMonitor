<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { PencilIcon, TrashIcon, UserIcon } from 'lucide-vue-next';
import { ref } from 'vue';
import RolePermissionPicker from '@/components/tables/admin/RolePermissionPicker.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import * as impersonateRoute from '@/routes/admin/impersonate';
import * as rolesRoute from '@/routes/admin/roles';
import type { Permission, Role } from '@/types/admin';

defineProps<{
    role: Role;
    allPermissions?: Permission[];
}>();

const editOpen = ref(false);
const editName = ref('');
const editPerms = ref<number[]>([]);

function openEdit(role: Role) {
    editName.value = role.name;
    editPerms.value = role.permissions.map((p) => p.id);
    editOpen.value = true;
}

function submitEdit(role: Role) {
    router.put(rolesRoute.update(role).url, { name: editName.value, permissions: editPerms.value }, {
        onSuccess: () => {
            editOpen.value = false;
        },
    });
}

function deleteRole(role: Role) {
    if (!confirm(`Delete role "${role.name}"?`)) {
        return;
    }

    router.delete(rolesRoute.destroy(role).url);
}

function impersonate(role: Role) {
    router.post(impersonateRoute.store(role).url);
}
</script>

<template>
    <div class="flex items-center gap-1 justify-end">
        <Button
            variant="ghost"
            size="icon"
            class="size-8"
            :disabled="role.name === 'Super Admin'"
            :title="role.name === 'Super Admin' ? 'Cannot impersonate Super Admin' : `Impersonate ${role.name}`"
            @click="impersonate(role)"
        >
            <UserIcon class="size-3.5" />
        </Button>
        <Button variant="ghost" size="icon" class="size-8" :title="`Edit ${role.name}`" @click="openEdit(role)">
            <PencilIcon class="size-3.5" />
        </Button>
        <Button
            variant="ghost"
            size="icon"
            class="size-8 text-destructive hover:text-destructive"
            :disabled="role.name === 'Super Admin'"
            :title="role.name === 'Super Admin' ? 'Cannot delete Super Admin' : `Delete ${role.name}`"
            @click="deleteRole(role)"
        >
            <TrashIcon class="size-3.5" />
        </Button>
    </div>

    <Dialog v-model:open="editOpen">
        <DialogContent class="sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>Edit Role: {{ role.name }}</DialogTitle>
            </DialogHeader>
            <div class="space-y-4 py-2">
                <div class="grid gap-1.5">
                    <Label for="edit-name">Name</Label>
                    <Input id="edit-name" v-model="editName" :disabled="role.name === 'Super Admin'" />
                </div>
                <div class="grid gap-2">
                    <Label>Permissions</Label>
                    <p v-if="role.name === 'Super Admin'" class="text-xs text-muted-foreground">
                        Super Admin has all permissions implicitly and cannot be changed.
                    </p>
                    <div v-else class="max-h-96 overflow-y-auto pr-1">
                        <RolePermissionPicker v-model="editPerms" :permissions="allPermissions ?? []" />
                    </div>
                </div>
            </div>
            <DialogFooter>
                <Button :disabled="role.name === 'Super Admin'" @click="submitEdit(role)">Save</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
