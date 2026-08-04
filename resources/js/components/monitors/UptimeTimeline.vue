<template>
    <div class="space-y-3">
        <TooltipProvider :delay-duration="80">
            <div
                class="flex h-10 w-full items-end gap-px"
                role="img"
                :aria-label="ariaLabel"
            >
                <Tooltip v-for="bar in bars" :key="bar.index">
                    <TooltipTrigger as-child>
                        <!--
                            Height carries the state as well as colour: a failing
                            slot is a full-height spike, an healthy one sits low.
                            Red/green alone is unreadable for many people.
                        -->
                        <div class="flex h-full min-w-0 flex-1 items-end">
                            <div
                                class="w-full rounded-t-[2px] transition-[height]"
                                :style="{
                                    height: barHeight(bar.status),
                                    backgroundColor: barColor(bar.status),
                                }"
                            />
                        </div>
                    </TooltipTrigger>
                    <TooltipContent class="space-y-1">
                        <p class="opacity-70">
                            {{
                                formatDateTime(
                                    new Date(bar.barStart).toISOString(),
                                )
                            }}
                        </p>
                        <p class="font-medium">
                            <template v-if="bar.status === 'up'">{{
                                $t('monitors.is_up')
                            }}</template>
                            <template v-else-if="bar.status === 'down'">{{
                                $t('monitors.is_down')
                            }}</template>
                            <template v-else>{{
                                $t('monitors.no_data')
                            }}</template>
                        </p>
                        <p v-if="bar.checkCount > 0" class="opacity-70">
                            {{ bar.checkCount }} checks<template
                                v-if="bar.downCount > 0"
                                >, {{ bar.downCount }} failed</template
                            >
                            <template v-if="bar.avgResponseMs !== null">
                                · {{ formatResponseMs(bar.avgResponseMs) }}
                            </template>
                        </p>
                    </TooltipContent>
                </Tooltip>
            </div>
        </TooltipProvider>

        <div
            class="flex flex-wrap items-center justify-between gap-3 text-xs text-muted-foreground"
        >
            <span>{{ formatDateTime(new Date(start).toISOString()) }}</span>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1.5">
                    <span
                        class="inline-block h-2 w-1.5 rounded-[1px]"
                        :style="{ backgroundColor: upColor }"
                    />
                    {{ $t('monitors.is_up') }}
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span
                        class="inline-block h-3.5 w-1.5 rounded-[1px]"
                        :style="{ backgroundColor: downColor }"
                    />
                    {{ $t('monitors.is_down') }}
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span
                        class="inline-block h-1.5 w-1.5 rounded-[1px]"
                        :style="{ backgroundColor: emptyColor }"
                    />
                    {{ $t('monitors.no_data') }}
                </span>
            </div>
            <span>{{ formatDateTime(new Date(end).toISOString()) }}</span>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { formatDateTime, formatResponseMs } from '@/lib/format';
import type { MonitorCheck } from '@/types/monitors';

const props = withDefaults(
    defineProps<{
        checks: MonitorCheck[];
        period: string;
        maxBars?: number;
    }>(),
    { maxBars: 80 },
);

type BarStatus = 'up' | 'down' | 'empty';

type TimelineBar = {
    index: number;
    status: BarStatus;
    barStart: number;
    checkCount: number;
    downCount: number;
    avgResponseMs: number | null;
};

const upColor = 'var(--viz-up)';
const downColor = 'var(--viz-down)';
const emptyColor = 'var(--viz-empty)';

function periodDurationMs(period: string): number {
    switch (period) {
        case '1h':
            return 60 * 60 * 1000;
        case '7d':
            return 7 * 24 * 60 * 60 * 1000;
        case '30d':
            return 30 * 24 * 60 * 60 * 1000;
        case '90d':
            return 90 * 24 * 60 * 60 * 1000;
        case '24h':
        default:
            return 24 * 60 * 60 * 1000;
    }
}

const end = computed(() => Date.now());
const start = computed(() => end.value - periodDurationMs(props.period));

const bars = computed<TimelineBar[]>(() => {
    const barCount = props.maxBars;
    const barDuration = (end.value - start.value) / barCount;

    const buckets: TimelineBar[] = Array.from(
        { length: barCount },
        (_, index) => ({
            index,
            status: 'empty',
            barStart: start.value + index * barDuration,
            checkCount: 0,
            downCount: 0,
            avgResponseMs: null,
        }),
    );

    const responseSums = new Array(barCount).fill(0);
    const responseCounts = new Array(barCount).fill(0);

    for (const check of props.checks) {
        const checkedAt = new Date(check.checked_at).getTime();

        if (checkedAt < start.value || checkedAt > end.value) {
            continue;
        }

        const index = Math.min(
            barCount - 1,
            Math.floor((checkedAt - start.value) / barDuration),
        );
        const bucket = buckets[index];

        bucket.checkCount += 1;

        if (check.is_up) {
            // A bucket only counts as up when nothing in it failed.
            if (bucket.status !== 'down') {
                bucket.status = 'up';
            }

            if (check.response_ms > 0) {
                responseSums[index] += check.response_ms;
                responseCounts[index] += 1;
            }
        } else {
            bucket.status = 'down';
            bucket.downCount += 1;
        }
    }

    for (let i = 0; i < barCount; i += 1) {
        if (responseCounts[i] > 0) {
            buckets[i].avgResponseMs = Math.round(
                responseSums[i] / responseCounts[i],
            );
        }
    }

    return buckets;
});

function barHeight(status: BarStatus): string {
    return status === 'down' ? '100%' : status === 'up' ? '55%' : '18%';
}

function barColor(status: BarStatus): string {
    return status === 'down'
        ? downColor
        : status === 'up'
          ? upColor
          : emptyColor;
}

const ariaLabel = computed(() => {
    const down = bars.value.filter((bar) => bar.status === 'down').length;
    const up = bars.value.filter((bar) => bar.status === 'up').length;

    return `Uptime timeline: ${up} healthy intervals, ${down} with failures.`;
});
</script>
