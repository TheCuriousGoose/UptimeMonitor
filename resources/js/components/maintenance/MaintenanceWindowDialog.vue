<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { SearchIcon } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import ScheduleField from '@/components/maintenance/ScheduleField.vue';
import TimezoneField from '@/components/TimezoneField.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogScrollContent,
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
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import { DEFAULT_CRON } from '@/lib/cron';
import * as maintenanceRoute from '@/routes/maintenance-windows';
import type {
    MaintenanceSchedule,
    MaintenanceWindow,
} from '@/types/maintenance';
import type { Monitor } from '@/types/monitors';

const props = defineProps<{
    window: MaintenanceWindow | null;
    monitors: Monitor[];
}>();

const open = defineModel<boolean>('open', { required: true });

const browserZone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';

type WindowForm = MaintenanceSchedule & {
    name: string;
    timezone: string;
    is_active: boolean;
    monitors: string[];
};

function blank(): WindowForm {
    return {
        name: '',
        recurrence: 'once',
        timezone: browserZone,
        starts_at: '',
        ends_at: '',
        cron: DEFAULT_CRON,
        duration_minutes: 60,
        is_active: true,
        monitors: [],
    };
}

function from(window: MaintenanceWindow): WindowForm {
    return {
        name: window.name,
        recurrence: window.recurrence,
        timezone: window.timezone,
        starts_at: window.starts_at?.slice(0, 16) ?? '',
        ends_at: window.ends_at?.slice(0, 16) ?? '',
        cron: window.cron ?? DEFAULT_CRON,
        duration_minutes: window.duration_minutes ?? 60,
        is_active: window.is_active,
        monitors: [...(window.monitors ?? [])],
    };
}

const form = ref<WindowForm>(blank());
const errors = ref<Record<string, string>>({});
const processing = ref(false);
const search = ref('');

// Remounts the schedule pickers whenever a different window is opened, so they
// re-read their state from the cron rather than keeping the last one's.
const formKey = ref(0);

// A `defineModel` object handed straight to ScheduleField: it owns the four
// scheduling keys and writes them back as one.
const schedule = computed<MaintenanceSchedule>({
    get: () => form.value,
    set: (value) => {
        form.value = { ...form.value, ...value };
    },
});

const visibleMonitors = computed(() => {
    const term = search.value.trim().toLowerCase();

    return term === ''
        ? props.monitors
        : props.monitors.filter(
              (monitor) =>
                  monitor.name.toLowerCase().includes(term) ||
                  monitor.url.toLowerCase().includes(term),
          );
});

const allVisibleSelected = computed(
    () =>
        visibleMonitors.value.length > 0 &&
        visibleMonitors.value.every((monitor) =>
            form.value.monitors.includes(monitor.uuid),
        ),
);

watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    form.value = props.window ? from(props.window) : blank();
    errors.value = {};
    search.value = '';
    formKey.value += 1;
});

function toggleMonitor(uuid: string) {
    form.value.monitors = form.value.monitors.includes(uuid)
        ? form.value.monitors.filter((value) => value !== uuid)
        : [...form.value.monitors, uuid];
}

function toggleVisible() {
    const visible = visibleMonitors.value.map((monitor) => monitor.uuid);

    form.value.monitors = allVisibleSelected.value
        ? form.value.monitors.filter((uuid) => !visible.includes(uuid))
        : [...new Set([...form.value.monitors, ...visible])];
}

function submit() {
    const options = {
        preserveScroll: true,
        onStart: () => {
            processing.value = true;
        },
        onFinish: () => {
            processing.value = false;
        },
        onSuccess: () => {
            open.value = false;
        },
        onError: (bag: Record<string, string>) => {
            errors.value = bag;
        },
    };

    if (props.window) {
        router.put(
            maintenanceRoute.update(props.window.uuid).url,
            form.value,
            options,
        );

        return;
    }

    router.post(maintenanceRoute.store().url, form.value, options);
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogScrollContent class="sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>{{
                    props.window
                        ? $t('maintenance.form.edit_title')
                        : $t('maintenance.form.create_title')
                }}</DialogTitle>
                <DialogDescription>{{
                    $t('maintenance.subtitle')
                }}</DialogDescription>
            </DialogHeader>

            <FieldGroup>
                <Field>
                    <FieldLabel for="name">{{
                        $t('maintenance.form.name')
                    }}</FieldLabel>
                    <Input
                        id="name"
                        v-model="form.name"
                        :placeholder="$t('maintenance.form.name_placeholder')"
                    />
                    <FieldError>{{ errors.name }}</FieldError>
                </Field>

                <ScheduleField
                    :key="formKey"
                    v-model="schedule"
                    :timezone="form.timezone"
                    :errors="errors"
                />

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

                    <p
                        v-if="props.monitors.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        {{ $t('maintenance.form.no_monitors') }}
                    </p>

                    <template v-else>
                        <div class="flex items-center gap-2">
                            <div class="relative flex-1">
                                <SearchIcon
                                    class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                                />
                                <Input
                                    v-model="search"
                                    class="pl-8"
                                    :placeholder="
                                        $t('maintenance.form.search_monitors')
                                    "
                                />
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                :disabled="visibleMonitors.length === 0"
                                @click="toggleVisible"
                            >
                                {{
                                    allVisibleSelected
                                        ? $t('base.clear')
                                        : $t('maintenance.form.select_all')
                                }}
                            </Button>
                        </div>

                        <div
                            class="max-h-52 divide-y overflow-y-auto rounded-sm border"
                        >
                            <label
                                v-for="monitor in visibleMonitors"
                                :key="monitor.uuid"
                                class="flex cursor-pointer items-center gap-3 px-3 py-2 transition-colors hover:bg-muted/40"
                            >
                                <Checkbox
                                    :model-value="
                                        form.monitors.includes(monitor.uuid)
                                    "
                                    @update:model-value="
                                        toggleMonitor(monitor.uuid)
                                    "
                                />
                                <span class="min-w-0">
                                    <span class="block text-sm font-medium">{{
                                        monitor.name
                                    }}</span>
                                    <span
                                        class="block truncate text-xs text-muted-foreground"
                                        >{{ monitor.url }}</span
                                    >
                                </span>
                            </label>
                            <p
                                v-if="visibleMonitors.length === 0"
                                class="px-3 py-4 text-sm text-muted-foreground"
                            >
                                {{ $t('maintenance.form.no_matches') }}
                            </p>
                        </div>

                        <FieldDescription>
                            {{
                                $t(
                                    'maintenance.silences',
                                    { count: form.monitors.length },
                                    form.monitors.length,
                                )
                            }}
                        </FieldDescription>
                    </template>
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
                <Button :disabled="processing" @click="submit">
                    <Spinner v-if="processing" />
                    {{ $t('base.save') }}
                </Button>
            </DialogFooter>
        </DialogScrollContent>
    </Dialog>
</template>
