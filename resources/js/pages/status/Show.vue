<template>
    <Head :title="page.title">
        <link
            v-if="theme.favicon_url"
            rel="icon"
            :href="theme.favicon_url"
            head-key="status-favicon"
        />
    </Head>

    <!--
        The owner's palette, injected as custom properties.

        v-html rather than interpolation because Vue escapes quotes on the
        server-rendered pass, and entities are not decoded inside a <style>
        element — an escaped font stack would simply not apply. The string is
        built by StatusPageTheme::css() from normalised hex, clamped lengths and
        a filtered font stack, so it cannot carry markup of its own.
    -->
    <!-- eslint-disable-next-line vue/no-v-text-v-html-on-component -->
    <component :is="'style'" v-html="themeCss" />

    <div
        class="sp-theme min-h-screen bg-[var(--sp-bg)] px-4 py-10 font-[family-name:var(--sp-font)] text-[var(--sp-fg)]"
    >
        <div class="mx-auto w-full" :style="{ maxWidth: 'var(--sp-width)' }">
            <header
                class="mb-6 border-b border-[var(--sp-border)] pb-5"
                :class="theme.logo_url ? 'text-center' : ''"
            >
                <img
                    v-if="theme.logo_url"
                    :src="theme.logo_url"
                    :alt="page.title"
                    class="mx-auto mb-4 h-10 w-auto max-w-[240px] object-contain"
                    referrerpolicy="no-referrer"
                />

                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ page.title }}
                </h1>
                <p
                    v-if="page.description"
                    class="mt-1 text-sm text-[var(--sp-muted-fg)]"
                >
                    {{ page.description }}
                </p>

                <nav
                    v-if="theme.links.length"
                    class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-sm"
                    :class="theme.logo_url ? 'justify-center' : ''"
                >
                    <a
                        v-for="link in theme.links"
                        :key="link.url"
                        :href="link.url"
                        rel="noopener noreferrer"
                        class="text-[var(--sp-brand)] underline-offset-4 hover:underline"
                    >
                        {{ link.label }}
                    </a>
                </nav>
            </header>

            <!-- Overall banner: icon + words carry the state, colour only
                 reinforces it. A left status rule rather than a filled box. -->
            <div
                class="mb-8 flex items-center gap-3 rounded-[var(--sp-radius)] border border-l-2 px-4 py-3"
                :style="overallTone.style"
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
                class="rounded-[var(--sp-radius)] border border-dashed border-[var(--sp-border)] p-10 text-center text-sm text-[var(--sp-muted-fg)]"
            >
                {{ $t('status_pages.public.no_monitors') }}
            </p>

            <ul
                v-else
                class="divide-y divide-[var(--sp-border)] overflow-hidden rounded-[var(--sp-radius)] border border-[var(--sp-border)] bg-[var(--sp-surface)]"
            >
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
                                :style="{
                                    color: statusTone(monitor.status).color,
                                }"
                                aria-hidden="true"
                            />
                            <span class="font-medium">{{ monitor.name }}</span>
                        </div>
                        <span
                            class="text-sm text-[var(--sp-muted-fg)] tabular-nums"
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
                class="mt-8 flex flex-wrap items-center justify-between gap-3 border-t border-[var(--sp-border)] pt-4 text-xs text-[var(--sp-muted-fg)]"
            >
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5">
                        <span
                            class="inline-block h-2 w-1.5 rounded-[1px]"
                            :style="{ backgroundColor: 'var(--sp-up)' }"
                        />
                        {{ $t('status_pages.public.legend_up') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span
                            class="inline-block h-3.5 w-1.5 rounded-[1px]"
                            :style="{ backgroundColor: 'var(--sp-down)' }"
                        />
                        {{ $t('status_pages.public.legend_down') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span
                            class="inline-block h-1.5 w-1.5 rounded-[1px]"
                            :style="{ backgroundColor: 'var(--sp-empty)' }"
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

            <p
                v-if="theme.footer_text"
                class="mt-4 text-center text-xs text-[var(--sp-muted-fg)]"
            >
                {{ theme.footer_text }}
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    AlertTriangleIcon,
    CheckCircle2Icon,
    ClockIcon,
    TrendingDownIcon,
} from 'lucide-vue-next';
import type { CSSProperties } from 'vue';
import { computed } from 'vue';
import { formatDateTime, formatUptime } from '@/lib/format';
import { trans } from '@/lib/i18n';
import type { MonitorStatus } from '@/types/monitors';
import type { StatusPageTheme } from '@/types/monitors';

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
    theme: StatusPageTheme;
    themeCss: string;
}>();

const DAYS = 90;

/**
 * A tinted panel built from one status colour, so a page that has recoloured
 * "down" gets a matching banner rather than a stock red one.
 */
function tone(color: string): CSSProperties {
    return {
        color,
        borderColor: `color-mix(in srgb, ${color} 30%, transparent)`,
        backgroundColor: `color-mix(in srgb, ${color} 10%, var(--sp-bg))`,
    };
}

const overallTone = computed(() => {
    if (props.overall === 'down') {
        return {
            icon: AlertTriangleIcon,
            label: trans('status_pages.public.degraded'),
            style: tone('var(--sp-down)'),
        };
    }

    if (props.overall === 'degraded') {
        return {
            icon: TrendingDownIcon,
            label: trans('status_pages.public.slow'),
            style: tone('var(--sp-warning)'),
        };
    }

    if (props.overall === 'up') {
        return {
            icon: CheckCircle2Icon,
            label: trans('status_pages.public.all_operational'),
            style: tone('var(--sp-up)'),
        };
    }

    return {
        icon: ClockIcon,
        label: trans('status_pages.public.pending'),
        style: {
            color: 'var(--sp-muted-fg)',
            borderColor: 'var(--sp-border)',
            backgroundColor: 'var(--sp-muted)',
        } satisfies CSSProperties,
    };
});

function statusTone(status: MonitorStatus) {
    if (status === 'down') {
        return { icon: AlertTriangleIcon, color: 'var(--sp-down)' };
    }

    if (status === 'degraded') {
        return { icon: TrendingDownIcon, color: 'var(--sp-warning)' };
    }

    if (status === 'up') {
        return { icon: CheckCircle2Icon, color: 'var(--sp-up)' };
    }

    return { icon: ClockIcon, color: 'var(--sp-muted-fg)' };
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
        return 'var(--sp-empty)';
    }

    return (day.uptime_percentage ?? 100) >= 100
        ? 'var(--sp-up)'
        : 'var(--sp-down)';
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
