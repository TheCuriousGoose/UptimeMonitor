<template>
    <Head :title="$t('settings.title')" />

    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-xl font-semibold">{{ $t('settings.title') }}</h1>
    </div>

    <div v-for="(items, group) in groupedSettings" :key="group" class="mb-8">
        <h2
            class="mb-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
        >
            {{ group }}
        </h2>
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead class="w-64">{{
                        $t('settings.table.setting')
                    }}</TableHead>
                    <TableHead class="w-64">{{
                        $t('settings.table.key')
                    }}</TableHead>
                    <TableHead>{{ $t('settings.table.value') }}</TableHead>
                    <TableHead class="w-16"></TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-for="setting in items" :key="setting.key">
                    <TableCell>
                        <div class="text-sm font-medium">
                            {{ setting.label }}
                        </div>
                        <div
                            v-if="setting.description"
                            class="mt-0.5 text-xs text-muted-foreground"
                        >
                            {{ setting.description }}
                        </div>
                    </TableCell>
                    <TableCell>
                        <Badge
                            variant="outline"
                            class="mt-1 font-mono text-xs"
                            >{{ setting.key }}</Badge
                        >
                    </TableCell>
                    <TableCell>
                        <span
                            :class="[
                                'text-sm',
                                setting.type === 'boolean'
                                    ? setting.value === '1'
                                        ? 'text-green-600 dark:text-green-400'
                                        : 'text-muted-foreground'
                                    : '',
                            ]"
                        >
                            {{ displayValue(setting) }}
                        </span>
                    </TableCell>
                    <TableCell>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="size-8"
                            @click="openEdit(setting)"
                        >
                            <PencilIcon class="size-3.5" />
                        </Button>
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>

    <Dialog v-model:open="editOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ $t('settings.edit.title') }}</DialogTitle>
                <DialogDescription v-if="editSetting">
                    {{ editSetting.label }}
                </DialogDescription>
            </DialogHeader>

            <div
                v-if="editSetting"
                :key="editSetting.key"
                class="space-y-4 py-2"
            >
                <div
                    v-if="editSetting.type === 'boolean'"
                    class="flex items-center justify-between"
                >
                    <Label for="setting-switch">Enabled</Label>
                    <Switch
                        id="setting-switch"
                        v-model:checked="editBooleanValue"
                    />
                </div>

                <div v-else class="grid gap-1.5">
                    <Label for="setting-value">Value</Label>
                    <Input
                        id="setting-value"
                        v-model="editValue"
                        :type="
                            editSetting.type === 'integer' ||
                            editSetting.type === 'float'
                                ? 'number'
                                : 'text'
                        "
                        :step="editSetting.type === 'float' ? 'any' : undefined"
                    />
                </div>

                <p
                    v-if="editSetting.description"
                    class="text-xs text-muted-foreground"
                >
                    {{ editSetting.description }}
                </p>
            </div>

            <DialogFooter>
                <Button variant="ghost" @click="editOpen = false">{{
                    $t('base.cancel')
                }}</Button>
                <Button @click="submitEdit">{{ $t('base.save') }}</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<script setup lang="ts">
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import { PencilIcon } from 'lucide-vue-next';
import { ref, computed, nextTick } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import * as adminSettings from '@/routes/admin/settings';
import type { AppSetting } from '@/types/admin';

defineOptions({
    inheritAttrs: false,
});

const props = defineProps<{
    settings: AppSetting[];
}>();

setLayoutProps({
    breadcrumbs: [{ title: 'Settings', href: adminSettings.index().url }],
});

const editOpen = ref(false);
const editSetting = ref<AppSetting | null>(null);
const editValue = ref<string>('');
const editBooleanValue = ref(false);

function openEdit(setting: AppSetting) {
    editSetting.value = setting;
    editValue.value = setting.value ?? '';

    // Explicitly parse string boolean representations cleanly
    editBooleanValue.value = setting.value === '1' || setting.value === 'true';

    nextTick(() => {
        editOpen.value = true;
    });
}

function submitEdit() {
    if (!editSetting.value) {
        return;
    }

    const payload =
        editSetting.value.type === 'boolean'
            ? { value: editBooleanValue.value ? '1' : '0' }
            : editSetting.value.type === 'integer'
              ? { value: parseInt(editValue.value, 10) }
              : editSetting.value.type === 'float'
                ? { value: parseFloat(editValue.value) }
                : { value: editValue.value };

    router.put(adminSettings.update(editSetting.value.key).url, payload, {
        onSuccess: () => {
            editOpen.value = false;
        },
    });
}

function displayValue(setting: AppSetting): string {
    if (setting.type === 'boolean') {
        return setting.value === '1' ? 'Enabled' : 'Disabled';
    }

    return setting.value ?? '—';
}

const groupedSettings = computed(() => {
    return props.settings.reduce(
        (acc, s) => {
            if (!acc[s.group]) {
                acc[s.group] = [];
            }

            acc[s.group].push(s);

            return acc;
        },
        {} as Record<string, AppSetting[]>,
    );
});
</script>
