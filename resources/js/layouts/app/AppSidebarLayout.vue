<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import CommandPalette from '@/components/CommandPalette.vue';
import ImpersonateBanner from '@/components/ImpersonateBanner.vue';
import SkipLink from '@/components/SkipLink.vue';
import { Toaster } from '@/components/ui/sonner';
import { useFocusOnNavigate } from '@/composables/useFocusOnNavigate';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
    customPadding?: string;
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
    customPadding: 'p-4',
});

useFocusOnNavigate();
</script>

<template>
    <SkipLink />

    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <ImpersonateBanner />
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <!-- The only main landmark in the authenticated app. It is also
                 the skip link's target and where focus lands after an Inertia
                 visit, so the id and tabindex are load bearing. -->
            <main id="main-content" tabindex="-1" :class="customPadding">
                <slot />
            </main>
        </AppContent>
        <Toaster />
        <CommandPalette />
    </AppShell>
</template>
