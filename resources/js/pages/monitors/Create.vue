<template>
    <Head :title="$t('monitors.create.label')" />
    <MonitorForm :types="types" />
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { trans } from '@/lib/i18n';
import * as monitorsRoute from '@/routes/monitors';
import type { MonitorType } from '@/types/monitors';
import MonitorForm from '@/components/forms/monitorForm.vue';

const props = defineProps<{
    types: MonitorType[];
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