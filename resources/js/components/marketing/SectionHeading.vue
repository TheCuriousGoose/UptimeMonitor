<template>
    <header :class="lead ? 'max-w-3xl' : 'max-w-2xl'">
        <p
            v-if="eyebrow"
            class="font-mono text-[11px] tracking-[0.14em] text-primary uppercase"
        >
            {{ eyebrow }}
        </p>
        <h2
            class="mt-3 text-2xl font-semibold tracking-tight text-balance md:text-3xl"
        >
            {{ heading }}
        </h2>
        <p v-if="lead" class="mt-4 text-muted-foreground">
            {{ lead }}
        </p>
    </header>
</template>

<script setup lang="ts">
defineProps<{
    eyebrow?: string;
    heading: string;
    /** Optional standfirst. Widens the measure, since a heading alone reads better short. */
    lead?: string;
}>();
</script>
