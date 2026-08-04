<template>
    <section class="flex flex-col gap-3">
        <div class="flex items-end justify-between gap-3 border-b pb-2">
            <div class="min-w-0">
                <h2
                    class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    {{ title }}
                </h2>
                <p
                    v-if="description"
                    class="mt-0.5 truncate text-sm text-muted-foreground"
                >
                    {{ description }}
                </p>
            </div>
            <div v-if="$slots.actions" class="flex shrink-0 items-center gap-2">
                <slot name="actions" />
            </div>
        </div>
        <slot />
    </section>
</template>

<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
}>();
</script>
