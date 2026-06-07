<script setup lang="ts">
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import * as adminUsers from '@/routes/admin/users';
import type { Role } from '@/types/admin';
import type { User } from '@/types/auth';

type UserWithRoles = User & { roles: Role[] };

const props = defineProps<{
    users: UserWithRoles[];
    roles: Role[];
}>();

setLayoutProps({
    breadcrumbs: [{ title: 'Users', href: adminUsers.index().url }],
});

const search = ref('');

const filteredUsers = computed(() =>
    props.users.filter(
        (u) =>
            u.name.toLowerCase().includes(search.value.toLowerCase()) ||
            u.email.toLowerCase().includes(search.value.toLowerCase()),
    ),
);

const editOpen = ref(false);
const editUser = ref<UserWithRoles | null>(null);
const editRoles = ref<number[]>([]);

function openEdit(user: UserWithRoles) {
    editUser.value = user;
    editRoles.value = user.roles.map((r) => r.id);
    editOpen.value = true;
}

function submitEdit() {
    if (!editUser.value) {
        return;
    }

    router.put(adminUsers.update(editUser.value).url, { roles: editRoles.value }, {
        onSuccess: () => {
            editOpen.value = false;
        },
    });
}

function toggleRole(id: number) {
    const idx = editRoles.value.indexOf(id);

    if (idx === -1) {
        editRoles.value.push(id);
    } else {
        editRoles.value.splice(idx, 1);
    }
}
</script>

<template>
    <Head title="Users" />

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold">Users</h1>
        <Input v-model="search" placeholder="Search users…" class="w-56" />
    </div>

    <Table>
        <TableHeader>
            <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Email</TableHead>
                <TableHead>Roles</TableHead>
                <TableHead class="w-28"></TableHead>
            </TableRow>
        </TableHeader>
        <TableBody>
            <TableRow v-for="user in filteredUsers" :key="user.id">
                <TableCell class="font-medium">{{ user.name }}</TableCell>
                <TableCell class="text-muted-foreground">{{ user.email }}</TableCell>
                <TableCell>
                    <div class="flex flex-wrap gap-1">
                        <Badge v-for="role in user.roles" :key="role.id" variant="secondary" class="text-xs">
                            {{ role.name }}
                        </Badge>
                        <span v-if="user.roles.length === 0" class="text-xs text-muted-foreground">No roles</span>
                    </div>
                </TableCell>
                <TableCell class="text-right">
                    <Button variant="outline" size="sm" @click="openEdit(user)">Edit roles</Button>
                </TableCell>
            </TableRow>
            <TableRow v-if="filteredUsers.length === 0">
                <TableCell colspan="4" class="text-center text-muted-foreground py-8">No users found.</TableCell>
            </TableRow>
        </TableBody>
    </Table>

    <Dialog v-model:open="editOpen">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle>Edit roles for {{ editUser?.name }}</DialogTitle>
            </DialogHeader>
            <div class="grid gap-3 py-2">
                <div v-for="role in props.roles" :key="role.id" class="flex items-center gap-2">
                    <Checkbox
                        :id="`r-${role.id}`"
                        :checked="editRoles.includes(role.id)"
                        @update:checked="toggleRole(role.id)"
                    />
                    <Label :for="`r-${role.id}`" class="text-sm font-normal cursor-pointer">{{ role.name }}</Label>
                </div>
            </div>
            <DialogFooter>
                <Button @click="submitEdit">Save</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
