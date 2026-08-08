<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    ArrowRightIcon,
    BellIcon,
    CheckCircle2Icon,
    CheckIcon,
    FileSearchIcon,
    GlobeIcon,
    MailIcon,
    NetworkIcon,
    PlugIcon,
    RadioIcon,
    ShieldCheckIcon,
    XCircleIcon,
} from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import SetupStep from '@/components/onboarding/SetupStep.vue';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { formatInterval, formatResponseMs } from '@/lib/format';
import { csrfHeaders } from '@/lib/http';
import { tList, trans } from '@/lib/i18n';
import * as monitorsRoute from '@/routes/monitors';
import * as onboardingRoute from '@/routes/onboarding';
import type {
    MonitorType,
    MonitorTypeOptions,
    NotificationChannel,
} from '@/types/monitors';
import type { OnboardingProgress } from '@/types/onboarding';

const props = defineProps<{
    types: MonitorType[];
    typeOptions: MonitorTypeOptions;
    channels: NotificationChannel[];
    suggestedEmail: string;
    progress: OnboardingProgress;
}>();

const page = usePage();
const appName = computed(() => (page.props.name as string) ?? 'Vigil Watch');

type Step = 'welcome' | 'target' | 'test' | 'schedule' | 'alerts' | 'done';

/** The four the progress rail counts; welcome and done bookend them. */
const rail: Step[] = ['target', 'test', 'schedule', 'alerts'];

const typeIcons: Record<MonitorType, unknown> = {
    http: GlobeIcon,
    keyword: FileSearchIcon,
    port: PlugIcon,
    ping: RadioIcon,
    dns: NetworkIcon,
    ssl: ShieldCheckIcon,
};

// The same presets the ordinary form offers, so choosing the guided route
// never means settling for fewer options than the manual one.
const intervalChoices = [
    { value: 30, label: '30s' },
    { value: 60, label: '1m' },
    { value: 300, label: '5m', recommended: true },
    { value: 600, label: '10m' },
    { value: 1800, label: '30m' },
    { value: 3600, label: '1h' },
];

const thresholdChoices = [1, 2, 3, 5];
const recordTypes = computed(() => props.typeOptions.record_types);

const welcomePoints = tList('onboarding.setup.welcome.points');

const step = ref<Step>('welcome');
const type = ref<MonitorType>('http');
const url = ref('');
const name = ref('');
const interval = ref<number | 'custom'>(300);
const intervalCustom = ref<number | string>('');
const confirmation = ref(2);
const recovery = ref(1);

// Type-specific settings. Three of the six types have a required config field,
// so without these the wizard could offer a check it could not then save.
const keyword = ref('');
const invert = ref(false);
const port = ref<number | string>(443);
const recordType = ref('A');
const expected = ref('');
const warnDays = ref<number | string>(14);
const alertChoice = ref<'email' | 'existing' | 'none'>(
    props.channels.length > 0 ? 'existing' : 'email',
);
const selectedChannels = ref<string[]>(
    props.channels.length > 0 ? [props.channels[0].uuid] : [],
);
const errors = ref<Record<string, string>>({});
const saving = ref(false);

const expectsUrl = computed(() =>
    props.typeOptions.url_types.includes(type.value),
);

const railIndex = computed(() => rail.indexOf(step.value));

// Someone who has already connected something should not be told to add email.
const hasChannels = computed(() => props.channels.length > 0);

/**
 * A name nobody asked for beats an empty required field: "example.com" is what
 * they would have typed. Only fills in while untouched.
 */
const nameTouched = ref(false);

