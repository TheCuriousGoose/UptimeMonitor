<template>
    <!-- A dashed hairline frame rather than a filled card: an empty state is an
         absence, so it should read as an outline waiting to be filled. -->
    <div
        class="flex flex-col items-center rounded-sm border border-dashed px-6 py-14 text-center"
    >
        <component
            v-if="icon"
            :is="icon"
            class="size-7 text-muted-foreground/60"
            aria-hidden="true"
        />
        <h2
            class="mt-4 font-mono text-xs font-semibold tracking-[0.12em] uppercase"
        >
            {{ title }}
        </h2>
        <p
            v-if="description"
            class="mx-auto mt-2 max-w-md text-sm text-muted-foreground"
        >
            {{ description }}
        </p>
        <div v-if="$slots.actions" class="mt-5 flex items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>

<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    title: string;
    description?: string;
    icon?: Component;
}>();
</script>
