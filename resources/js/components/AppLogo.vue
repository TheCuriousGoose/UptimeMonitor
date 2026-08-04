<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const page = usePage();

const name = computed(() => (page.props.name as string) ?? 'Vigil Watch');
</script>

<template>
    <div
        class="flex aspect-square size-8 shrink-0 items-center justify-center rounded-sm border border-sidebar-primary/40 bg-sidebar-primary/10 text-sidebar-primary"
    >
        <AppLogoIcon class="size-[18px]" />
    </div>
    <div class="ml-2.5 grid flex-1 text-left leading-tight">
        <span class="truncate text-sm font-semibold tracking-tight">
            {{ name }}
        </span>
        <span
            class="truncate font-mono text-[10px] tracking-[0.1em] text-muted-foreground uppercase"
        >
            {{ $t('base.tagline') }}
        </span>
    </div>
</template>
