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
                            slot is a full-height spike, a healthy one is scaled by
                            how slow it was, relative to the rest of the window.
                            Red/green alone is unreadable for many people.
                        -->
                        <div class="flex h-full min-w-0 flex-1 items-end">
                            <div
                                class="w-full rounded-t-[2px] transition-[height]"
                                :style="{
                                    height: barHeight(bar),
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
        /** The monitor's own check cadence, used to size buckets so a normal
         *  cadence never straddles a bucket boundary and reads as "no data". */
        intervalSeconds?: number;
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

// A bucket narrower than ~2x the check interval will regularly land a
// check on one side or the other of its boundary and read as "no data"
// even though the monitor never missed a beat. Widening the bucket (and
// therefore shrinking the bar count) below that ratio keeps every regular
// check inside a single bucket.
const barDuration = computed(() => {
    const evenSplit = (end.value - start.value) / props.maxBars;
    const intervalMs = Math.max(30, props.intervalSeconds ?? 0) * 1000;

    return Math.max(evenSplit, intervalMs * 2);
});

const barCount = computed(() =>
    Math.max(1, Math.round((end.value - start.value) / barDuration.value)),
);

const bars = computed<TimelineBar[]>(() => {
    const count = barCount.value;
    const duration = barDuration.value;

    const buckets: TimelineBar[] = Array.from(
        { length: count },
        (_, index) => ({
            index,
            status: 'empty',
            barStart: start.value + index * duration,
            checkCount: 0,
            downCount: 0,
            avgResponseMs: null,
        }),
    );

    const responseSums = new Array(count).fill(0);
    const responseCounts = new Array(count).fill(0);

    for (const check of props.checks) {
        const checkedAt = new Date(check.checked_at).getTime();

        if (checkedAt < start.value || checkedAt > end.value) {
            continue;
        }

        const index = Math.min(
            count - 1,
            Math.floor((checkedAt - start.value) / duration),
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

    for (let i = 0; i < count; i += 1) {
        if (responseCounts[i] > 0) {
            buckets[i].avgResponseMs = Math.round(
                responseSums[i] / responseCounts[i],
            );
        }
    }

    return buckets;
});

// Only 'up' bars are scaled by response time — 'down' stays the tallest
// (most severe) and 'empty' the shortest, regardless of latency.
const maxUpResponseMs = computed(() =>
    Math.max(
        1,
        ...bars.value
            .filter((bar) => bar.status === 'up' && bar.avgResponseMs !== null)
            .map((bar) => bar.avgResponseMs as number),
    ),
);

const MIN_UP_HEIGHT = 20;
const MAX_UP_HEIGHT = 85;

function barHeight(bar: TimelineBar): string {
    if (bar.status === 'down') {
        return '100%';
    }

    if (bar.status === 'empty' || bar.avgResponseMs === null) {
        return '18%';
    }

    const ratio = bar.avgResponseMs / maxUpResponseMs.value;

    return `${MIN_UP_HEIGHT + ratio * (MAX_UP_HEIGHT - MIN_UP_HEIGHT)}%`;
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
