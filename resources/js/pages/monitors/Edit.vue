<template>
    <Head :title="trans('monitors.breadcrumbs.edit', { name: monitor.name })" />
    <MonitorForm
        :types="types"
        :form="monitorsRoute.update.form(monitor.uuid)"
        :defaults="{
            name: monitor.name,
            url: monitor.url,
            type: monitor.type,
            timeout: monitor.timeout,
            check_interval: monitor.check_interval,
            is_active: monitor.is_active,
        }"
    />
</template>

<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { trans } from '@/lib/i18n';
import * as monitorsRoute from '@/routes/monitors';
import type { Monitor, MonitorType } from '@/types/monitors';
import MonitorForm from '@/components/forms/monitorForm.vue';

const props = defineProps<{
    types: MonitorType[];
    monitor: Monitor;
}>();

setLayoutProps({
    breadcrumbs: [
        {
            title: trans('monitors.breadcrumbs.index'),
            href: monitorsRoute.index(),
        },
        {
            title: trans('monitors.breadcrumbs.edit', { name: props.monitor.name }),
        },
    ],
});

</script>