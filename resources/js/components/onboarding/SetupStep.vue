<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <!-- Each step announces itself: the heading changes without a page load,
         so a screen reader would otherwise be told nothing happened. -->
    <section aria-live="polite">
        <h1 class="text-2xl font-semibold tracking-tight text-balance">
            {{ title }}
        </h1>
        <p
            v-if="description"
            class="mt-2 text-sm text-pretty text-muted-foreground"
        >
            {{ description }}
        </p>

        <div class="mt-6">
            <slot />
        </div>
    </section>
</template>
