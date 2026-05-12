<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
    customPadding?: string;
}>();
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs" :custom-padding="customPadding">
        <slot />
    </AppLayout>
</template>
