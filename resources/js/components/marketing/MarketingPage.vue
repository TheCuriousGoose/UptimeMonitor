<template>
    <Head :title="title" />

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
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        title: string;
        subtitle?: string;
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
