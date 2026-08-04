<template>
    <Head :title="$t('monitors.create.label')" />

    <div class="p-4">
        <div class="mb-6">
            <h1 class="text-xl font-semibold">
                {{ $t('monitors.create.form.title') }}
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ $t('monitors.create.form.subtitle') }}
            </p>
        </div>

        <MonitorForm :types="types" :channels="channels" />
    </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import MonitorForm from '@/components/monitors/MonitorForm.vue';
import { trans } from '@/lib/i18n';
import * as monitorsRoute from '@/routes/monitors';
import type { MonitorType, NotificationChannel } from '@/types/monitors';

defineProps<{
    types: MonitorType[];
    channels: NotificationChannel[];
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
