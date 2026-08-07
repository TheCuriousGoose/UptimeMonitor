<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { PencilIcon, TrashIcon, UserIcon } from 'lucide-vue-next';
import { ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
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
const confirmingDelete = ref(false);

function openEdit(role: Role) {
    editName.value = role.name;
    editPerms.value = role.permissions.map((p) => p.id);
    editOpen.value = true;
}

function submitEdit(role: Role) {
    router.put(
        rolesRoute.update(role).url,
        { name: editName.value, permissions: editPerms.value },
        {
            onSuccess: () => {
                editOpen.value = false;
            },
        },
    );
}

function deleteRole(role: Role) {
    router.delete(rolesRoute.destroy(role).url);
}

function impersonate(role: Role) {
    router.post(impersonateRoute.store(role).url);
}
</script>

<template>
    <div class="flex items-center justify-end gap-1">
        <Button
            variant="ghost"
            size="icon"
            class="size-8"
            :disabled="role.name === 'Super Admin'"
            :title="
                role.name === 'Super Admin'
                    ? $t('admin.roles.cannot_impersonate')
                    : $t('admin.roles.impersonate', { name: role.name })
            "
            @click="impersonate(role)"
        >
            <UserIcon class="size-3.5" />
        </Button>
        <Button
            variant="ghost"
            size="icon"
            class="size-8"
            :title="$t('admin.roles.edit_role', { name: role.name })"
            @click="openEdit(role)"
        >
            <PencilIcon class="size-3.5" />
        </Button>
        <Button
            variant="ghost"
            size="icon"
            class="size-8 text-destructive hover:text-destructive"
            :disabled="role.name === 'Super Admin'"
            :title="
                role.name === 'Super Admin'
                    ? $t('admin.roles.cannot_delete')
                    : $t('admin.roles.delete_role', { name: role.name })
            "
            @click="confirmingDelete = true"
        >
            <TrashIcon class="size-3.5" />
        </Button>
    </div>

    <ConfirmDialog
        v-model:open="confirmingDelete"
        :title="$t('admin.roles.confirm_delete', { name: role.name })"
        :description="$t('admin.roles.confirm_delete_description')"
        :confirm-label="$t('base.delete')"
        destructive
        @confirm="deleteRole(role)"
    />

    <Dialog v-model:open="editOpen">
        <DialogContent class="sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>{{
                    $t('admin.roles.edit_role', { name: role.name })
                }}</DialogTitle>
            </DialogHeader>
            <div class="space-y-4 py-2">
                <div class="grid gap-1.5">
                    <Label for="edit-name">{{ $t('admin.roles.name') }}</Label>
                    <Input
                        id="edit-name"
                        v-model="editName"
                        :disabled="role.name === 'Super Admin'"
                    />
                </div>
                <div class="grid gap-2">
                    <Label>{{ $t('admin.roles.permissions') }}</Label>
                    <p
                        v-if="role.name === 'Super Admin'"
                        class="text-xs text-muted-foreground"
                    >
                        {{ $t('admin.roles.super_admin_locked') }}
                    </p>
                    <div v-else class="max-h-96 overflow-y-auto pr-1">
                        <RolePermissionPicker
                            v-model="editPerms"
                            :permissions="allPermissions ?? []"
                        />
                    </div>
                </div>
            </div>
            <DialogFooter>
                <Button
                    :disabled="role.name === 'Super Admin'"
                    @click="submitEdit(role)"
                    >{{ $t('admin.roles.save') }}</Button
                >
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