const suggestedName = computed(() => {
    const target = url.value.trim();

    if (target === '') {
        return '';
    }

    return target
        .replace(/^[a-z]+:\/\//i, '')
        .replace(/\/.*$/, '')
        .replace(/^www\./i, '');
});

watch(suggestedName, (next) => {
    if (!nameTouched.value) {
        name.value = next;
    }
});

const intervalValue = computed(() =>
    interval.value === 'custom'
        ? Number(intervalCustom.value) || 0
        : interval.value,
);

/** Only the keys the chosen type actually uses; the server drops the rest. */
const config = computed(() => {
    const values: Record<string, string> = {};

    if (type.value === 'keyword') {
        values.keyword = keyword.value;
        values.invert = invert.value ? '1' : '0';
    }

    if (type.value === 'port') {
        values.port = String(port.value);
    }

    if (type.value === 'dns') {
        values.record_type = recordType.value;
        values.expected = expected.value;
    }

    if (type.value === 'ssl') {
        values.warn_days = String(warnDays.value);
    }

    return values;
});

const canLeaveTarget = computed(() => {
    if (url.value.trim() === '' || name.value.trim() === '') {
        return false;
    }

    // Mirrors the required config rules, so Continue never leads to a 422 for
    // a field this step did not ask about.
    if (type.value === 'keyword') {
        return keyword.value.trim() !== '';
    }

    if (type.value === 'port') {
        return Number(port.value) > 0;
    }

    return true;
});

// ---------------------------------------------------------------- live test

type TestResult = {
    is_up: boolean;
    response_ms: number;
    error: string | null;
    status_code?: number | null;
};

const testing = ref(false);
const testResult = ref<TestResult | null>(null);
let advanceTimer: ReturnType<typeof setTimeout> | null = null;

function clearAdvance() {
    if (advanceTimer) {
        clearTimeout(advanceTimer);
        advanceTimer = null;
    }
}

onBeforeUnmount(clearAdvance);

/**
 * The moment the whole flow is built around: it runs the real check, through
 * the same endpoint the monitor form's test button uses, before anything has
 * been saved.
 */
async function runTest() {
    clearAdvance();
    testing.value = true;
    testResult.value = null;
    errors.value = {};

    const body = new FormData();
    body.set('type', type.value);
    body.set('url', url.value.trim());
    body.set('timeout', '10');

    for (const [key, value] of Object.entries(config.value)) {
        body.set(`config[${key}]`, value);
    }

    try {
        const response = await fetch(monitorsRoute.preview().url, {
            method: 'POST',
            headers: { Accept: 'application/json', ...csrfHeaders() },
            body,
        });

        if (response.status === 422) {
            const payload = (await response.json()) as {
                errors?: Record<string, string[]>;
            };

            errors.value = Object.fromEntries(
                Object.entries(payload.errors ?? {}).map(([key, messages]) => [
                    key,
                    messages[0],
                ]),
            );
            step.value = 'target';

            return;
        }

        testResult.value = response.ok
            ? ((await response.json()) as TestResult)
            : {
                  is_up: false,
                  response_ms: 0,
                  error: trans('monitors.preview.failed'),
              };

        // Carry them onward on success rather than making them confirm that
        // yes, the thing that just worked, worked.
        if (testResult.value.is_up) {
            advanceTimer = setTimeout(() => go('schedule'), 1600);
        }
    } catch {
        testResult.value = {
            is_up: false,
            response_ms: 0,
            error: trans('monitors.preview.failed'),
        };
    } finally {
        testing.value = false;
    }
}

// ------------------------------------------------------------- step control

function go(next: Step) {
    clearAdvance();
    step.value = next;

    if (next === 'test') {
        runTest();
    }

    // Landing straight in the field they came back to fix keeps the flow
    // moving without a stray click. Queried rather than reffed: Input wraps
    // the element and does not forward a ref to it.
    if (next === 'target') {
        nextTick(() => document.getElementById('url')?.focus());
    }
}

function back() {
    const order: Step[] = ['welcome', 'target', 'test', 'schedule', 'alerts'];
    const index = order.indexOf(step.value);

    go(order[Math.max(0, index - 1)]);
}

function onTargetSubmit() {
    if (canLeaveTarget.value) {
        go('test');
    }
}

function skip() {
    router.post(onboardingRoute.skip().url);
}

function finish() {
    saving.value = true;

    router.post(
        onboardingRoute.store().url,
        {
            name: name.value.trim(),
            url: url.value.trim(),
            type: type.value,
            timeout: 10,
            interval_seconds: intervalValue.value,
            confirmation_threshold: confirmation.value,
            recovery_threshold: recovery.value,
            is_active: true,
            config: config.value,
            alert_email:
                alertChoice.value === 'email' ? props.suggestedEmail : null,
            notification_channels:
                alertChoice.value === 'existing' ? selectedChannels.value : [],
        },
        {
            onError: (bag) => {
                errors.value = bag as Record<string, string>;
                saving.value = false;
                // Validation can only be about the fields the first step owns.
                step.value = 'target';
            },
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}

function toggleChannel(uuid: string) {
    selectedChannels.value = selectedChannels.value.includes(uuid)
        ? selectedChannels.value.filter((value) => value !== uuid)
        : [...selectedChannels.value, uuid];
}

const alertsSummary = computed(() => {
    if (alertChoice.value === 'email') {
        return props.suggestedEmail;
    }

    if (alertChoice.value === 'existing' && selectedChannels.value.length > 0) {
        return props.channels
            .filter((channel) => selectedChannels.value.includes(channel.uuid))
            .map((channel) => channel.name)
            .join(', ');
    }

    return trans('onboarding.setup.done.summary.no_alerts');
});
</script>

<template>
    <Head :title="$t('onboarding.setup.page_title')" />

    <div class="mx-auto flex min-h-svh max-w-2xl flex-col px-4 py-8 sm:py-14">
        <div class="mb-10 flex items-center gap-2.5">
            <span
                class="flex size-7 items-center justify-center rounded-sm border border-primary/40 bg-primary/10 text-primary"
            >
                <AppLogoIcon class="size-4" />
            </span>
            <span class="text-sm font-semibold tracking-tight">{{
                appName
            }}</span>
        </div>

        <!-- Progress rail. Present from the first real step so the length of
             the flow is never a surprise. -->
        <div
            v-if="railIndex >= 0"
            class="mb-10"
            role="group"
            :aria-label="
                $t('onboarding.setup.step_of', {
                    current: railIndex + 1,
                    total: rail.length,
                })
            "
        >
            <ol class="flex items-center gap-2">
                <!-- Fixed-width connectors rather than stretched ones: the
                     step labels differ in length, so flexing the lines left
                     them visibly uneven. -->
                <li
                    v-for="(item, index) in rail"
                    :key="item"
                    class="flex items-center gap-2"
                >
                    <span
                        class="flex size-6 shrink-0 items-center justify-center rounded-full border text-xs font-medium transition-colors"
                        :class="
                            index < railIndex
                                ? 'border-primary bg-primary text-primary-foreground'
                                : index === railIndex
                                  ? 'border-primary text-primary'
                                  : 'text-muted-foreground/60'
                        "
                        :aria-current="index === railIndex ? 'step' : undefined"
                    >
                        <CheckIcon v-if="index < railIndex" class="size-3" />
                        <template v-else>{{ index + 1 }}</template>
                    </span>
                    <span
                        class="hidden text-xs sm:block"
                        :class="
                            index === railIndex
                                ? 'font-medium text-foreground'
                                : 'text-muted-foreground'
                        "
                    >
                        {{ $t(`onboarding.setup.rail.${item}`) }}
                    </span>
                    <span
                        v-if="index < rail.length - 1"
                        class="h-px w-4 transition-colors sm:w-8"
                        :class="index < railIndex ? 'bg-primary' : 'bg-border'"
                    />
                </li>
            </ol>
        </div>

        <div class="flex-1">
            <!-- 0. Welcome -->
            <SetupStep
                v-if="step === 'welcome'"
                :title="$t('onboarding.setup.welcome.title')"
                :description="$t('onboarding.setup.welcome.description')"
            >
                <ol class="flex flex-col gap-3">
                    <li
                        v-for="(point, index) in welcomePoints"
                        :key="point"
                        class="flex items-start gap-3 text-sm"
                    >
                        <span
                            class="flex size-6 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-medium"
                        >
                            {{ index + 1 }}
                        </span>
                        <span class="pt-0.5 text-muted-foreground">
                            {{ point }}
                        </span>
                    </li>
                </ol>

                <p class="mt-6 text-xs text-muted-foreground">
                    {{ $t('onboarding.setup.welcome.reassure') }}
                </p>

                <div class="mt-8 flex items-center gap-3">
                    <Button size="lg" @click="go('target')">
                        {{ $t('onboarding.setup.welcome.start') }}
                        <ArrowRightIcon />
                    </Button>
                    <Button variant="ghost" @click="skip">
                        {{ $t('onboarding.setup.skip') }}
                    </Button>
                </div>
            </SetupStep>

            <!-- 1. What to watch -->
            <SetupStep
                v-else-if="step === 'target'"
                :title="$t('onboarding.setup.target.title')"
                :description="$t('onboarding.setup.target.description')"
            >
                <form class="space-y-6" @submit.prevent="onTargetSubmit">
                    <div class="grid gap-2 sm:grid-cols-2">
                        <button
                            v-for="option in types"
                            :key="option"
                            type="button"
                            class="rounded-sm border p-3 text-left transition-colors hover:bg-accent focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                            :class="
                                type === option
                                    ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                    : ''
                            "
                            :aria-pressed="type === option"
                            @click="type = option"
                        >
                            <span
                                class="flex items-center gap-2 text-sm font-medium"
                            >
                                <component
                                    :is="typeIcons[option]"
                                    class="size-4 shrink-0"
                                />
                                {{ $t(`monitors.form.type.options.${option}`) }}
                            </span>
                            <span
                                class="mt-1 block text-xs text-muted-foreground"
                            >
                                {{ $t(`monitors.form.type.hints.${option}`) }}
                            </span>
                        </button>
                    </div>

                    <Field>
                        <FieldLabel for="url">
                            {{
                                expectsUrl
                                    ? $t('monitors.form.url.title')
                                    : $t('monitors.form.url.host_title')
                            }}
                        </FieldLabel>
                        <Input
                            id="url"
                            v-model="url"
                            autocomplete="off"
                            autofocus
                            :placeholder="
                                expectsUrl
                                    ? $t('monitors.form.url.placeholder')
                                    : $t('monitors.form.url.host_placeholder')
                            "
                        />
                        <FieldError>{{ errors.url }}</FieldError>
                        <FieldDescription>{{
                            $t('onboarding.setup.target.url_hint')
                        }}</FieldDescription>
                    </Field>

                    <!-- Whatever else the chosen check needs to run at all.
                         Offering the type card without these would lead
                         straight to a rejection with nothing to fix. -->
                    <template v-if="type === 'keyword'">
                        <Field>
                            <FieldLabel for="keyword">{{
                                $t('monitors.form.config.keyword.title')
                            }}</FieldLabel>
                            <Input
                                id="keyword"
                                v-model="keyword"
                                autocomplete="off"
                                :placeholder="
                                    $t(
                                        'monitors.form.config.keyword.placeholder',
                                    )
                                "
                            />
                            <FieldError>{{
                                errors['config.keyword']
                            }}</FieldError>
                            <FieldDescription>{{
                                $t('monitors.form.config.keyword.description')
                            }}</FieldDescription>
                        </Field>
                        <Field orientation="horizontal">
                            <FieldContent>
                                <FieldLabel for="invert">{{
                                    $t('monitors.form.config.invert.title')
                                }}</FieldLabel>
                                <FieldDescription>{{
                                    $t(
                                        'monitors.form.config.invert.description',
                                    )
                                }}</FieldDescription>
                            </FieldContent>
                            <Switch id="invert" v-model:checked="invert" />
                        </Field>
                    </template>

                    <Field v-if="type === 'port'">
                        <FieldLabel for="port">{{
                            $t('monitors.form.config.port.title')
                        }}</FieldLabel>
                        <Input
                            id="port"
                            v-model="port"
                            type="number"
                            min="1"
                            max="65535"
                        />
                        <FieldError>{{ errors['config.port'] }}</FieldError>
                        <FieldDescription>{{
                            $t('monitors.form.config.port.description')
                        }}</FieldDescription>
                    </Field>

                    <template v-if="type === 'dns'">
                        <Field>
                            <FieldLabel for="record_type">{{
                                $t('monitors.form.config.record_type.title')
                            }}</FieldLabel>
                            <Select v-model="recordType">
                                <SelectTrigger id="record_type"
                                    ><SelectValue
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="record in recordTypes"
                                        :key="record"
                                        :value="record"
                                    >
                                        {{ record }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <FieldError>{{
                                errors['config.record_type']
                            }}</FieldError>
                        </Field>
                        <Field>
                            <FieldLabel for="expected">{{
                                $t('monitors.form.config.expected.title')
                            }}</FieldLabel>
                            <Input
                                id="expected"
                                v-model="expected"
                                autocomplete="off"
                                :placeholder="
                                    $t(
                                        'monitors.form.config.expected.placeholder',
                                    )
                                "
                            />
                            <FieldError>{{
                                errors['config.expected']
                            }}</FieldError>
                            <FieldDescription>{{
                                $t('monitors.form.config.expected.description')
                            }}</FieldDescription>
                        </Field>
                    </template>

                    <Field v-if="type === 'ssl'">
                        <FieldLabel for="warn_days">{{
                            $t('monitors.form.config.warn_days.title')
                        }}</FieldLabel>
                        <Input
                            id="warn_days"
                            v-model="warnDays"
                            type="number"
                            min="1"
                            max="365"
                        />
                        <FieldError>{{
                            errors['config.warn_days']
                        }}</FieldError>
                        <FieldDescription>{{
                            $t('monitors.form.config.warn_days.description')
                        }}</FieldDescription>
                    </Field>

                    <Field>
                        <FieldLabel for="name">{{
                            $t('onboarding.setup.target.name_label')
                        }}</FieldLabel>
                        <Input
                            id="name"
                            v-model="name"
                            autocomplete="off"
                            :placeholder="$t('monitors.form.name.placeholder')"
                            @input="nameTouched = true"
                        />
                        <FieldError>{{ errors.name }}</FieldError>
                        <FieldDescription>{{
                            $t('onboarding.setup.target.name_hint')
                        }}</FieldDescription>
                    </Field>

                    <div class="flex items-center gap-3">
                        <Button
                            type="submit"
                            size="lg"
                            :disabled="!canLeaveTarget"
                        >
                            {{ $t('onboarding.setup.continue') }}
                            <ArrowRightIcon />
                        </Button>
                        <Button variant="ghost" type="button" @click="back">
                            <ArrowLeftIcon />
                            {{ $t('onboarding.setup.back') }}
                        </Button>
                    </div>
                </form>
            </SetupStep>

            <!-- 2. Live test -->
            <SetupStep
                v-else-if="step === 'test'"
                :title="$t('onboarding.setup.test.title')"
                :description="$t('onboarding.setup.test.description')"
            >
                <div
                    v-if="testing"
                    class="flex items-center gap-3 rounded-md border p-4 text-sm"
                >
                    <Spinner />
                    <span class="text-muted-foreground">
                        {{
                            $t('onboarding.setup.test.running', {
                                target: url,
                            })
                        }}
                    </span>
                </div>

                <div
                    v-else-if="testResult"
                    class="rounded-md border p-4"
                    :class="
                        testResult.is_up
                            ? 'border-emerald-600/30 bg-emerald-500/5'
                            : 'border-destructive/30 bg-destructive/5'
                    "
                >
                    <div class="flex items-start gap-3">
                        <component
                            :is="
                                testResult.is_up
                                    ? CheckCircle2Icon
                                    : XCircleIcon
                            "
                            class="mt-0.5 size-5 shrink-0"
                            :class="
                                testResult.is_up
                                    ? 'text-emerald-700 dark:text-emerald-400'
                                    : 'text-destructive'
                            "
                            aria-hidden="true"
                        />
                        <div class="min-w-0">
                            <p class="font-medium">
                                {{
                                    testResult.is_up
                                        ? $t(
                                              'onboarding.setup.test.success_title',
                                          )
                                        : $t(
                                              'onboarding.setup.test.failure_title',
                                          )
                                }}
                            </p>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{
                                    testResult.is_up
                                        ? $t(
                                              'onboarding.setup.test.success_body',
                                              {
                                                  duration: formatResponseMs(
                                                      testResult.response_ms,
                                                  ),
                                              },
                                          )
                                        : $t(
                                              'onboarding.setup.test.failure_body',
                                          )
                                }}
                            </p>
                            <p
                                v-if="testResult.error"
                                class="mt-2 font-mono text-xs text-muted-foreground"
                            >
                                {{ testResult.error }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <template v-if="testResult && !testResult.is_up">
                        <Button size="lg" @click="go('target')">
                            {{ $t('onboarding.setup.test.edit') }}
                        </Button>
                        <Button variant="outline" @click="runTest">
                            {{ $t('onboarding.setup.test.retry') }}
                        </Button>
                        <Button variant="ghost" @click="go('schedule')">
                            {{ $t('onboarding.setup.test.continue_anyway') }}
                        </Button>
                    </template>
                    <p
                        v-else-if="testResult"
                        class="text-sm text-muted-foreground"
                    >
                        {{ $t('onboarding.setup.test.auto_advance') }}
                    </p>
                </div>
            </SetupStep>

            <!-- 3. Schedule -->
            <SetupStep
                v-else-if="step === 'schedule'"
                :title="$t('onboarding.setup.schedule.title')"
                :description="$t('onboarding.setup.schedule.description')"
            >
                <div class="space-y-8">
                    <div>
                        <div class="grid gap-2 sm:grid-cols-3">
                            <button
                                v-for="choice in intervalChoices"
                                :key="choice.value"
                                type="button"
                                class="rounded-sm border p-3 text-left transition-colors hover:bg-accent focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                                :class="
                                    interval === choice.value
                                        ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                        : ''
                                "
                                :aria-pressed="interval === choice.value"
                                @click="interval = choice.value"
                            >
                                <span class="block text-sm font-medium">
                                    {{
                                        $t(
                                            `monitors.form.check_interval.options.${choice.label}`,
                                        )
                                    }}
                                </span>
                                <span
                                    v-if="choice.recommended"
                                    class="mt-1 block text-xs text-primary"
                                >
                                    {{
                                        $t(
                                            'onboarding.setup.schedule.recommended',
                                        )
                                    }}
                                </span>
                            </button>
                            <button
                                type="button"
                                class="rounded-sm border p-3 text-left text-sm font-medium transition-colors hover:bg-accent focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                                :class="
                                    interval === 'custom'
                                        ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                        : ''
                                "
                                :aria-pressed="interval === 'custom'"
                                @click="interval = 'custom'"
                            >
                                {{ $t('monitors.form.custom') }}
                            </button>
                        </div>
                        <Input
                            v-if="interval === 'custom'"
                            v-model="intervalCustom"
                            type="number"
                            min="30"
                            class="mt-2 sm:w-64"
                            :placeholder="
                                $t(
                                    'monitors.form.check_interval.custom_placeholder',
                                )
                            "
                        />
                        <FieldError>{{ errors.interval_seconds }}</FieldError>
                        <p class="mt-2 text-xs text-muted-foreground">
                            {{ $t('onboarding.setup.schedule.interval_hint') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-medium">
                            {{ $t('onboarding.setup.schedule.confirm_label') }}
                        </p>
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            <button
                                v-for="value in thresholdChoices"
                                :key="value"
                                type="button"
                                class="rounded-sm border p-3 text-left text-sm transition-colors hover:bg-accent focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                                :class="
                                    confirmation === value
                                        ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                        : ''
                                "
                                :aria-pressed="confirmation === value"
                                @click="confirmation = value"
                            >
                                {{
                                    $t(
                                        `monitors.form.confirmation_threshold.options.${value}`,
                                    )
                                }}
                            </button>
                        </div>
                        <p class="mt-2 text-xs text-muted-foreground">
                            {{ $t('onboarding.setup.schedule.confirm_hint') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-medium">
                            {{ $t('onboarding.setup.schedule.recover_label') }}
                        </p>
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            <button
                                v-for="value in thresholdChoices"
                                :key="value"
                                type="button"
                                class="rounded-sm border p-3 text-left text-sm transition-colors hover:bg-accent focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                                :class="
                                    recovery === value
                                        ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                        : ''
                                "
                                :aria-pressed="recovery === value"
                                @click="recovery = value"
                            >
                                {{
                                    $t(
                                        `monitors.form.recovery_threshold.options.${value}`,
                                    )
                                }}
                            </button>
                        </div>
                        <p class="mt-2 text-xs text-muted-foreground">
                            {{ $t('onboarding.setup.schedule.recover_hint') }}
                        </p>
                    </div>
                </div>

                <div class="mt-8 flex items-center gap-3">
                    <Button size="lg" @click="go('alerts')">
                        {{ $t('onboarding.setup.continue') }}
                        <ArrowRightIcon />
                    </Button>
                    <Button variant="ghost" @click="back">
                        <ArrowLeftIcon />
                        {{ $t('onboarding.setup.back') }}
                    </Button>
                </div>
            </SetupStep>

            <!-- 4. Alerts -->
            <SetupStep
                v-else-if="step === 'alerts'"
                :title="$t('onboarding.setup.alerts.title')"
                :description="$t('onboarding.setup.alerts.description')"
            >
                <div class="space-y-2">
                    <button
                        type="button"
                        class="flex w-full items-start gap-3 rounded-sm border p-3 text-left transition-colors hover:bg-accent focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                        :class="
                            alertChoice === 'email'
                                ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                : ''
                        "
                        :aria-pressed="alertChoice === 'email'"
                        @click="alertChoice = 'email'"
                    >
                        <MailIcon
                            class="mt-0.5 size-4 shrink-0"
                            aria-hidden="true"
                        />
                        <span class="min-w-0">
                            <span class="block text-sm font-medium">
                                {{
                                    $t('onboarding.setup.alerts.email_option', {
                                        email: suggestedEmail,
                                    })
                                }}
                            </span>
                            <span
                                class="mt-0.5 block text-xs text-muted-foreground"
                            >
                                {{ $t('onboarding.setup.alerts.email_hint') }}
                            </span>
                        </span>
                    </button>

                    <div
                        v-if="hasChannels"
                        class="rounded-sm border p-3 transition-colors"
                        :class="
                            alertChoice === 'existing'
                                ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                : ''
                        "
                    >
                        <button
                            type="button"
                            class="flex w-full items-center gap-3 text-left"
                            :aria-pressed="alertChoice === 'existing'"
                            @click="alertChoice = 'existing'"
                        >
                            <BellIcon
                                class="size-4 shrink-0"
                                aria-hidden="true"
                            />
                            <span class="text-sm font-medium">
                                {{ $t('onboarding.setup.alerts.existing') }}
                            </span>
                        </button>

                        <ul
                            v-if="alertChoice === 'existing'"
                            class="mt-3 flex flex-col gap-1 border-t pt-3"
                        >
                            <li v-for="channel in channels" :key="channel.uuid">
                                <label
                                    class="flex cursor-pointer items-center gap-2 text-sm"
                                >
                                    <input
                                        type="checkbox"
                                        class="size-4 rounded-sm border-input"
                                        :checked="
                                            selectedChannels.includes(
                                                channel.uuid,
                                            )
                                        "
                                        @change="toggleChannel(channel.uuid)"
                                    />
                                    {{ channel.name }}
                                    <span class="text-xs text-muted-foreground">
                                        {{
                                            $t(
                                                `integrations.types.${channel.type}`,
                                            )
                                        }}
                                    </span>
                                </label>
                            </li>
                        </ul>
                    </div>

                    <button
                        type="button"
                        class="flex w-full items-start gap-3 rounded-sm border p-3 text-left transition-colors hover:bg-accent focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                        :class="
                            alertChoice === 'none'
                                ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                : ''
                        "
                        :aria-pressed="alertChoice === 'none'"
                        @click="alertChoice = 'none'"
                    >
                        <span class="min-w-0">
                            <span class="block text-sm font-medium">
                                {{ $t('onboarding.setup.alerts.none') }}
                            </span>
                            <span
                                v-if="alertChoice === 'none'"
                                class="mt-0.5 block text-xs text-amber-700 dark:text-amber-400"
                            >
                                {{ $t('onboarding.setup.alerts.none_warning') }}
                            </span>
                        </span>
                    </button>
                </div>

                <div class="mt-8 flex items-center gap-3">
                    <Button size="lg" @click="go('done')">
                        {{ $t('onboarding.setup.continue') }}
                        <ArrowRightIcon />
                    </Button>
                    <Button variant="ghost" @click="back">
                        <ArrowLeftIcon />
                        {{ $t('onboarding.setup.back') }}
                    </Button>
                </div>
            </SetupStep>

            <!-- 5. Recap and commit -->
            <SetupStep
                v-else
                :title="$t('onboarding.setup.done.title', { name })"
                :description="$t('onboarding.setup.done.description')"
            >
                <dl class="divide-y rounded-md border text-sm">
                    <div class="flex justify-between gap-3 px-4 py-3">
                        <dt class="text-muted-foreground">
                            {{ $t('onboarding.setup.done.summary.target') }}
                        </dt>
                        <dd class="truncate font-medium">{{ url }}</dd>
                    </div>
                    <div class="flex justify-between gap-3 px-4 py-3">
                        <dt class="text-muted-foreground">
                            {{ $t('onboarding.setup.done.summary.interval') }}
                        </dt>
                        <dd class="font-medium">
                            {{ formatInterval(intervalValue) }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3 px-4 py-3">
                        <dt class="text-muted-foreground">
                            {{ $t('onboarding.setup.done.summary.alerts') }}
                        </dt>
                        <dd class="truncate font-medium">
                            {{ alertsSummary }}
                        </dd>
                    </div>
                </dl>

                <p class="mt-4 text-sm text-muted-foreground">
                    {{ $t('onboarding.setup.done.whats_next') }}
                </p>

                <div class="mt-8 flex items-center gap-3">
                    <Button size="lg" :disabled="saving" @click="finish">
                        <Spinner v-if="saving" />
                        {{
                            saving
                                ? $t('onboarding.setup.done.saving')
                                : $t('onboarding.setup.done.finish')
                        }}
                    </Button>
                    <Button variant="ghost" :disabled="saving" @click="back">
                        <ArrowLeftIcon />
                        {{ $t('onboarding.setup.back') }}
                    </Button>
                </div>
            </SetupStep>
        </div>

        <div class="mt-10 text-center">
            <button
                v-if="step !== 'welcome' && step !== 'done'"
                type="button"
                class="text-xs text-muted-foreground underline-offset-4 hover:underline"
                @click="skip"
            >
                {{ $t('onboarding.setup.skip') }}
            </button>
        </div>
    </div>
</template>
