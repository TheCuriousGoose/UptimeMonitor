<template>
    <Head :title="page.title" />

    <div class="min-h-screen bg-background px-4 py-10 text-foreground">
        <div class="mx-auto w-full max-w-3xl">
            <header class="mb-6 border-b pb-4">
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ page.title }}
                </h1>
                <p
                    v-if="page.description"
                    class="mt-1 text-sm text-muted-foreground"
                >
                    {{ page.description }}
                </p>
            </header>

            <!-- Overall banner: icon + words carry the state, colour only
                 reinforces it. A left status rule rather than a filled box. -->
            <div
                class="mb-8 flex items-center gap-3 rounded-sm border border-l-2 px-4 py-3"
                :class="overallTone.wrapper"
            >
                <component
                    :is="overallTone.icon"
                    class="size-5 shrink-0"
                    aria-hidden="true"
                />
                <p class="font-medium">{{ overallTone.label }}</p>
            </div>

            <p
                v-if="monitors.length === 0"
                class="rounded-sm border border-dashed p-10 text-center text-sm text-muted-foreground"
            >
                {{ $t('status_pages.public.no_monitors') }}
            </p>

            <ul v-else class="divide-y rounded-sm border">
                <li
                    v-for="monitor in monitors"
                    :key="monitor.name"
                    class="px-4 py-3.5"
                >
                    <div
                        class="flex flex-wrap items-center justify-between gap-2"
                    >
                        <div class="flex items-center gap-2">
                            <component
                                :is="statusTone(monitor.status).icon"
                                class="size-4 shrink-0"
                                :class="statusTone(monitor.status).text"
                                aria-hidden="true"
                            />
                            <span class="font-medium">{{ monitor.name }}</span>
                        </div>
                        <span
                            class="font-mono text-sm text-muted-foreground tabular-nums"
                        >
                            {{ formatUptime(monitor.uptime_percentage) }} ·
                            {{ $t('status_pages.public.uptime_90d') }}
                        </span>
                    </div>

                    <!-- One bar per day. Height doubles as a non-colour channel:
                         a day with failures is a full-height bar. -->
                    <div
                        class="mt-3 flex h-8 items-end gap-px"
                        role="img"
                        :aria-label="barsLabel(monitor)"
                    >
                        <div
                            v-for="(day, index) in dayBars(monitor)"
                            :key="index"
                            class="flex h-full flex-1 items-end"
                            :title="dayTitle(day)"
                        >
                            <div
                                class="w-full rounded-t-[2px]"
                                :style="{
                                    height: dayHeight(day),
                                    backgroundColor: dayColor(day),
                                }"
                            />
                        </div>
                    </div>
                </li>
            </ul>

            <footer
                class="mt-8 flex flex-wrap items-center justify-between gap-3 text-xs text-muted-foreground"
            >
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5">
                        <span
                            class="inline-block h-2 w-1.5 rounded-[1px]"
                            style="background-color: var(--viz-up)"
                        />
                        {{ $t('status_pages.public.legend_up') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span
                            class="inline-block h-3.5 w-1.5 rounded-[1px]"
                            style="background-color: var(--viz-down)"
                        />
                        {{ $t('status_pages.public.legend_down') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span
                            class="inline-block h-1.5 w-1.5 rounded-[1px]"
                            style="background-color: var(--viz-empty)"
                        />
                        {{ $t('status_pages.public.legend_empty') }}
                    </span>
                </div>
                <span>{{
                    $t('status_pages.public.updated', {
                        time: formatDateTime(updatedAt),
                    })
                }}</span>
            </footer>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    AlertTriangleIcon,
    CheckCircle2Icon,
    ClockIcon,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { formatDateTime, formatUptime } from '@/lib/format';
import { trans } from '@/lib/i18n';
import type { MonitorStatus } from '@/types/monitors';

type PublicDay = {
    date: string;
    uptime_percentage: number | null;
    total: number;
};

type PublicMonitor = {
    name: string;
    status: MonitorStatus;
    uptime_percentage: number | null;
    daily: PublicDay[];
};

const props = defineProps<{
    page: { title: string; description: string | null };
    monitors: PublicMonitor[];
    overall: MonitorStatus;
    updatedAt: string;
}>();

const DAYS = 90;

const overallTone = computed(() => {
    if (props.overall === 'down') {
        return {
            icon: AlertTriangleIcon,
            label: trans('status_pages.public.degraded'),
            wrapper:
                'border-red-600/25 bg-red-600/10 text-red-700 dark:text-red-400',
        };
    }

    if (props.overall === 'up') {
        return {
            icon: CheckCircle2Icon,
            label: trans('status_pages.public.all_operational'),
            wrapper:
                'border-emerald-600/25 bg-emerald-600/10 text-emerald-700 dark:text-emerald-400',
        };
    }

    return {
        icon: ClockIcon,
        label: trans('status_pages.public.pending'),
        wrapper: 'border-border bg-muted text-muted-foreground',
    };
});

function statusTone(status: MonitorStatus) {
    if (status === 'down') {
        return {
            icon: AlertTriangleIcon,
            text: 'text-red-600 dark:text-red-400',
        };
    }

    if (status === 'up') {
        return {
            icon: CheckCircle2Icon,
            text: 'text-emerald-600 dark:text-emerald-400',
        };
    }

    return { icon: ClockIcon, text: 'text-muted-foreground' };
}

/**
 * Pad the reported days out to a fixed 90-day window so every monitor's
 * bar strip lines up, regardless of when it was created.
 */
function dayBars(monitor: PublicMonitor): PublicDay[] {
    const byDate = new Map(monitor.daily.map((day) => [day.date, day]));
    const bars: PublicDay[] = [];

    for (let offset = DAYS - 1; offset >= 0; offset -= 1) {
        const date = new Date();
        date.setDate(date.getDate() - offset);
        const key = date.toISOString().slice(0, 10);

        bars.push(
            byDate.get(key) ?? { date: key, uptime_percentage: null, total: 0 },
        );
    }

    return bars;
}

function dayHeight(day: PublicDay): string {
    if (day.total === 0) {
        return '18%';
    }

    return (day.uptime_percentage ?? 100) >= 100 ? '55%' : '100%';
}

function dayColor(day: PublicDay): string {
    if (day.total === 0) {
        return 'var(--viz-empty)';
    }

    return (day.uptime_percentage ?? 100) >= 100
        ? 'var(--viz-up)'
        : 'var(--viz-down)';
}

function dayTitle(day: PublicDay): string {
    if (day.total === 0) {
        return `${day.date}: no data`;
    }

    return `${day.date}: ${formatUptime(day.uptime_percentage)}`;
}

function barsLabel(monitor: PublicMonitor): string {
    return `${monitor.name}: daily uptime for the last ${DAYS} days.`;
}
</script>
