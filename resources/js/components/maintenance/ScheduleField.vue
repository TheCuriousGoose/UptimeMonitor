<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import {
    Field,
    FieldDescription,
    FieldError,
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
import {
    buildCron,
    DEFAULT_CRON,
    describeSchedule,
    isCronShaped,
    parseCron,
    weekdayLabel,
} from '@/lib/cron';
import type { ScheduleMode } from '@/lib/cron';
import { formatDuration } from '@/lib/format';
import type { MaintenanceSchedule } from '@/types/maintenance';

type Mode = 'once' | ScheduleMode;

const props = defineProps<{
    timezone: string;
    errors: Record<string, string>;
}>();

const model = defineModel<MaintenanceSchedule>({ required: true });

const durationPresets = [15, 30, 60, 120, 240, 480, 1440];
const allWeekdays = [0, 1, 2, 3, 4, 5, 6];

// Everything but "once" is stored as a recurring window; the mode only decides
// which shape of cron gets written.
const initial = parseCron(model.value.cron || DEFAULT_CRON);

const mode = ref<Mode>(
    model.value.recurrence === 'once' ? 'once' : initial.mode,
);
const startsAt = ref(model.value.starts_at);
const endsAt = ref(model.value.ends_at);
const time = ref(initial.time);
const weekdays = ref<number[]>([...initial.weekdays]);
const dayOfMonth = ref(initial.dayOfMonth);
const expression = ref(initial.expression);

const initialDuration = model.value.duration_minutes || 60;
const durationOption = ref<number | 'custom'>(
    durationPresets.includes(initialDuration) ? initialDuration : 'custom',
);
const durationCustom = ref<number | string>(
    durationPresets.includes(initialDuration) ? '' : initialDuration,
);

const isRecurring = computed(() => mode.value !== 'once');
const isCustom = computed(() => mode.value === 'custom');
const isWeekly = computed(() => mode.value === 'weekly');
const isMonthly = computed(() => mode.value === 'monthly');

const schedule = computed(() => ({
    mode: isRecurring.value ? (mode.value as ScheduleMode) : 'custom',
    time: time.value,
    weekdays: weekdays.value,
    dayOfMonth: Number(dayOfMonth.value) || 1,
    expression: expression.value,
}));

const durationValue = computed(() =>
    durationOption.value === 'custom'
        ? Number(durationCustom.value) || 0
        : durationOption.value,
);

const cronLooksWrong = computed(
    () =>
        isCustom.value &&
        expression.value.trim() !== '' &&
        !isCronShaped(expression.value),
);

const noWeekdays = computed(
    () => isWeekly.value && weekdays.value.length === 0,
);

/**
 * The plain-English line under the pickers, and the whole point of the screen:
 * whatever you tick, you read back the sentence you meant.
 */
const summary = computed(() => {
    if (!isRecurring.value || cronLooksWrong.value || noWeekdays.value) {
        return null;
    }

    return describeSchedule(schedule.value);
});

// A one-off window carries its length in the two dates rather than a duration.
const onceDuration = computed(() => {
    const start = Date.parse(startsAt.value);
    const end = Date.parse(endsAt.value);

    if (Number.isNaN(start) || Number.isNaN(end) || end <= start) {
        return null;
    }

    return formatDuration((end - start) / 1000);
});

function toggleWeekday(day: number) {
    weekdays.value = weekdays.value.includes(day)
        ? weekdays.value.filter((value) => value !== day)
        : [...weekdays.value, day];
}

// Leaving a hand-written expression should not carry its text into the cron the
// pickers build next, so re-read the pickers from whatever it said.
watch(mode, (next, previous) => {
    if (previous === 'custom' && next !== 'once' && next !== 'custom') {
        const parsed = parseCron(expression.value);

        time.value = parsed.time;
        weekdays.value = [...parsed.weekdays];
        dayOfMonth.value = parsed.dayOfMonth;
    }
});

watch(
    [schedule, durationValue, startsAt, endsAt],
    () => {
        model.value = {
            recurrence: isRecurring.value ? 'recurring' : 'once',
            starts_at: startsAt.value,
            ends_at: endsAt.value,
            cron: isRecurring.value
                ? buildCron(schedule.value)
                : model.value.cron,
            duration_minutes: durationValue.value,
        };
    },
    { immediate: true, deep: true },
);
</script>

<template>
    <div class="flex flex-col gap-4">
        <Field>
            <FieldLabel for="recurrence">{{
                $t('maintenance.schedule.title')
            }}</FieldLabel>
            <Select v-model="mode">
                <SelectTrigger id="recurrence"><SelectValue /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="once">{{
                        $t('maintenance.schedule.modes.once')
                    }}</SelectItem>
                    <SelectItem value="daily">{{
                        $t('maintenance.schedule.modes.daily')
                    }}</SelectItem>
                    <SelectItem value="weekly">{{
                        $t('maintenance.schedule.modes.weekly')
                    }}</SelectItem>
                    <SelectItem value="monthly">{{
                        $t('maintenance.schedule.modes.monthly')
                    }}</SelectItem>
                    <SelectItem value="custom">{{
                        $t('maintenance.schedule.modes.custom')
                    }}</SelectItem>
                </SelectContent>
            </Select>
            <FieldDescription>{{
                $t('maintenance.schedule.description')
            }}</FieldDescription>
        </Field>

        <div v-if="!isRecurring" class="grid gap-4 sm:grid-cols-2">
            <Field>
                <FieldLabel for="starts_at">{{
                    $t('maintenance.form.starts_at')
                }}</FieldLabel>
                <Input
                    id="starts_at"
                    v-model="startsAt"
                    type="datetime-local"
                />
                <FieldError>{{ props.errors.starts_at }}</FieldError>
            </Field>
            <Field>
                <FieldLabel for="ends_at">{{
                    $t('maintenance.form.ends_at')
                }}</FieldLabel>
                <Input id="ends_at" v-model="endsAt" type="datetime-local" />
                <FieldError>{{ props.errors.ends_at }}</FieldError>
            </Field>
        </div>

        <template v-else>
            <Field v-if="isCustom">
                <FieldLabel for="cron">{{
                    $t('maintenance.schedule.cron')
                }}</FieldLabel>
                <Input id="cron" v-model="expression" class="font-mono" />
                <FieldError>{{
                    cronLooksWrong
                        ? $t('maintenance.schedule.cron_invalid')
                        : props.errors.cron
                }}</FieldError>
                <FieldDescription>{{
                    $t('maintenance.schedule.cron_description')
                }}</FieldDescription>
            </Field>

            <Field v-if="isWeekly">
                <FieldLabel>{{
                    $t('maintenance.schedule.weekdays')
                }}</FieldLabel>
                <div class="flex flex-wrap gap-1.5">
                    <button
                        v-for="day in allWeekdays"
                        :key="day"
                        type="button"
                        class="min-w-12 rounded-sm border px-2.5 py-1.5 text-xs font-medium transition-colors hover:bg-accent focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                        :class="
                            weekdays.includes(day)
                                ? 'border-primary bg-primary/10 text-foreground'
                                : 'text-muted-foreground'
                        "
                        :aria-pressed="weekdays.includes(day)"
                        @click="toggleWeekday(day)"
                    >
                        {{ weekdayLabel(day, 'short') }}
                    </button>
                </div>
                <FieldError v-if="noWeekdays">{{
                    $t('maintenance.schedule.weekdays_required')
                }}</FieldError>
            </Field>

            <div class="grid gap-4 sm:grid-cols-2">
                <Field v-if="!isCustom">
                    <FieldLabel for="time">{{
                        $t('maintenance.schedule.time')
                    }}</FieldLabel>
                    <Input id="time" v-model="time" type="time" />
                </Field>

                <Field v-if="isMonthly">
                    <FieldLabel for="day_of_month">{{
                        $t('maintenance.schedule.day_of_month')
                    }}</FieldLabel>
                    <Input
                        id="day_of_month"
                        v-model="dayOfMonth"
                        type="number"
                        min="1"
                        max="31"
                    />
                    <FieldDescription>{{
                        $t('maintenance.schedule.day_of_month_description')
                    }}</FieldDescription>
                </Field>

                <Field>
                    <FieldLabel for="duration">{{
                        $t('maintenance.form.duration.title')
                    }}</FieldLabel>
                    <Select v-model="durationOption">
                        <SelectTrigger id="duration"
                            ><SelectValue
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="preset in durationPresets"
                                :key="preset"
                                :value="preset"
                            >
                                {{
                                    $t(
                                        `maintenance.form.duration.options.${preset}`,
                                    )
                                }}
                            </SelectItem>
                            <SelectItem value="custom">{{
                                $t('maintenance.form.duration.custom')
                            }}</SelectItem>
                        </SelectContent>
                    </Select>
                    <Input
                        v-if="durationOption === 'custom'"
                        v-model="durationCustom"
                        type="number"
                        min="5"
                        max="1440"
                        :placeholder="
                            $t('maintenance.form.duration.custom_placeholder')
                        "
                    />
                    <FieldError>{{ props.errors.duration_minutes }}</FieldError>
                </Field>
            </div>
        </template>

        <p
            v-if="summary"
            class="rounded-sm bg-muted/50 px-3 py-2 text-sm text-muted-foreground"
        >
            {{ summary }} ·
            {{
                $t('maintenance.duration', {
                    duration: formatDuration(durationValue * 60),
                })
            }}
            · {{ props.timezone }}
        </p>
        <p
            v-else-if="onceDuration"
            class="rounded-sm bg-muted/50 px-3 py-2 text-sm text-muted-foreground"
        >
            {{ $t('maintenance.duration', { duration: onceDuration }) }}
        </p>
    </div>
</template>
