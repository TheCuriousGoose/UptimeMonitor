<template>
    <Head :title="$t('monitors.create')" />


</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import * as monitorsRoute from '@/routes/monitors';

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