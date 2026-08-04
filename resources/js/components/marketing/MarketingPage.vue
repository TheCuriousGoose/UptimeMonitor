<template>
    <Head :title="title" />

    <div class="mx-auto w-full max-w-3xl px-4 py-16">
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

defineProps<{
    title: string;
    subtitle?: string;
}>();
</script>
