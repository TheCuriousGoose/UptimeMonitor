<template>
    <div class="flex flex-col gap-1.5 bg-card px-4 py-3.5">
        <div
            class="flex items-center gap-1.5 text-xs tracking-wide text-muted-foreground uppercase"
        >
            <component
                v-if="icon"
                :is="icon"
                class="size-3.5 shrink-0"
                aria-hidden="true"
            />
            <span>{{ label }}</span>
        </div>
        <p
            class="font-mono text-2xl leading-none font-semibold tabular-nums"
            :class="valueClass"
        >
            {{ value }}
        </p>
        <p v-if="hint" class="text-xs text-muted-foreground">
            {{ hint }}
        </p>
    </div>
</template>

<script setup lang="ts">
import type { Component } from 'vue';

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
