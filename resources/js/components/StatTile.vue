<template>
    <div class="rounded-xl border bg-card p-4">
        <div class="flex items-center gap-1.5 text-sm text-muted-foreground">
            <component
                v-if="icon"
                :is="icon"
                class="size-4 shrink-0"
                aria-hidden="true"
            />
            <span>{{ label }}</span>
        </div>
        <p class="mt-2 text-2xl leading-none font-semibold" :class="valueClass">
            {{ value }}
        </p>
        <p v-if="hint" class="mt-1.5 text-xs text-muted-foreground">
            {{ hint }}
        </p>
    </div>
</template>

<script setup lang="ts">
import type { Component } from 'vue';

/**
 * A headline number. Uses the font's proportional figures — tabular digits
 * look loose at display sizes and are only needed for aligned columns.
 */
withDefaults(
    defineProps<{
        label: string;
        value: string | number;
        hint?: string;
        icon?: Component;
        valueClass?: string;
    }>(),
    {
        valueClass: '',
    },
);
</script>
