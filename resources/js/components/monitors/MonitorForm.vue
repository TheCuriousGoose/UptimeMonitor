<template>
    <Form
        v-bind="formBinding"
        v-slot="{ errors, processing }"
        class="max-w-3xl space-y-6"
    >
        <!-- 1. What to check -->
        <Card>
            <CardHeader>
                <CardTitle class="text-base">{{
                    $t('monitors.form.sections.what')
                }}</CardTitle>
                <CardDescription>{{
                    $t('monitors.form.type.description')
                }}</CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
                <!-- A card grid instead of a dropdown: each type explains itself,
                     so nobody has to guess what "keyword" means. -->
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    <button
                        v-for="option in types"
                        :key="option"
                        type="button"
                        class="rounded-lg border p-3 text-left transition hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        :class="
                            type === option ? 'border-primary bg-accent' : ''
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
                        <span class="mt-1 block text-xs text-muted-foreground">
                            {{ $t(`monitors.form.type.hints.${option}`) }}
                        </span>
                    </button>
                </div>
                <input type="hidden" name="type" :value="type" />
                <FieldError>{{ errors.type }}</FieldError>

                <FieldGroup>
                    <Field>
                        <FieldLabel for="name">{{
                            $t('monitors.form.name.title')
                        }}</FieldLabel>
                        <Input
                            id="name"
                            name="name"
                            autocomplete="off"
                            :placeholder="$t('monitors.form.name.placeholder')"
                            :default-value="defaults?.name"
                        />
                        <FieldError>{{ errors.name }}</FieldError>
                    </Field>

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
                            name="url"
                            autocomplete="off"
                            :placeholder="
                                expectsUrl
                                    ? $t('monitors.form.url.placeholder')
                                    : $t('monitors.form.url.host_placeholder')
                            "
                            :default-value="defaults?.url"
                        />
                        <FieldError>{{ errors.url }}</FieldError>
                        <FieldDescription>
                            {{
                                expectsUrl
                                    ? $t('monitors.form.url.description')
                                    : $t('monitors.form.url.host_description')
                            }}
                        </FieldDescription>
                    </Field>

                    <!-- Keyword -->
                    <template v-if="type === 'keyword'">
                        <Field>
                            <FieldLabel for="keyword">{{
                                $t('monitors.form.config.keyword.title')
                            }}</FieldLabel>
                            <Input
                                id="keyword"
                                name="config[keyword]"
                                :placeholder="
                                    $t(
                                        'monitors.form.config.keyword.placeholder',
                                    )
                                "
                                :default-value="defaults?.config?.keyword"
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
                            <input
                                type="hidden"
                                name="config[invert]"
                                :value="invert ? '1' : '0'"
                            />
                        </Field>
                    </template>

                    <!-- Port -->
                    <Field v-if="type === 'port'">
                        <FieldLabel for="port">{{
                            $t('monitors.form.config.port.title')
                        }}</FieldLabel>
                        <Input
                            id="port"
                            name="config[port]"
                            type="number"
                            min="1"
                            max="65535"
                            v-model="port"
                        />
                        <FieldError>{{ errors['config.port'] }}</FieldError>
                        <FieldDescription>{{
                            $t('monitors.form.config.port.description')
                        }}</FieldDescription>
                    </Field>

                    <!-- DNS -->
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
                            <input
                                type="hidden"
                                name="config[record_type]"
                                :value="recordType"
                            />
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
                                name="config[expected]"
                                :placeholder="
                                    $t(
                                        'monitors.form.config.expected.placeholder',
                                    )
                                "
                                :default-value="
                                    defaults?.config?.expected ?? ''
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

                    <!-- SSL -->
                    <Field v-if="type === 'ssl'">
                        <FieldLabel for="warn_days">{{
                            $t('monitors.form.config.warn_days.title')
                        }}</FieldLabel>
                        <Input
                            id="warn_days"
                            name="config[warn_days]"
                            type="number"
                            min="1"
                            max="365"
                            v-model="warnDays"
                        />
                        <FieldError>{{
                            errors['config.warn_days']
                        }}</FieldError>
                        <FieldDescription>{{
                            $t('monitors.form.config.warn_days.description')
                        }}</FieldDescription>
                    </Field>

                    <!-- HTTP options, shared by the plain and keyword checks -->
                    <template v-if="type === 'http' || type === 'keyword'">
                        <div class="grid gap-6 sm:grid-cols-2">
                            <Field>
                                <FieldLabel for="method">{{
                                    $t('monitors.form.config.method.title')
                                }}</FieldLabel>
                                <Select v-model="method">
                                    <SelectTrigger id="method"
                                        ><SelectValue
                                    /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="verb in methods"
                                            :key="verb"
                                            :value="verb"
                                            >{{ verb }}</SelectItem
                                        >
                                    </SelectContent>
                                </Select>
                                <input
                                    type="hidden"
                                    name="config[method]"
                                    :value="method"
                                />
                                <FieldError>{{
                                    errors['config.method']
                                }}</FieldError>
                            </Field>
                            <Field>
                                <FieldLabel for="expected_status">{{
                                    $t(
                                        'monitors.form.config.expected_status.title',
                                    )
                                }}</FieldLabel>
                                <Input
                                    id="expected_status"
                                    name="config[expected_status]"
                                    type="number"
                                    min="100"
                                    max="599"
                                    :placeholder="
                                        $t(
                                            'monitors.form.config.expected_status.placeholder',
                                        )
                                    "
                                    v-model="expectedStatus"
                                />
                                <FieldError>{{
                                    errors['config.expected_status']
                                }}</FieldError>
                                <FieldDescription>{{
                                    $t(
                                        'monitors.form.config.expected_status.description',
                                    )
                                }}</FieldDescription>
                            </Field>
                        </div>
                        <Field orientation="horizontal">
                            <FieldContent>
                                <FieldLabel for="verify_ssl">{{
                                    $t('monitors.form.config.verify_ssl.title')
                                }}</FieldLabel>
                                <FieldDescription>{{
                                    $t(
                                        'monitors.form.config.verify_ssl.description',
                                    )
                                }}</FieldDescription>
                            </FieldContent>
                            <Switch
                                id="verify_ssl"
                                v-model:checked="verifySsl"
                            />
                            <input
                                type="hidden"
                                name="config[verify_ssl]"
                                :value="verifySsl ? '1' : '0'"
                            />
                        </Field>
                    </template>
                </FieldGroup>
            </CardContent>
        </Card>

        <!-- 2. Schedule -->
        <Card>
            <CardHeader>
                <CardTitle class="text-base">{{
                    $t('monitors.form.sections.schedule')
                }}</CardTitle>
            </CardHeader>
            <CardContent>
                <FieldGroup>
                    <div class="grid gap-6 sm:grid-cols-2">
                        <Field>
                            <FieldLabel for="interval">{{
                                $t('monitors.form.check_interval.title')
                            }}</FieldLabel>
                            <Select v-model="intervalOption">
                                <SelectTrigger id="interval"
                                    ><SelectValue
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="preset in intervalPresets"
                                        :key="preset.value"
                                        :value="preset.value"
                                    >
                                        {{
                                            $t(
                                                `monitors.form.check_interval.options.${preset.label}`,
                                            )
                                        }}
                                    </SelectItem>
                                    <SelectItem value="custom">{{
                                        $t('monitors.form.custom')
                                    }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <Input
                                v-if="intervalOption === 'custom'"
                                v-model="intervalCustom"
                                type="number"
                                min="30"
                                :placeholder="
                                    $t(
                                        'monitors.form.check_interval.custom_placeholder',
                                    )
                                "
                            />
                            <input
                                type="hidden"
                                name="interval_seconds"
                                :value="intervalValue"
                            />
                            <FieldError>{{
                                errors.interval_seconds
                            }}</FieldError>
                            <FieldDescription>{{
                                $t('monitors.form.check_interval.description')
                            }}</FieldDescription>
                        </Field>

                        <Field>
                            <FieldLabel for="timeout">{{
                                $t('monitors.form.timeout.title')
                            }}</FieldLabel>
                            <Select v-model="timeoutOption">
                                <SelectTrigger id="timeout"
                                    ><SelectValue
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="preset in timeoutPresets"
                                        :key="preset.value"
                                        :value="preset.value"
                                    >
                                        {{
                                            $t(
                                                `monitors.form.timeout.options.${preset.label}`,
                                            )
                                        }}
                                    </SelectItem>
                                    <SelectItem value="custom">{{
                                        $t('monitors.form.custom')
                                    }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <Input
                                v-if="timeoutOption === 'custom'"
                                v-model="timeoutCustom"
                                type="number"
                                min="1"
                                :placeholder="
                                    $t(
                                        'monitors.form.timeout.custom_placeholder',
                                    )
                                "
                            />
                            <input
                                type="hidden"
                                name="timeout"
                                :value="timeoutValue"
                            />
                            <FieldError>{{ errors.timeout }}</FieldError>
                            <FieldDescription>{{
                                $t('monitors.form.timeout.description')
                            }}</FieldDescription>
                        </Field>
                    </div>

                    <Field>
                        <FieldLabel for="confirmation">{{
                            $t('monitors.form.confirmation_threshold.title')
                        }}</FieldLabel>
                        <Select v-model="confirmation">
                            <SelectTrigger id="confirmation" class="sm:w-72"
                                ><SelectValue
                            /></SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="value in confirmationOptions"
                                    :key="value"
                                    :value="value"
                                >
                                    {{
                                        $t(
                                            `monitors.form.confirmation_threshold.options.${value}`,
                                        )
                                    }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <input
                            type="hidden"
                            name="confirmation_threshold"
                            :value="confirmation"
                        />
                        <FieldError>{{
                            errors.confirmation_threshold
                        }}</FieldError>
                        <FieldDescription>{{
                            $t(
                                'monitors.form.confirmation_threshold.description',
                            )
                        }}</FieldDescription>
                    </Field>

                    <Field orientation="horizontal">
                        <FieldContent>
                            <FieldLabel for="is_active">{{
                                $t('monitors.form.is_active.title')
                            }}</FieldLabel>
                            <FieldDescription>{{
                                $t('monitors.form.is_active.description')
                            }}</FieldDescription>
                        </FieldContent>
                        <Switch id="is_active" v-model:checked="isActive" />
                        <input
                            type="hidden"
                            name="is_active"
                            :value="isActive ? '1' : '0'"
                        />
                    </Field>
                </FieldGroup>
            </CardContent>
        </Card>

        <!-- 3. Alerts -->
        <Card>
            <CardHeader>
                <CardTitle class="text-base">{{
                    $t('monitors.form.sections.alerts')
                }}</CardTitle>
                <CardDescription>{{
                    $t('monitors.form.channels.description')
                }}</CardDescription>
            </CardHeader>
            <CardContent>
                <p
                    v-if="channels.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    {{ $t('monitors.form.channels.empty') }}
                    <Link :href="channelsRoute.index()" class="underline">
                        {{ $t('monitors.form.channels.manage') }}
                    </Link>
                </p>
                <div v-else class="space-y-3">
                    <label
                        v-for="channel in channels"
                        :key="channel.uuid"
                        class="flex cursor-pointer items-center gap-3 rounded-lg border p-3"
                    >
                        <Checkbox
                            :model-value="
                                selectedChannels.includes(channel.uuid)
                            "
                            @update:model-value="toggleChannel(channel.uuid)"
                        />
                        <span class="min-w-0">
                            <span class="block text-sm font-medium">{{
                                channel.name
                            }}</span>
                            <span
                                class="block truncate text-xs text-muted-foreground"
                            >
                                {{ $t(`channels.types.${channel.type}`) }} ·
                                {{ channel.destination }}
                            </span>
                        </span>
                    </label>
                    <input
                        v-for="uuid in selectedChannels"
                        :key="`sel-${uuid}`"
                        type="hidden"
                        name="notification_channels[]"
                        :value="uuid"
                    />
                    <!-- Keeps the key present when everything is unticked, so an
                         update can clear the list rather than silently keep it. -->
                    <input
                        v-if="selectedChannels.length === 0"
                        type="hidden"
                        name="notification_channels[]"
                        value=""
                    />
                </div>
            </CardContent>
        </Card>

        <div class="flex justify-end gap-2">
            <Button :as="Link" variant="ghost" :href="monitorsRoute.index()">
                {{ $t('base.cancel') }}
            </Button>
            <Button type="submit" :disabled="processing">
                <Spinner v-if="processing" />
                {{ $t('monitors.form.submit') }}
            </Button>
        </div>
    </Form>
</template>

<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import {
    FileSearchIcon,
    GlobeIcon,
    NetworkIcon,
    PlugIcon,
    RadioIcon,
    ShieldCheckIcon,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Field,
    FieldContent,
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
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import * as channelsRoute from '@/routes/channels';
import * as monitorsRoute from '@/routes/monitors';
import type {
    Monitor,
    MonitorType,
    NotificationChannel,
} from '@/types/monitors';

const props = withDefaults(
    defineProps<{
        types: MonitorType[];
        channels: NotificationChannel[];
        form?:
            | ReturnType<typeof monitorsRoute.store.form>
            | ReturnType<typeof monitorsRoute.update.form>;
        defaults?: Partial<Monitor>;
    }>(),
    {},
);

const formBinding = computed(() => props.form ?? monitorsRoute.store.form());

const typeIcons: Record<MonitorType, unknown> = {
    http: GlobeIcon,
    keyword: FileSearchIcon,
    port: PlugIcon,
    ping: RadioIcon,
    dns: NetworkIcon,
    ssl: ShieldCheckIcon,
};

const urlTypes: MonitorType[] = ['http', 'keyword', 'ssl'];
const methods = ['GET', 'POST', 'HEAD'];
const recordTypes = ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS'];
const confirmationOptions = [1, 2, 3, 5];

const intervalPresets = [
    { value: 30, label: '30s' },
    { value: 60, label: '1m' },
    { value: 300, label: '5m' },
    { value: 600, label: '10m' },
    { value: 1800, label: '30m' },
    { value: 3600, label: '1h' },
];

const timeoutPresets = [
    { value: 5, label: '5s' },
    { value: 10, label: '10s' },
    { value: 30, label: '30s' },
    { value: 60, label: '60s' },
];

const type = ref<MonitorType>(props.defaults?.type ?? props.types[0]);
const expectsUrl = computed(() => urlTypes.includes(type.value));

const invert = ref(props.defaults?.config?.invert ?? false);
const verifySsl = ref(props.defaults?.config?.verify_ssl ?? true);
const method = ref(props.defaults?.config?.method ?? 'GET');
const expectedStatus = ref<number | string>(
    props.defaults?.config?.expected_status ?? '',
);
const port = ref<number | string>(props.defaults?.config?.port ?? 443);
const recordType = ref(props.defaults?.config?.record_type ?? 'A');
const warnDays = ref<number | string>(props.defaults?.config?.warn_days ?? 14);

const initialInterval = props.defaults?.interval_seconds ?? 300;
const intervalOption = ref<number | 'custom'>(
    intervalPresets.some((preset) => preset.value === initialInterval)
        ? initialInterval
        : 'custom',
);
const intervalCustom = ref<number | string>(
    intervalPresets.some((preset) => preset.value === initialInterval)
        ? ''
        : initialInterval,
);
const intervalValue = computed(() =>
    intervalOption.value === 'custom'
        ? intervalCustom.value
        : intervalOption.value,
);

const initialTimeout = props.defaults?.timeout ?? 10;
const timeoutOption = ref<number | 'custom'>(
    timeoutPresets.some((preset) => preset.value === initialTimeout)
        ? initialTimeout
        : 'custom',
);
const timeoutCustom = ref<number | string>(
    timeoutPresets.some((preset) => preset.value === initialTimeout)
        ? ''
        : initialTimeout,
);
const timeoutValue = computed(() =>
    timeoutOption.value === 'custom'
        ? timeoutCustom.value
        : timeoutOption.value,
);

const confirmation = ref(props.defaults?.confirmation_threshold ?? 1);
const isActive = ref(props.defaults?.is_active ?? true);

const selectedChannels = ref<string[]>(
    (props.defaults?.notification_channels ?? []).map(
        (channel) => channel.uuid,
    ),
);

function toggleChannel(uuid: string) {
    selectedChannels.value = selectedChannels.value.includes(uuid)
        ? selectedChannels.value.filter((value) => value !== uuid)
        : [...selectedChannels.value, uuid];
}

// A port check on "example.com:6379" should not silently keep the 443 default.
watch(type, (next) => {
    if (next === 'port' && !props.defaults?.config?.port) {
        port.value = 443;
    }
});
</script>
