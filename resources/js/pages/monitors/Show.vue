<template>

    <Head :title="$t('monitors.table.header')" />

    <Card>
        <CardHeader class="flex flex-row items-center justify-between">
            <div>{{ $t('monitors.uptime_timeline_for', { name: monitor.name }) }}</div>
            <Select :model-value="period" @update:model-value="(v) => updatePeriod(v as string)">
                <SelectTrigger class="w-[180px]">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem v-for="p in periods" :key="p" :value="p">
                        {{ $t('monitors.periods.' + p) }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </CardHeader>
        <CardContent>
            <div class="space-y-3">
                <TooltipProvider>
                    <div
                        class="grid h-5 w-full items-center gap-px rounded-sm bg-muted/35 p-px"
                        :style="{ gridTemplateColumns: `repeat(${timelineBars.length}, minmax(0, 1fr))` }"
                    >
                        <Tooltip v-for="bar in timelineBars" :key="bar.index">
                            <TooltipTrigger as-child>
                                <div
                                    class="h-full min-w-0 rounded-[2px]"
                                    :class="{
                                        'bg-emerald-500': bar.status === 'up',
                                        'bg-red-500': bar.status === 'down',
                                        'bg-transparent': bar.status === 'empty',
                                    }"
                                />
                            </TooltipTrigger>
                            <TooltipContent class="space-y-1">
                                <p class="opacity-70">
                                    {{ formatDateLabel(bar.barStart) }} &ndash; {{ formatDateLabel(bar.barEnd) }}
                                </p>
                                <p class="flex items-center gap-1.5 font-medium">
                                    <span
                                        class="inline-block size-1.5 shrink-0 rounded-full"
                                        :class="{
                                            'bg-emerald-400': bar.status === 'up',
                                            'bg-red-400': bar.status === 'down',
                                        }"
                                    />
                                    <span v-if="bar.status === 'up'">{{ $t('monitors.is_up') }}</span>
                                    <span v-else-if="bar.status === 'down'">{{ $t('monitors.is_down') }}</span>
                                    <span v-else class="opacity-50">{{ $t('monitors.no_data') }}</span>
                                </p>
                                <p v-if="bar.checkCount > 0" class="opacity-70">
                                    <template v-if="bar.lastStatusCode !== null">HTTP {{ bar.lastStatusCode }}</template>
                                    <template v-if="bar.avgResponseMs !== null">
                                        <template v-if="bar.lastStatusCode !== null"> &middot; </template>{{ bar.avgResponseMs }}&thinsp;ms<template v-if="bar.checkCount > 1"> avg</template>
                                    </template>
                                    <template v-if="bar.checkCount > 1">
                                        &middot; {{ bar.checkCount }} checks<template v-if="bar.downCount > 0">, {{ bar.downCount }} failed</template>
                                    </template>
                                </p>
                            </TooltipContent>
                        </Tooltip>
                    </div>
                </TooltipProvider>

                <div class="flex items-center justify-between text-xs text-muted-foreground">
                    <span>{{ periodStartLabel }}</span>
                    <span>{{ $t('monitors.periods.' + period) }}</span>
                    <span>{{ currentTimeLabel }}</span>
                </div>
            </div>
        </CardContent>
    </Card>

</template>

