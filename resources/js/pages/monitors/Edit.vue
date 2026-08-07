<template>
    <Head :title="trans('monitors.breadcrumbs.edit', { name: monitor.name })" />

    <div>
        <div class="mb-6">
            <h1 class="text-xl font-semibold">
                {{ $t('monitors.edit.form.title') }}
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ $t('monitors.edit.form.subtitle') }}
            </p>
        </div>

        <MonitorForm
            :types="types"
            :channels="channels"
            :defaults="monitor"
            :form="monitorsRoute.update.form(monitor.uuid)"
        />
    </div>
</template>

<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import MonitorForm from '@/components/monitors/MonitorForm.vue';
import { trans } from '@/lib/i18n';
import * as monitorsRoute from '@/routes/monitors';
import type {
    Monitor,
    MonitorType,
    NotificationChannel,
} from '@/types/monitors';

const props = defineProps<{
    monitor: Monitor;
    types: MonitorType[];
    channels: NotificationChannel[];
}>();

setLayoutProps({
    breadcrumbs: [
        {
            title: trans('monitors.breadcrumbs.index'),
            href: monitorsRoute.index(),
        },
        {
            title: props.monitor.name,
            href: monitorsRoute.show(props.monitor.uuid),
        },
        {
            // Not breadcrumbs.edit — that reads "Edit :name", and the crumb
            // before this one is already the name.
            title: trans('monitors.actions.edit'),
        },
    ],
});
</script>
