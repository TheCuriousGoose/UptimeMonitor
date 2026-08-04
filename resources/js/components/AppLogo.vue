<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const page = usePage();

const name = computed(() => (page.props.name as string) ?? 'Vigil Watch');
</script>

<template>
    <div
        class="flex aspect-square size-8 shrink-0 items-center justify-center rounded-lg bg-primary text-primary-foreground shadow-sm"
    >
        <AppLogoIcon class="size-[18px]" />
    </div>
    <div class="ml-2 grid flex-1 text-left leading-tight">
        <span class="truncate text-sm font-semibold tracking-tight">
            {{ name }}
        </span>
        <span class="truncate text-[11px] text-muted-foreground">
            {{ $t('base.tagline') }}
        </span>
    </div>
</template>
