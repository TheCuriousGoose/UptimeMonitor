<template>
    <div class="flex flex-wrap items-start justify-between gap-3 border-b pb-4">
        <div class="min-w-0">
            <h1 class="text-xl font-semibold tracking-tight">{{ title }}</h1>
            <p
                v-if="description"
                class="mt-1 max-w-2xl text-sm text-muted-foreground"
            >
                {{ description }}
            </p>
        </div>
        <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
}>();
</script>
