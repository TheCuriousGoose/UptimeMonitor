<template>
    <Form v-bind="formBinding" v-slot="{ errors, processing }" class="max-w-2xl">
        <FieldSet>
            <FieldLegend>{{ $t(titleKey) }}</FieldLegend>
            <FieldDescription>{{ $t(subtitleKey) }}</FieldDescription>
            <FieldGroup>

                <Field>
                    <FieldLabel for="name">{{ $t('monitors.form.name.title') }}</FieldLabel>
                    <Input
                        id="name"
                        name="name"
                        autocomplete="off"
                        :default-value="defaults?.name"
                    />
                    <FieldError>{{ errors.name }}</FieldError>
                    <FieldDescription>{{ $t('monitors.form.name.description') }}</FieldDescription>
                </Field>

                <Field>
                    <FieldLabel for="type">{{ $t('monitors.form.type.title') }}</FieldLabel>
                    <Select v-model="typeOption">
                        <SelectTrigger id="type">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="t in types" :key="t" :value="t">
                                {{ $t(`monitors.form.type.options.${t}`) }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <input type="hidden" name="type" :value="typeOption" />
                    <FieldError>{{ errors.type }}</FieldError>
                    <FieldDescription>{{ $t('monitors.form.type.description') }}</FieldDescription>
                </Field>

                <FieldSeparator />

                <component
                    :is="activeCheckerForm"
                    :errors="errors"
                    :url="defaults?.url"
                />

                <FieldSeparator />

                <div class="grid grid-cols-1 gap-7 sm:grid-cols-2">
                    <Field>
                        <FieldLabel for="timeout">{{ $t('monitors.form.timeout.title') }}</FieldLabel>
                        <Select v-model="timeoutOption">
                            <SelectTrigger id="timeout">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="10">{{ $t('monitors.form.timeout.options.10s') }}</SelectItem>
                                <SelectItem :value="30">{{ $t('monitors.form.timeout.options.30s') }}</SelectItem>
                                <SelectItem :value="60">{{ $t('monitors.form.timeout.options.1m') }}</SelectItem>
                                <SelectItem :value="120">{{ $t('monitors.form.timeout.options.2m') }}</SelectItem>
                                <SelectItem :value="300">{{ $t('monitors.form.timeout.options.5m') }}</SelectItem>
                                <SelectItem value="custom">{{ $t('monitors.form.custom') }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <Input
                            v-if="timeoutOption === 'custom'"
                            v-model="timeoutCustom"
                            type="number"
                            min="1"
                            :placeholder="$t('monitors.form.timeout.custom_placeholder')"
                        />
                        <input type="hidden" name="timeout" :value="timeoutValue" />
                        <FieldError>{{ errors.timeout }}</FieldError>
                        <FieldDescription>{{ $t('monitors.form.timeout.description') }}</FieldDescription>
                    </Field>

                    <Field>
                        <FieldLabel for="check_interval">{{ $t('monitors.form.check_interval.title') }}</FieldLabel>
                        <Select v-model="intervalOption">
                            <SelectTrigger id="check_interval">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="* * * * *">{{ $t('monitors.form.check_interval.options.every_minute')
                                    }}</SelectItem>
                                <SelectItem value="*/5 * * * *">{{
                                    $t('monitors.form.check_interval.options.every_5_minutes') }}</SelectItem>
                                <SelectItem value="*/10 * * * *">{{
                                    $t('monitors.form.check_interval.options.every_10_minutes') }}</SelectItem>
                                <SelectItem value="*/15 * * * *">{{
                                    $t('monitors.form.check_interval.options.every_15_minutes') }}</SelectItem>
                                <SelectItem value="*/30 * * * *">{{
                                    $t('monitors.form.check_interval.options.every_30_minutes') }}</SelectItem>
                                <SelectItem value="0 * * * *">{{ $t('monitors.form.check_interval.options.every_hour')
                                    }}</SelectItem>
                                <SelectItem value="custom">{{ $t('monitors.form.custom') }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <Input
                            v-if="intervalOption === 'custom'"
                            v-model="intervalCustom"
                            :placeholder="$t('monitors.form.check_interval.custom_placeholder')"
                        />
                        <input type="hidden" name="check_interval" :value="intervalValue" />
                        <FieldError>{{ errors.check_interval }}</FieldError>
                        <FieldDescription>{{ $t('monitors.form.check_interval.description') }}</FieldDescription>
                    </Field>
                </div>

                <FieldSeparator />

                <Field orientation="horizontal">
                    <FieldContent>
                        <FieldLabel for="is_active">{{ $t('monitors.form.is_active.title') }}</FieldLabel>
                        <FieldDescription>{{ $t('monitors.form.is_active.description') }}</FieldDescription>
                    </FieldContent>
                    <Checkbox id="is_active" v-model="isActive" />
                    <input type="hidden" name="is_active" :value="isActive ? '1' : '0'" />
                </Field>

            </FieldGroup>
        </FieldSet>
        <div class="flex justify-end">
            <Button type="submit" :disabled="processing">
                <Spinner v-if="processing" />
                {{ $t('monitors.form.submit') }}
            </Button>
        </div>
    </Form>
</template>

<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldContent, FieldDescription, FieldError, FieldGroup, FieldLabel, FieldLegend, FieldSeparator, FieldSet } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import HttpCheckerFields from '@/pages/monitors/partials/HttpCheckerFields.vue';
import * as monitorsRoute from '@/routes/monitors';
import type { MonitorType } from '@/types/monitors';

const checkerForms: Record<MonitorType, unknown> = {
    http: HttpCheckerFields,
};

const timeoutPresets = [10, 30, 60, 120, 300];
const intervalPresets = [
    '* * * * *',
    '*/5 * * * *',
    '*/10 * * * *',
    '*/15 * * * *',
    '*/30 * * * *',
    '0 * * * *',
];

const props = withDefaults(
    defineProps<{
        types: MonitorType[];
        form?:
            | ReturnType<typeof monitorsRoute.store.form>
            | ReturnType<typeof monitorsRoute.update.form>;
        defaults?: {
            name?: string;
            url?: string;
            type?: MonitorType;
            timeout?: number;
            check_interval?: string;
            is_active?: boolean;
        };
        titleKey?: string;
        subtitleKey?: string;
    }>(),
    {
        titleKey: 'monitors.create.form.title',
        subtitleKey: 'monitors.create.form.subtitle',
    },
);

const formBinding = computed(() => props.form ?? monitorsRoute.store.form());

const typeOption = ref<MonitorType>(props.defaults?.type ?? props.types[0]);
const activeCheckerForm = computed(() => checkerForms[typeOption.value]);

const initialTimeout = props.defaults?.timeout ?? 30;
const timeoutOption = ref<number | 'custom'>(
    timeoutPresets.includes(initialTimeout) ? initialTimeout : 'custom',
);
const timeoutCustom = ref<number | 'custom'>(
    timeoutPresets.includes(initialTimeout) ? 'custom' : initialTimeout,
);
const timeoutValue = computed(() =>
    timeoutOption.value === 'custom' ? timeoutCustom.value : timeoutOption.value,
);


const initialInterval = props.defaults?.check_interval ?? '*/5 * * * *';
const intervalOption = ref(
    intervalPresets.includes(initialInterval) ? initialInterval : 'custom',
);
const intervalCustom = ref(
    intervalPresets.includes(initialInterval) ? '' : initialInterval,
);
const intervalValue = computed(() =>
    intervalOption.value === 'custom' ? intervalCustom.value : intervalOption.value,
);

const isActive = ref(props.defaults?.is_active ?? true);
</script>