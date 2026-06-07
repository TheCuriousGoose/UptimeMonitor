<script setup lang="ts">
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import { PencilIcon, PlusIcon, TrashIcon, UserIcon } from 'lucide-vue-next';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import * as impersonateRoute from '@/routes/admin/impersonate';
import * as rolesRoute from '@/routes/admin/roles';
import type { Permission, Role } from '@/types/admin';

const props = defineProps<{
    roles: Role[];
    permissions: Permission[];
}>();

setLayoutProps({
    breadcrumbs: [{ title: 'Roles', href: rolesRoute.index().url }],
});

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

// ---- Edit ----
const editOpen = ref(false);
const editRole = ref<Role | null>(null);
const editName = ref('');
const editPerms = ref<number[]>([]);

function openEdit(role: Role) {
    editRole.value = role;
    editName.value = role.name;
    editPerms.value = role.permissions.map((p) => p.id);
    editOpen.value = true;
}

function submitEdit() {
    if (!editRole.value) {
        return;
    }

    router.put(rolesRoute.update(editRole.value).url, { name: editName.value, permissions: editPerms.value }, {
        onSuccess: () => {
            editOpen.value = false;
        },
    });
}

// ---- Delete ----
function deleteRole(role: Role) {
    if (!confirm(`Delete role "${role.name}"?`)) {
        return;
    }

    router.delete(rolesRoute.destroy(role).url);
}

// ---- Impersonate ----
function impersonate(role: Role) {
    router.post(impersonateRoute.store(role).url);
}

function togglePerm(perms: number[], id: number) {
    const idx = perms.indexOf(id);

    if (idx === -1) {
        perms.push(id);
    } else {
        perms.splice(idx, 1);
    }
}
</script>

<template>
    <Head title="Roles" />

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold">Roles</h1>

        <Dialog v-model:open="createOpen">
            <DialogTrigger as-child>
                <Button size="sm"><PlusIcon class="size-4 mr-1.5" /> New Role</Button>
            </DialogTrigger>
            <DialogContent class="sm:max-w-md">
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
                        <div class="grid grid-cols-2 gap-2 max-h-64 overflow-y-auto pr-1">
                            <div v-for="perm in props.permissions" :key="perm.id" class="flex items-center gap-2">
                                <Checkbox
                                    :id="`cp-${perm.id}`"
                                    :checked="createPerms.includes(perm.id)"
                                    @update:checked="togglePerm(createPerms, perm.id)"
                                />
                                <Label :for="`cp-${perm.id}`" class="text-sm font-normal cursor-pointer">{{ perm.name }}</Label>
                            </div>
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button @click="submitCreate">Create</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>

    <Table>
        <TableHeader>
            <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Permissions</TableHead>
                <TableHead class="w-16 text-center">Users</TableHead>
                <TableHead class="w-36"></TableHead>
            </TableRow>
        </TableHeader>
        <TableBody>
            <TableRow v-for="role in props.roles" :key="role.id">
                <TableCell class="font-medium">{{ role.name }}</TableCell>
                <TableCell>
                    <div class="flex flex-wrap gap-1">
                        <template v-if="role.name === 'Super Admin'">
                            <span class="text-xs text-muted-foreground italic">all permissions</span>
                        </template>
                        <template v-else-if="role.permissions.length === 0">
                            <span class="text-xs text-muted-foreground">none</span>
                        </template>
                        <template v-else>
                            <Badge
                                v-for="perm in role.permissions"
                                :key="perm.id"
                                variant="secondary"
                                class="text-xs"
                            >{{ perm.name }}</Badge>
                        </template>
                    </div>
                </TableCell>
                <TableCell class="text-center text-muted-foreground text-sm">{{ role.users_count ?? 0 }}</TableCell>
                <TableCell>
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
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>

    <!-- Edit dialog -->
    <Dialog v-model:open="editOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Edit Role: {{ editRole?.name }}</DialogTitle>
            </DialogHeader>
            <div v-if="editRole" class="space-y-4 py-2">
                <div class="grid gap-1.5">
                    <Label for="edit-name">Name</Label>
                    <Input id="edit-name" v-model="editName" :disabled="editRole.name === 'Super Admin'" />
                </div>
                <div class="grid gap-2">
                    <Label>Permissions</Label>
                    <p v-if="editRole.name === 'Super Admin'" class="text-xs text-muted-foreground">
                        Super Admin has all permissions implicitly and cannot be changed.
                    </p>
                    <div v-else class="grid grid-cols-2 gap-2 max-h-64 overflow-y-auto pr-1">
                        <div v-for="perm in props.permissions" :key="perm.id" class="flex items-center gap-2">
                            <Checkbox
                                :id="`ep-${perm.id}`"
                                :checked="editPerms.includes(perm.id)"
                                @update:checked="togglePerm(editPerms, perm.id)"
                            />
                            <Label :for="`ep-${perm.id}`" class="text-sm font-normal cursor-pointer">{{ perm.name }}</Label>
                        </div>
                    </div>
                </div>
            </div>
            <DialogFooter>
                <Button :disabled="editRole?.name === 'Super Admin'" @click="submitEdit">Save</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