<script setup lang="ts">
import { Head, setLayoutProps, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Card, CardHeader, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { trans } from '@/lib/i18n';
import * as monitorsRoute from "@/routes/monitors/index"
import type { Monitor, MonitorCheck } from '@/types/monitors';

const props = defineProps<{
    monitor: Monitor;
    period: string;
    periods: string[];
}>();

type TimelineBar = {
    index: number;
    status: 'up' | 'down' | 'empty';
    barStart: number;
    barEnd: number;
    checkCount: number;
    downCount: number;
    avgResponseMs: number | null;
    lastStatusCode: number | null;
};

const MAX_RENDER_BARS = 720;

function periodDurationMs(period: string): number {
    switch (period) {
        case '1h':
            return 60 * 60 * 1000;
        case '7d':
            return 7 * 24 * 60 * 60 * 1000;
        case '30d':
            return 30 * 24 * 60 * 60 * 1000;
        case '24h':
        default:
            return 24 * 60 * 60 * 1000;
    }
}

function parseCheckIntervalMs(interval: string): number | null {
    if (interval === '* * * * *') {
        return 60 * 1000;
    }

    const everyMinutes = interval.match(/^\*\/(\d+) \* \* \* \*$/);

    if (everyMinutes) {
        return Number(everyMinutes[1]) * 60 * 1000;
    }

    const hourlyAtMinute = interval.match(/^(\d+) \* \* \* \*$/);

    if (hourlyAtMinute) {
        return 60 * 60 * 1000;
    }

    return null;
}

function median(numbers: number[]): number | null {
    if (numbers.length === 0) {
        return null;
    }

    const sorted = [...numbers].sort((left, right) => left - right);
    const midpoint = Math.floor(sorted.length / 2);

    if (sorted.length % 2 === 0) {
        return (sorted[midpoint - 1] + sorted[midpoint]) / 2;
    }

    return sorted[midpoint];
}

function estimateCadenceMs(checks: MonitorCheck[], fallbackInterval: string): number {
    const fromInterval = parseCheckIntervalMs(fallbackInterval);

    if (fromInterval !== null) {
        return fromInterval;
    }

    const timestamps = checks
        .map((check) => new Date(check.checked_at).getTime())
        .sort((left, right) => left - right);

    const deltas: number[] = [];

    for (let index = 1; index < timestamps.length; index += 1) {
        const delta = timestamps[index] - timestamps[index - 1];

        if (delta > 0) {
            deltas.push(delta);
        }
    }

    return median(deltas) ?? 5 * 60 * 1000;
}

function formatDateLabel(timestamp: number): string {
    return new Intl.DateTimeFormat(undefined, {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(timestamp));
}

const timelineState = computed(() => {
    const duration = periodDurationMs(props.period);
    const end = Date.now();
    const start = end - duration;
    const checks = [...(props.monitor.checks ?? [])]
        .filter((check) => {
            const checkedAt = new Date(check.checked_at).getTime();

            return checkedAt >= start && checkedAt <= end;
        })
        .sort((left, right) => new Date(left.checked_at).getTime() - new Date(right.checked_at).getTime());

    const cadenceMs = Math.max(estimateCadenceMs(checks, props.monitor.check_interval), 60 * 1000);
    const rawSlotCount = Math.max(1, Math.ceil(duration / cadenceMs));
    const slotsPerBar = Math.max(1, Math.ceil(rawSlotCount / MAX_RENDER_BARS));
    const barDurationMs = cadenceMs * slotsPerBar;
    const barCount = Math.max(1, Math.ceil(duration / barDurationMs));

    const bars: TimelineBar[] = Array.from({ length: barCount }, (_, index) => {
        const barStart = start + index * barDurationMs;
        const barEnd = Math.min(end, barStart + barDurationMs);

        return {
            index,
            status: 'empty',
            barStart,
            barEnd,
            checkCount: 0,
            downCount: 0,
            avgResponseMs: null,
            lastStatusCode: null,
        };
    });

    const responseMsSums = new Array(barCount).fill(0);
    const responseMsCounts = new Array(barCount).fill(0);

    for (const check of checks) {
        const checkedAt = new Date(check.checked_at).getTime();
        const barIndex = Math.min(barCount - 1, Math.floor((checkedAt - start) / barDurationMs));
        const currentBar = bars[barIndex];

        if (currentBar.status !== 'down') {
            currentBar.status = check.is_up ? 'up' : 'down';
        }

        currentBar.checkCount += 1;

        if (!check.is_up) {
            currentBar.downCount += 1;
        }

        if (check.response_ms > 0) {
            responseMsSums[barIndex] += check.response_ms;
            responseMsCounts[barIndex] += 1;
        }

        currentBar.lastStatusCode = check.meta?.status_code ?? null;
    }

    for (let i = 0; i < barCount; i++) {
        if (responseMsCounts[i] > 0) {
            bars[i].avgResponseMs = Math.round(responseMsSums[i] / responseMsCounts[i]);
        }
    }

    return {
        bars,
        start,
        end,
    };
});

const timelineBars = computed(() => timelineState.value.bars);
const periodStartLabel = computed(() => formatDateLabel(timelineState.value.start));
const currentTimeLabel = computed(() => formatDateLabel(timelineState.value.end));

function updatePeriod(newPeriod: string) {
    router.get(monitorsRoute.show(props.monitor), { period: newPeriod }, { preserveState: true, preserveScroll: true });
}

setLayoutProps({
    breadcrumbs: [
        {
            title: trans('monitors.breadcrumbs.index'),
            href: monitorsRoute.index(),
        },
        {
            title: trans('monitors.breadcrumbs.show', { name: props.monitor.name }),
        },
    ],
});

</script>
