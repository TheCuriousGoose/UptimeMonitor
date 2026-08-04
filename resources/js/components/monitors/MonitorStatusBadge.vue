<template>
    <span
        class="inline-flex items-center gap-1.5 font-mono text-xs font-medium tracking-wide uppercase"
        :class="tone.classes"
    >
        <component
            :is="tone.icon"
            class="size-3.5 shrink-0"
            aria-hidden="true"
        />
        <span>{{ $t(`monitors.status.${status}`) }}</span>
    </span>
</template>

<script setup lang="ts">
import {
    CheckCircle2Icon,
    ClockIcon,
    PauseIcon,
    XCircleIcon,
} from 'lucide-vue-next';
import { computed } from 'vue';
import type { MonitorStatus } from '@/types/monitors';

const props = defineProps<{
    status: MonitorStatus;
}>();

/**
 * Every status pairs its colour with an icon and a word: red and green are
 * indistinguishable to a large share of readers, so colour never stands alone.
 */
const tones = {
    up: {
        icon: CheckCircle2Icon,
        classes: 'text-emerald-700 dark:text-emerald-400',
    },
    down: {
        icon: XCircleIcon,
        classes: 'text-red-700 dark:text-red-400',
    },
    paused: {
        icon: PauseIcon,
        classes: 'text-muted-foreground',
    },
    pending: {
        icon: ClockIcon,
        classes: 'text-muted-foreground',
    },
} as const;

const tone = computed(() => tones[props.status] ?? tones.pending);
</script>
