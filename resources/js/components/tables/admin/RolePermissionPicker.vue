<script setup lang="ts">
import { computed, ref } from 'vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Permission } from '@/types/admin';

const props = defineProps<{
    permissions: Permission[];
    modelValue: number[];
    disabled?: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: number[]];
}>();

interface PermissionGroup {
    key: string;
    label: string;
    permissions: Permission[];
}

function titleCase(value: string) {
    return value.charAt(0).toUpperCase() + value.slice(1);
}

const search = ref('');

const groups = computed<PermissionGroup[]>(() => {
    const query = search.value.trim().toLowerCase();
    const filtered = query
        ? props.permissions.filter((permission) => permission.name.toLowerCase().includes(query))
        : props.permissions;

    const map = new Map<string, Permission[]>();

    for (const permission of filtered) {
        const [prefix] = permission.name.split('.');
        const list = map.get(prefix) ?? [];
        list.push(permission);
        map.set(prefix, list);
    }

    return [...map.entries()].map(([key, permissions]) => ({
        key,
        label: titleCase(key),
        permissions,
    }));
});

function actionLabel(permission: Permission) {
    const [, action] = permission.name.split('.');

    return action ? titleCase(action) : permission.name;
}

function isChecked(id: number) {
    return props.modelValue.includes(id);
}

function toggle(id: number) {
    emit('update:modelValue', isChecked(id)
        ? props.modelValue.filter((value) => value !== id)
        : [...props.modelValue, id]);
}

function groupState(group: PermissionGroup): boolean | 'indeterminate' {
    const checkedCount = group.permissions.filter((permission) => isChecked(permission.id)).length;

    if (checkedCount === 0) {
        return false;
    }

    return checkedCount === group.permissions.length ? true : 'indeterminate';
}

function toggleGroup(group: PermissionGroup) {
    const ids = group.permissions.map((permission) => permission.id);

    emit('update:modelValue', groupState(group) === true
        ? props.modelValue.filter((value) => !ids.includes(value))
        : [...new Set([...props.modelValue, ...ids])]);
}
</script>

<template>
    <div class="grid gap-3">
        <Input
            v-model="search"
            type="search"
            placeholder="Search permissions…"
            :disabled="disabled"
            class="sm:max-w-xs"
        />

        <p v-if="!groups.length" class="text-sm text-muted-foreground">
            No permissions match "{{ search }}".
        </p>

        <div v-else class="grid gap-3 sm:grid-cols-2">
            <div v-for="group in groups" :key="group.key" class="rounded-md border">
                <div class="flex items-center gap-2 border-b bg-muted/40 px-3 py-2">
                    <Checkbox
                        :id="`perm-group-${group.key}`"
                        :checked="groupState(group)"
                        :disabled="disabled"
                        @update:checked="toggleGroup(group)"
                    />
                    <Label :for="`perm-group-${group.key}`" class="cursor-pointer text-sm font-semibold">
                        {{ group.label }}
                    </Label>
                </div>
                <div class="grid gap-2 p-3">
                    <div v-for="permission in group.permissions" :key="permission.id" class="flex items-center gap-2">
                        <Checkbox
                            :id="`perm-${permission.id}`"
                            :checked="isChecked(permission.id)"
                            :disabled="disabled"
                            @update:checked="toggle(permission.id)"
                        />
                        <Label :for="`perm-${permission.id}`" class="cursor-pointer text-sm font-normal">
                            {{ actionLabel(permission) }}
                        </Label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
