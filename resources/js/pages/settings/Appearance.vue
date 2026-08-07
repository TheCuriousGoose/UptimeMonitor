<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import { trans } from '@/lib/i18n';
import { edit } from '@/routes/appearance';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: trans('settings.appearance.breadcrumb'),
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="$t('settings.appearance.breadcrumb')" />

    <h1 class="sr-only">{{ $t('settings.appearance.breadcrumb') }}</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            :title="$t('settings.appearance.heading')"
            :description="$t('settings.appearance.description')"
        />
        <AppearanceTabs />
    </div>
</template>
