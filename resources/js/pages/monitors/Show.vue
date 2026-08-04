<template>
    <Head :title="monitor.name" />

    <div class="flex flex-col gap-4 p-4">
        <!-- Header: identity, current state, and the actions you reach for
             during an incident, all above the fold. -->
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="truncate text-xl font-semibold">
                        {{ monitor.name }}
                    </h1>
                    <MonitorStatusBadge :status="monitor.status" />
                </div>
                <p class="mt-1 truncate text-sm text-muted-foreground">
                    {{ $t(`monitors.form.type.options.${monitor.type}`) }} ·
                    {{ monitor.url }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Select
                    :model-value="period"
                    @update:model-value="
                        (value) => updatePeriod(value as string)
                    "
                >
                    <SelectTrigger class="w-40"><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in periods"
                            :key="option"
                            :value="option"
                        >
                            {{ $t(`monitors.periods.${option}`) }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <Button variant="outline" @click="runCheck">
                    <PlayIcon />
                    {{ $t('monitors.actions.check_now') }}
                </Button>
                <Button variant="outline" @click="toggleState">
                    <component
                        :is="monitor.is_active ? PauseIcon : PlayCircleIcon"
                    />
                    {{
                        monitor.is_active
                            ? $t('monitors.actions.pause')
                            : $t('monitors.actions.resume')
                    }}
                </Button>
                <Button
                    :as="Link"
                    variant="outline"
                    :href="monitorsRoute.edit(monitor.uuid).url"
                >
                    <PencilIcon />
                    {{ $t('monitors.actions.edit') }}
                </Button>
            </div>
        </div>

        <div
            class="grid grid-cols-2 gap-px overflow-hidden rounded-md border bg-border lg:grid-cols-4"
        >
            <StatTile
                :label="$t('monitors.stats.uptime')"
                :value="formatUptime(stats.uptime_percentage)"
            />
            <StatTile
                :label="$t('monitors.stats.avg_response')"
                :value="formatResponseMs(stats.avg_response_ms)"
            />
            <StatTile
                :label="$t('monitors.stats.p95_response')"
                :value="formatResponseMs(stats.p95_response_ms)"
            />
            <StatTile
                :label="$t('monitors.stats.downtime')"
                :value="
                    stats.downtime_seconds > 0
                        ? formatDuration(stats.downtime_seconds)
                        : '—'
                "
                :hint="`${stats.incidents} ${$t('monitors.stats.incidents').toLowerCase()} · ${stats.total_checks} ${$t('monitors.stats.checks').toLowerCase()}`"
            />
        </div>

        <Section
            :title="$t('monitors.show.timeline')"
            :description="$t(`monitors.periods.${period}`)"
        >
            <UptimeTimeline
                :checks="checks"
                :period="period"
                :interval-seconds="monitor.interval_seconds"
            />
        </Section>

        <div
            class="grid gap-6 divide-y divide-border lg:grid-cols-3 lg:divide-x lg:divide-y-0"
        >
            <Section
                :title="$t('monitors.show.response_chart')"
                class="lg:col-span-2"
            >
                <ResponseChart :series="series" />
            </Section>

            <Section :title="$t('monitors.show.details')" class="lg:pl-6">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">
                            {{ $t('monitors.form.check_interval.title') }}
                        </dt>
                        <dd>
                            {{ formatInterval(monitor.interval_seconds) }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">
                            {{ $t('monitors.form.timeout.title') }}
                        </dt>
                        <dd>{{ monitor.timeout }}s</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">
                            {{
                                $t('monitors.form.confirmation_threshold.title')
                            }}
                        </dt>
                        <dd>{{ monitor.confirmation_threshold }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">
                            {{ $t('monitors.show.last_checked') }}
                        </dt>
                        <dd>
                            {{
                                monitor.last_checked_at
                                    ? formatRelative(monitor.last_checked_at)
                                    : $t('monitors.never_checked')
                            }}
                        </dd>
                    </div>
                    <div
                        v-for="(value, key) in visibleConfig"
                        :key="key"
                        class="flex justify-between gap-3"
                    >
                        <dt class="text-muted-foreground">{{ key }}</dt>
                        <dd class="truncate">{{ value }}</dd>
                    </div>
                </dl>

                <div
                    v-if="monitor.notification_channels?.length"
                    class="mt-4 border-t pt-4"
                >
                    <p class="mb-2 text-xs font-medium text-muted-foreground">
                        {{ $t('monitors.form.channels.title') }}
                    </p>
                    <ul class="space-y-1 text-sm">
                        <li
                            v-for="channel in monitor.notification_channels"
                            :key="channel.uuid"
                            class="truncate"
                        >
                            {{ channel.name }}
                        </li>
                    </ul>
                </div>
            </Section>
        </div>

        <Section :title="$t('monitors.show.incidents')">
            <p
                v-if="incidents.length === 0"
                class="py-6 text-center text-sm text-muted-foreground"
            >
                {{ $t('monitors.show.no_incidents') }}
            </p>
            <ul v-else class="divide-y">
                <li
                    v-for="incident in incidents"
                    :key="incident.uuid"
                    class="flex items-start justify-between gap-3 py-3"
                >
                    <div class="min-w-0">
                        <p class="text-sm font-medium">
                            {{ incident.cause ?? '—' }}
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ formatDateTime(incident.started_at) }} ·
                            {{ incident.failed_checks }} failed checks
                        </p>
                    </div>
                    <span
                        class="shrink-0 text-xs font-medium"
                        :class="
                            incident.is_ongoing
                                ? 'text-red-600 dark:text-red-400'
                                : 'text-muted-foreground'
                        "
                    >
                        {{
                            incident.is_ongoing
                                ? $t('monitors.show.ongoing', {
                                      time: formatRelative(incident.started_at),
                                  })
                                : $t('monitors.show.resolved_after', {
                                      duration: formatDuration(
                                          incident.duration_seconds,
                                      ),
                                  })
                        }}
                    </span>
                </li>
            </ul>
        </Section>
    </div>
</template>

<script setup lang="ts">
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import {
    PauseIcon,
    PencilIcon,
    PlayCircleIcon,
    PlayIcon,
} from 'lucide-vue-next';
import { computed } from 'vue';
import MonitorStatusBadge from '@/components/monitors/MonitorStatusBadge.vue';
import ResponseChart from '@/components/monitors/ResponseChart.vue';
import UptimeTimeline from '@/components/monitors/UptimeTimeline.vue';
import Section from '@/components/Section.vue';
import StatTile from '@/components/StatTile.vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    formatDateTime,
    formatDuration,
    formatInterval,
    formatRelative,
    formatResponseMs,
    formatUptime,
} from '@/lib/format';
import { trans } from '@/lib/i18n';
import * as monitorsRoute from '@/routes/monitors';
import type {
    Incident,
    Monitor,
    MonitorCheck,
    MonitorStats,
    SeriesPoint,
} from '@/types/monitors';

const props = defineProps<{
    monitor: Monitor;
    checks: MonitorCheck[];
    stats: MonitorStats;
    series: SeriesPoint[];
    incidents: Incident[];
    period: string;
    periods: string[];
}>();

/** Only show config the user actually set something meaningful for. */
const visibleConfig = computed(() =>
    Object.fromEntries(
        Object.entries(props.monitor.config ?? {}).filter(
            ([, value]) => value !== null && value !== '' && value !== false,
        ),
    ),
);

function updatePeriod(next: string) {
    router.get(
        monitorsRoute.show(props.monitor.uuid),
        { period: next },
        { preserveState: true, preserveScroll: true },
    );
}

function runCheck() {
    router.post(
        monitorsRoute.check(props.monitor.uuid).url,
        {},
        { preserveScroll: true },
    );
}

function toggleState() {
    router.patch(
        monitorsRoute.state(props.monitor.uuid).url,
        {},
        { preserveScroll: true },
    );
}

setLayoutProps({
    breadcrumbs: [
        {
            title: trans('monitors.breadcrumbs.index'),
            href: monitorsRoute.index(),
        },
        {
            title: props.monitor.name,
        },
    ],
});
</script>
