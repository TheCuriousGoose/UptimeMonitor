<template>
    <div class="flex items-center gap-1">
        <!-- Not a live region itself: this ticks every few seconds, and
             announcing "8s ago", "13s ago" forever is unusable. The live
             region below fires only when data actually changed. -->
        <span class="text-xs text-muted-foreground tabular-nums">
            {{ label }}
        </span>

        <span class="sr-only" role="status" aria-live="polite">
            {{ announcement }}
        </span>

        <Button
            variant="ghost"
            size="icon-sm"
            :disabled="isRefreshing"
            @click="refresh"
        >
            <RefreshCwIcon :class="isRefreshing && 'animate-spin'" />
            <span class="sr-only">{{ $t('base.live.refresh') }}</span>
        </Button>

        <Button variant="ghost" size="icon-sm" @click="toggle">
            <component :is="enabled ? PauseIcon : PlayIcon" />
            <span class="sr-only">{{
                enabled ? $t('base.live.pause') : $t('base.live.resume')
            }}</span>
        </Button>
    </div>
</template>

<script setup lang="ts">
import { PauseIcon, PlayIcon, RefreshCwIcon } from 'lucide-vue-next';
import { computed, onUnmounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { useLiveData } from '@/composables/useLiveData';
import { formatRelative } from '@/lib/format';
import { t } from '@/lib/i18n';

const props = defineProps<{
    /** Milliseconds between refreshes. */
    interval: number;
    /** Inertia props to reload — see useLiveData on why this is required. */
    only: string[];
}>();

const { enabled, toggle, lastUpdatedAt, isRefreshing, refresh } = useLiveData(
    props.interval,
    props.only,
);

// Re-render the relative label without re-rendering it once a second.
const now = ref(Date.now());
const ticker = setInterval(() => (now.value = Date.now()), 5000);

onUnmounted(() => clearInterval(ticker));

const label = computed(() => {
    if (!enabled.value) {
        return t('base.live.paused');
    }

    // Touch `now` so the relative phrasing re-evaluates on each tick.
    void now.value;

    return formatRelative(new Date(lastUpdatedAt.value).toISOString());
});

const announcement = ref('');

watch(lastUpdatedAt, (value) => {
    // Carries the time so consecutive refreshes are distinct strings —
    // an unchanged live region is not re-announced.
    announcement.value = t('base.live.announced', {
        time: new Date(value).toLocaleTimeString(),
    });
});
</script>
