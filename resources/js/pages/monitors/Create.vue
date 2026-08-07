<template>
    <Head :title="$t('monitors.create.label')" />

    <div>
        <div class="mb-6">
            <h1 class="text-xl font-semibold">
                {{ $t('monitors.create.form.title') }}
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ $t('monitors.create.form.subtitle') }}
            </p>
        </div>

        <!-- Shown once, to the person who has never done this: the form is
             self-explanatory after the first time, and permanent instructions
             just become furniture. -->
        <div
            v-if="!onboarding.has_monitor"
            class="mb-6 max-w-3xl rounded-md border bg-muted/30 p-4"
        >
            <h2 class="flex items-center gap-2 text-sm font-medium">
                <SparklesIcon class="size-4 shrink-0" aria-hidden="true" />
                {{ $t('monitors.create.guide.title') }}
            </h2>
            <ol
                class="mt-2 ml-4 list-decimal space-y-1 text-sm text-muted-foreground marker:text-muted-foreground/60"
            >
                <li>{{ $t('monitors.create.guide.pick') }}</li>
                <li>{{ $t('monitors.create.guide.test') }}</li>
                <li>{{ $t('monitors.create.guide.alerts') }}</li>
            </ol>
        </div>

        <MonitorForm :types="types" :channels="channels" />
    </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { SparklesIcon } from 'lucide-vue-next';
import MonitorForm from '@/components/monitors/MonitorForm.vue';
import { trans } from '@/lib/i18n';
import * as monitorsRoute from '@/routes/monitors';
import type { MonitorType, NotificationChannel } from '@/types/monitors';
import type { OnboardingProgress } from '@/types/onboarding';

defineProps<{
    types: MonitorType[];
    channels: NotificationChannel[];
    onboarding: OnboardingProgress;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: trans('monitors.breadcrumbs.index'),
                href: monitorsRoute.index(),
            },
            {
                title: trans('monitors.breadcrumbs.create'),
            },
        ],
    },
});
</script>
