<script setup lang="ts">
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import { PlusIcon, WrenchIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import TimezoneField from '@/components/TimezoneField.vue';
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
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { formatDateTime, formatInterval } from '@/lib/format';
import { trans } from '@/lib/i18n';
import * as maintenanceRoute from '@/routes/maintenance-windows';
import type { Monitor } from '@/types/monitors';

type Recurrence = 'once' | 'recurring';

type MaintenanceWindow = {
    uuid: string;
    name: string;
    recurrence: Recurrence;
    timezone: string;
    starts_at: string | null;
    ends_at: string | null;
    cron: string | null;
    duration_minutes: number | null;
    is_active: boolean;
    is_active_now: boolean;
    next_occurrence_at: string | null;
    monitors?: string[];
};

const props = defineProps<{
    windows: MaintenanceWindow[];
    monitors: Monitor[];
}>();

const open = ref(false);
const editing = ref<MaintenanceWindow | null>(null);
const errors = ref<Record<string, string>>({});
const confirmOpen = ref(false);
const pendingDelete = ref<MaintenanceWindow | null>(null);

const browserZone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';

const form = ref({
    name: '',
    recurrence: 'once' as Recurrence,
    timezone: browserZone,
    starts_at: '',
    ends_at: '',
    cron: '0 2 * * 0',
    duration_minutes: 60,
    is_active: true,
    monitors: [] as string[],
});

const isRecurring = computed(() => form.value.recurrence === 'recurring');

function reset() {
    form.value = {
        name: '',
        recurrence: 'once',
        timezone: browserZone,
        starts_at: '',
        ends_at: '',
        cron: '0 2 * * 0',
        duration_minutes: 60,
        is_active: true,
        monitors: [],
    };
    errors.value = {};
}

function create() {
    editing.value = null;
    reset();
    open.value = true;
}

function edit(window: MaintenanceWindow) {
    editing.value = window;
    errors.value = {};
    form.value = {
        name: window.name,
        recurrence: window.recurrence,
        timezone: window.timezone,
        starts_at: window.starts_at?.slice(0, 16) ?? '',
        ends_at: window.ends_at?.slice(0, 16) ?? '',
        cron: window.cron ?? '0 2 * * 0',
        duration_minutes: window.duration_minutes ?? 60,
        is_active: window.is_active,
        monitors: [...(window.monitors ?? [])],
    };
    open.value = true;
}

// Pluralised in the template through the component's own $t — the standalone
// trans() helper takes no count argument.
function silencedCount(window: MaintenanceWindow) {
    return window.monitors?.length ?? 0;
}

function toggleMonitor(uuid: string) {
    form.value.monitors = form.value.monitors.includes(uuid)
        ? form.value.monitors.filter((value) => value !== uuid)
        : [...form.value.monitors, uuid];
}

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
        onError: (bag: Record<string, string>) => {
            errors.value = bag;
        },
    };

    if (editing.value) {
        router.put(
            maintenanceRoute.update(editing.value.uuid).url,
            form.value,
            options,
        );

        return;
    }

    router.post(maintenanceRoute.store().url, form.value, options);
}

function askDelete(window: MaintenanceWindow) {
    pendingDelete.value = window;
    confirmOpen.value = true;
}

function confirmDelete() {
    if (pendingDelete.value) {
        router.delete(maintenanceRoute.destroy(pendingDelete.value.uuid).url, {
            preserveScroll: true,
        });
    }
}

setLayoutProps({
    breadcrumbs: [
        { title: trans('maintenance.title'), href: maintenanceRoute.index() },
    ],
});
</script>

