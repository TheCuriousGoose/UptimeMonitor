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

        <div class="divide-y rounded-md border">
            <Collapsible
                v-for="setting in items"
                :key="setting.key"
                :open="isExpanded(setting)"
            >
                <div class="flex items-start justify-between gap-3 px-4 py-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium">{{
                                setting.label
                            }}</span>
                            <Badge variant="outline" class="font-mono text-xs">
                                {{ setting.key }}
                            </Badge>
                        </div>
                        <p
                            v-if="setting.description"
                            class="mt-0.5 text-xs text-muted-foreground"
                        >
                            {{ setting.description }}
                        </p>
                    </div>

                    <div class="flex shrink-0 items-center gap-3">
                        <Switch
                            v-if="setting.type === 'boolean'"
                            :checked="setting.value === '1'"
                            @update:checked="toggle(setting, $event)"
                        />
                        <template v-else>
                            <span class="text-sm text-muted-foreground">
                                {{ displayValue(setting) }}
                            </span>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="size-8"
                                @click="openEdit(setting)"
                            >
                                <PencilIcon class="size-3.5" />
                            </Button>
                        </template>
                    </div>
                </div>

                <CollapsibleContent v-if="setting.children?.length">
                    <div class="space-y-px border-t bg-muted/30 px-4 py-3">
                        <p
                            v-if="!isConfigured(setting)"
                            class="mb-2 text-xs text-amber-600 dark:text-amber-500"
                        >
                            {{ $t('settings.oauth.incomplete') }}
                        </p>

                        <div
                            v-for="child in setting.children"
                            :key="child.key"
                            class="flex items-center justify-between gap-3 py-1.5"
                        >
                            <div class="min-w-0">
                                <div class="text-sm">{{ child.label }}</div>
                                <p
                                    v-if="child.description"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ child.description }}
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-3">
                                <span
                                    class="font-mono text-xs text-muted-foreground"
                                >
                                    {{ displayValue(child) }}
                                </span>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="size-8"
                                    @click="openEdit(child)"
                                >
                                    <PencilIcon class="size-3.5" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </CollapsibleContent>
            </Collapsible>
        </div>
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
                <div class="grid gap-1.5">
                    <Label for="setting-value">{{
                        $t('settings.table.value')
                    }}</Label>
                    <Input
                        id="setting-value"
                        v-model="editValue"
                        :type="inputType"
                        :step="editSetting.type === 'float' ? 'any' : undefined"
                        :placeholder="
                            editSetting.type === 'secret' &&
                            editSetting.has_value
                                ? $t('settings.edit.secret_placeholder')
                                : undefined
                        "
                        autocomplete="off"
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
import { computed, nextTick, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Collapsible, CollapsibleContent } from '@/components/ui/collapsible';
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
import { trans } from '@/lib/i18n';
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

const inputType = computed(() => {
    switch (editSetting.value?.type) {
        case 'secret':
            return 'password';
        case 'integer':
        case 'float':
            return 'number';
        default:
            return 'text';
    }
});

// Children only make sense once the toggle they hang off is on.
function isExpanded(setting: AppSetting): boolean {
    return Boolean(setting.children?.length) && setting.value === '1';
}

function isConfigured(setting: AppSetting): boolean {
    return (setting.children ?? [])
        .filter(
            (child) =>
                child.key.endsWith('client_id') ||
                child.key.endsWith('client_secret'),
        )
        .every((child) => child.has_value);
}

function openEdit(setting: AppSetting) {
    editSetting.value = setting;
    editValue.value = setting.type === 'secret' ? '' : (setting.value ?? '');

    nextTick(() => {
        editOpen.value = true;
    });
}

function toggle(setting: AppSetting, enabled: boolean) {
    router.put(
        adminSettings.update(setting.key).url,
        { value: enabled ? '1' : '0' },
        { preserveScroll: true },
    );
}

function submitEdit() {
    if (!editSetting.value) {
        return;
    }

    const type = editSetting.value.type;
    const payload =
        type === 'integer'
            ? { value: parseInt(editValue.value, 10) }
            : type === 'float'
              ? { value: parseFloat(editValue.value) }
              : { value: editValue.value };

    router.put(adminSettings.update(editSetting.value.key).url, payload, {
        preserveScroll: true,
        onSuccess: () => {
            editOpen.value = false;
        },
    });
}

function displayValue(setting: AppSetting): string {
    if (setting.type === 'boolean') {
        return setting.value === '1'
            ? trans('settings.value.enabled')
            : trans('settings.value.disabled');
    }

    if (setting.type === 'secret') {
        return setting.has_value ? '••••••••' : trans('settings.value.not_set');
    }

    return setting.value || trans('settings.value.not_set');
}

const groupedSettings = computed(() =>
    props.settings.reduce(
        (acc, setting) => {
            (acc[setting.group] ??= []).push(setting);

            return acc;
        },
        {} as Record<string, AppSetting[]>,
    ),
);
</script>
