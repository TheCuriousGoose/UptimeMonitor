<template>
    <!-- The subtitle already summarises the page, so it doubles as the meta
         description rather than asking every caller to write it twice. -->
    <SeoHead :title="title" :description="description ?? subtitle" />

    <div class="mx-auto w-full px-4 py-16" :class="widthClass">
        <header class="border-b pb-6">
            <h1 class="text-3xl font-semibold tracking-tight">{{ title }}</h1>
            <p v-if="subtitle" class="mt-2 text-muted-foreground">
                {{ subtitle }}
            </p>
        </header>

        <div class="mt-8">
            <slot />
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import SeoHead from '@/components/SeoHead.vue';

const props = withDefaults(
    defineProps<{
        title: string;
        subtitle?: string;
        /** Overrides the subtitle when it is too terse to describe the page. */
        description?: string;
        /**
         * `prose` keeps the measure short enough to read comfortably; `wide`
         * is for listings and card grids, which look starved at reading width.
         */
        width?: 'prose' | 'wide';
    }>(),
    { width: 'prose' },
);

const widthClass = computed(() =>
    props.width === 'wide' ? 'max-w-5xl' : 'max-w-3xl',
);
</script>