<template>
    <Head :title="$t('maintenance.title')" />

    <div class="flex flex-col gap-6">
        <PageHeader
            :title="$t('maintenance.title')"
            :description="$t('maintenance.subtitle')"
        >
            <template #actions>
                <Button v-can="'maintenance.create'" @click="create">
                    <PlusIcon />
                    {{ $t('maintenance.title') }}
                </Button>
            </template>
        </PageHeader>

        <EmptyState
            v-if="props.windows.length === 0"
            :icon="WrenchIcon"
            :title="$t('maintenance.empty.title')"
            :description="$t('maintenance.empty.description')"
        >
            <template #actions>
                <Button v-can="'maintenance.create'" @click="create">
                    <PlusIcon />
                    {{ $t('maintenance.title') }}
                </Button>
            </template>
        </EmptyState>

        <div v-else class="divide-y rounded-md border">
            <div
                v-for="window in props.windows"
                :key="window.uuid"
                class="flex flex-wrap items-center justify-between gap-3 px-4 py-3"
            >
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-medium">{{ window.name }}</span>
                        <Badge v-if="window.is_active_now" variant="default">
                            {{ $t('maintenance.active_now') }}
                        </Badge>
                        <Badge v-else-if="!window.is_active" variant="outline">
                            {{ $t('maintenance.paused') }}
                        </Badge>
                    </div>

                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ $t(`maintenance.recurrence.${window.recurrence}`) }}
                        ·
                        <template v-if="window.recurrence === 'recurring'">
                            <code class="font-mono">{{ window.cron }}</code>
                            <template v-if="window.duration_minutes">
                                ·
                                {{
                                    $t('maintenance.duration', {
                                        duration: formatInterval(
                                            window.duration_minutes * 60,
                                        ),
                                    })
                                }}
                            </template>
                        </template>
                        <template v-else-if="window.starts_at">
                            {{
                                $t('maintenance.window', {
                                    start: formatDateTime(window.starts_at),
                                    end: formatDateTime(window.ends_at),
                                })
                            }}
                        </template>
                        · {{ window.timezone }}
                    </p>

                    <p class="mt-0.5 text-xs text-muted-foreground">
                        {{
                            $t(
                                'maintenance.silences',
                                { count: silencedCount(window) },
                                silencedCount(window),
                            )
                        }}
                        <template v-if="window.next_occurrence_at">
                            ·
                            {{
                                $t('maintenance.next_occurrence', {
                                    time: formatDateTime(
                                        window.next_occurrence_at,
                                    ),
                                })
                            }}
                        </template>
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        v-can="'maintenance.edit'"
                        variant="outline"
                        size="sm"
                        @click="edit(window)"
                    >
                        {{ $t('monitors.actions.edit') }}
                    </Button>
                    <Button
                        v-can="'maintenance.delete'"
                        variant="ghost"
                        size="sm"
                        @click="askDelete(window)"
                    >
                        {{ $t('base.delete') }}
                    </Button>
                </div>
            </div>
        </div>
    </div>

    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{ $t('maintenance.title') }}</DialogTitle>
            </DialogHeader>

            <FieldGroup>
                <Field>
                    <FieldLabel for="name">{{
                        $t('maintenance.form.name')
                    }}</FieldLabel>
                    <Input id="name" v-model="form.name" />
                    <FieldError>{{ errors.name }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel for="recurrence">{{
                        $t('maintenance.form.cron')
                    }}</FieldLabel>
                    <Select v-model="form.recurrence">
                        <SelectTrigger id="recurrence"
                            ><SelectValue
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="once">{{
                                $t('maintenance.recurrence.once')
                            }}</SelectItem>
                            <SelectItem value="recurring">{{
                                $t('maintenance.recurrence.recurring')
                            }}</SelectItem>
                        </SelectContent>
                    </Select>
                </Field>

                <template v-if="!isRecurring">
                    <Field>
                        <FieldLabel for="starts_at">{{
                            $t('maintenance.form.starts_at')
                        }}</FieldLabel>
                        <Input
                            id="starts_at"
                            v-model="form.starts_at"
                            type="datetime-local"
                        />
                        <FieldError>{{ errors.starts_at }}</FieldError>
                    </Field>
                    <Field>
                        <FieldLabel for="ends_at">{{
                            $t('maintenance.form.ends_at')
                        }}</FieldLabel>
                        <Input
                            id="ends_at"
                            v-model="form.ends_at"
                            type="datetime-local"
                        />
                        <FieldError>{{ errors.ends_at }}</FieldError>
                    </Field>
                </template>

                <template v-else>
                    <Field>
                        <FieldLabel for="cron">{{
                            $t('maintenance.form.cron')
                        }}</FieldLabel>
                        <Input
                            id="cron"
                            v-model="form.cron"
                            class="font-mono"
                        />
                        <FieldError>{{ errors.cron }}</FieldError>
                        <FieldDescription>0 2 * * 0</FieldDescription>
                    </Field>
                    <Field>
                        <FieldLabel for="duration_minutes">{{
                            $t('maintenance.form.duration_minutes')
                        }}</FieldLabel>
                        <Input
                            id="duration_minutes"
                            v-model="form.duration_minutes"
                            type="number"
                            min="5"
                        />
                        <FieldError>{{ errors.duration_minutes }}</FieldError>
                    </Field>
                </template>

                <Field>
                    <FieldLabel for="timezone">{{
                        $t('maintenance.form.timezone')
                    }}</FieldLabel>
                    <TimezoneField id="timezone" v-model="form.timezone" />
                    <FieldError>{{ errors.timezone }}</FieldError>
                </Field>

                <Field>
                    <FieldLabel>{{
                        $t('maintenance.form.monitors')
                    }}</FieldLabel>
                    <div class="max-h-40 space-y-2 overflow-y-auto">
                        <label
                            v-for="monitor in props.monitors"
                            :key="monitor.uuid"
                            class="flex items-center gap-2 text-sm"
                        >
                            <Checkbox
                                :model-value="
                                    form.monitors.includes(monitor.uuid)
                                "
                                @update:model-value="
                                    toggleMonitor(monitor.uuid)
                                "
                            />
                            {{ monitor.name }}
                        </label>
                    </div>
                </Field>

                <Field orientation="horizontal">
                    <FieldLabel for="is_active">{{
                        $t('maintenance.form.is_active')
                    }}</FieldLabel>
                    <Switch id="is_active" v-model:checked="form.is_active" />
                </Field>
            </FieldGroup>

            <DialogFooter>
                <Button variant="outline" @click="open = false">
                    {{ $t('base.cancel') }}
                </Button>
                <Button @click="submit">{{ $t('base.save') }}</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <ConfirmDialog
        v-model:open="confirmOpen"
        :title="$t('base.delete')"
        :confirm-label="$t('base.delete')"
        destructive
        @confirm="confirmDelete"
    />
</template>
