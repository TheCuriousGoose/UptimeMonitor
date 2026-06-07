<template>

    <Head :title="$t('monitors.create.label')" />

    <div class="max-w-2xl">
        <!-- Step indicator -->
        <Stepper v-model="step" class="flex w-full items-start gap-2 mb-8">
            <StepperItem
                v-for="s in steps"
                :key="s.step"
                v-slot="{ state }"
                class="relative flex w-full flex-col items-center justify-center"
                :step="s.step"
            >
                <StepperSeparator
                    v-if="s.step !== steps[steps.length - 1].step"
                    class="absolute left-[calc(50%+20px)] right-[calc(-50%+10px)] top-5 block h-0.5 shrink-0 rounded-full bg-muted group-data-[state=completed]:bg-primary"
                />
                <StepperTrigger as-child>
                    <Button
                        :variant="state === 'completed' || state === 'active' ? 'default' : 'outline'"
                        size="icon"
                        type="button"
                        class="z-10 rounded-full shrink-0"
                        :class="state === 'active' && 'ring-2 ring-ring ring-offset-2 ring-offset-background'"
                    >
                        <CheckIcon v-if="state === 'completed'" class="size-5" />
                        <CircleIcon v-else-if="state === 'active'" class="size-5" />
                        <DotIcon v-else class="size-5" />
                    </Button>
                </StepperTrigger>
                <div class="mt-5 flex flex-col items-center text-center">
                    <StepperTitle
                        class="text-sm font-semibold transition lg:text-base"
                        :class="state === 'active' && 'text-primary'"
                    >{{ s.label }}</StepperTitle>
                </div>
            </StepperItem>
        </Stepper>

        <!-- Shared form wrapping all steps so we can submit natively on step 4 -->
        <Form v-bind="monitorsRoute.store.form()" v-slot="{ errors, processing }">
            <!-- Hidden fields always present -->
            <input type="hidden" name="type" :value="typeOption" />
            <input type="hidden" name="timeout" :value="timeoutValue" />
            <input type="hidden" name="check_interval" :value="intervalValue" />
            <input type="hidden" name="is_active" :value="isActive ? '1' : '0'" />

            <!-- Step 1 – Basics -->
            <div v-show="step === 1">
                <div class="mb-6">
                    <h2 class="text-lg font-semibold">{{ $t('monitors.wizard.step1.title') }}</h2>
                    <p class="text-sm text-muted-foreground mt-1">{{ $t('monitors.wizard.step1.description') }}</p>
                </div>
                <FieldGroup class="mb-8">
                    <Field>
                        <FieldLabel for="name">{{ $t('monitors.form.name.title') }}</FieldLabel>
                        <Input id="name" name="name" v-model="name" autocomplete="off" />
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
                        <FieldError>{{ errors.type }}</FieldError>
                        <FieldDescription>{{ $t('monitors.form.type.description') }}</FieldDescription>
                    </Field>
                </FieldGroup>
            </div>

            <!-- Step 2 – Checker fields -->
            <div v-show="step === 2">
                <div class="mb-6">
                    <h2 class="text-lg font-semibold">{{ $t('monitors.wizard.step2.title') }}</h2>
                    <p class="text-sm text-muted-foreground mt-1">{{ $t('monitors.wizard.step2.description') }}</p>
                </div>
                <FieldGroup class="mb-8">
                    <component :is="activeCheckerForm" :errors="errors" />
                </FieldGroup>
            </div>

            <!-- Step 3 – Schedule -->
            <div v-show="step === 3">
                <div class="mb-6">
                    <h2 class="text-lg font-semibold">{{ $t('monitors.wizard.step3.title') }}</h2>
                    <p class="text-sm text-muted-foreground mt-1">{{ $t('monitors.wizard.step3.description') }}</p>
                </div>
                <FieldGroup class="mb-8">
                    <div class="grid grid-cols-1 gap-7 sm:grid-cols-2">
                        <Field>
                            <FieldLabel for="check_interval">{{ $t('monitors.form.check_interval.title') }}</FieldLabel>
                            <Select v-model="intervalOption">
                                <SelectTrigger id="check_interval">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="* * * * *">{{
                                        $t('monitors.form.check_interval.options.every_minute') }}
                                    </SelectItem>
                                    <SelectItem value="*/5 * * * *">{{
                                        $t('monitors.form.check_interval.options.every_5_minutes') }}</SelectItem>
                                    <SelectItem value="*/10 * * * *">{{
                                        $t('monitors.form.check_interval.options.every_10_minutes') }}</SelectItem>
                                    <SelectItem value="*/15 * * * *">{{
                                        $t('monitors.form.check_interval.options.every_15_minutes') }}</SelectItem>
                                    <SelectItem value="*/30 * * * *">{{
                                        $t('monitors.form.check_interval.options.every_30_minutes') }}</SelectItem>
                                    <SelectItem value="0 * * * *">{{
                                        $t('monitors.form.check_interval.options.every_hour') }}
                                    </SelectItem>
                                    <SelectItem value="custom">{{ $t('monitors.form.custom') }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <Input v-if="intervalOption === 'custom'" v-model="intervalCustom" class="mt-2"
                                :placeholder="$t('monitors.form.check_interval.custom_placeholder')" />
                            <FieldError>{{ errors.check_interval }}</FieldError>
                            <FieldDescription>{{ $t('monitors.form.check_interval.description') }}</FieldDescription>
                        </Field>

                        <Field>
                            <FieldLabel for="timeout">{{ $t('monitors.form.timeout.title') }}</FieldLabel>
                            <Select v-model="timeoutOption">
                                <SelectTrigger id="timeout">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="10">{{ $t('monitors.form.timeout.options.10s') }}</SelectItem>
                                    <SelectItem value="30">{{ $t('monitors.form.timeout.options.30s') }}</SelectItem>
                                    <SelectItem value="60">{{ $t('monitors.form.timeout.options.1m') }}</SelectItem>
                                    <SelectItem value="120">{{ $t('monitors.form.timeout.options.2m') }}</SelectItem>
                                    <SelectItem value="300">{{ $t('monitors.form.timeout.options.5m') }}</SelectItem>
                                    <SelectItem value="custom">{{ $t('monitors.form.custom') }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <Input v-if="timeoutOption === 'custom'" v-model="timeoutCustom" class="mt-2" type="number"
                                min="1" :placeholder="$t('monitors.form.timeout.custom_placeholder')" />
                            <FieldError>{{ errors.timeout }}</FieldError>
                            <FieldDescription>{{ $t('monitors.form.timeout.description') }}</FieldDescription>
                        </Field>
                    </div>
                </FieldGroup>
            </div>

            <!-- Step 4 – Review -->
            <div v-show="step === 4">
                <div class="mb-6">
                    <h2 class="text-lg font-semibold">{{ $t('monitors.wizard.step4.title') }}</h2>
                    <p class="text-sm text-muted-foreground mt-1">{{ $t('monitors.wizard.step4.description') }}</p>
                </div>
                <div class="rounded-lg border divide-y mb-6">
                    <div class="grid grid-cols-2 gap-4 px-4 py-3 text-sm">
                        <span class="text-muted-foreground">{{ $t('monitors.form.name.title') }}</span>
                        <span class="font-medium">{{ name || '—' }}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-4 px-4 py-3 text-sm">
                        <span class="text-muted-foreground">{{ $t('monitors.form.type.title') }}</span>
                        <span class="font-medium uppercase">{{ typeOption }}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-4 px-4 py-3 text-sm">
                        <span class="text-muted-foreground">{{ $t('monitors.form.check_interval.title') }}</span>
                        <span class="font-medium font-mono">{{ intervalValue }}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-4 px-4 py-3 text-sm">
                        <span class="text-muted-foreground">{{ $t('monitors.form.timeout.title') }}</span>
                        <span class="font-medium">{{ timeoutValue }}s</span>
                    </div>
                    <div class="grid grid-cols-2 gap-4 px-4 py-3 text-sm items-center">
                        <FieldLabel for="is_active">{{ $t('monitors.form.is_active.title') }}</FieldLabel>
                        <div class="flex items-center gap-2">
                            <Checkbox id="is_active" v-model="isActive" />
                            <span class="text-muted-foreground text-xs">{{ $t('monitors.form.is_active.description')
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="flex items-center justify-between pt-2">
                <Button v-if="step > 1" type="button" variant="ghost" @click="step--">
                    <ChevronLeftIcon class="size-4 mr-1" />
                    {{ $t('monitors.wizard.back') }}
                </Button>
                <div v-else />

                <Button v-if="step < steps.length" type="button" @click="next">
                    {{ $t('monitors.wizard.next') }}
                    <ChevronRightIcon class="size-4 ml-1" />
                </Button>
                <Button v-else type="submit" :disabled="processing">
                    <Spinner v-if="processing" />
                    {{ $t('monitors.form.submit') }}
                </Button>
            </div>
        </Form>
    </div>
</template>

<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { CheckIcon, ChevronLeftIcon, ChevronRightIcon, CircleIcon, DotIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldDescription, FieldError, FieldGroup, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { trans } from '@/lib/i18n';
import * as monitorsRoute from '@/routes/monitors';
import type { MonitorType } from '@/types/monitors';
import HttpCheckerFields from './partials/HttpCheckerFields.vue';
import {
    Stepper,
    StepperItem,
    StepperSeparator,
    StepperTitle,
    StepperTrigger,
} from '@/components/ui/stepper';

const checkerForms: Record<MonitorType, unknown> = {
    http: HttpCheckerFields,
};

const props = defineProps<{
    types: MonitorType[];
}>();

setLayoutProps({
    breadcrumbs: [
        {
            title: trans('monitors.breadcrumbs.index'),
            href: monitorsRoute.index(),
        },
        {
            title: trans('monitors.breadcrumbs.create'),
        },
    ],
});

const steps = [
    { step: 1, label: trans('monitors.wizard.step1.label'), description: trans('monitors.wizard.step1.description') },
    { step: 2, label: trans('monitors.wizard.step2.label'), description: trans('monitors.wizard.step2.description') },
    { step: 3, label: trans('monitors.wizard.step3.label'), description: trans('monitors.wizard.step3.description') },
    { step: 4, label: trans('monitors.wizard.step4.label'), description: trans('monitors.wizard.step4.description') },
];

const step = ref(1);

function next() {
    if (step.value < steps.length) {
        step.value++;
    }
}

const name = ref('');
const typeOption = ref<MonitorType>(props.types[0]);
const activeCheckerForm = computed(() => checkerForms[typeOption.value]);

const timeoutOption = ref('30');
const timeoutCustom = ref('');
const timeoutValue = computed(() =>
    timeoutOption.value === 'custom' ? timeoutCustom.value : timeoutOption.value,
);

const intervalOption = ref('*/5 * * * *');
const intervalCustom = ref('');
const intervalValue = computed(() =>
    intervalOption.value === 'custom' ? intervalCustom.value : intervalOption.value,
);

const isActive = ref(true);
</script>