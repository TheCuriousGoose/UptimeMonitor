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
        <span>{{
            $t(`incidents.status.${isOngoing ? 'ongoing' : 'resolved'}`)
        }}</span>
    </span>
</template>

<script setup lang="ts">
import { CheckCircle2Icon, AlertTriangleIcon } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    isOngoing: boolean;
}>();

/**
 * Icon and word carry the state alongside colour — red and green are
 * indistinguishable to a large share of readers.
 */
const tone = computed(() =>
    props.isOngoing
        ? {
              icon: AlertTriangleIcon,
              classes: 'text-red-700 dark:text-red-400',
          }
        : {
              icon: CheckCircle2Icon,
              classes: 'text-muted-foreground',
          },
);
</script>
