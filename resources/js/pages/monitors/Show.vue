<template>
    <Head :title="monitor.name" />

    <div class="flex flex-col gap-4">
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
                <p
                    v-if="!monitor.is_active && monitor.paused_reason"
                    class="mt-2 rounded-sm border border-amber-500/30 bg-amber-500/5 px-3 py-2 text-xs text-amber-700 dark:text-amber-400"
                >
                    {{ monitor.paused_reason }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <LiveIndicator
                    :interval="30000"
                    :only="[
                        'monitor',
                        'checks',
                        'stats',
                        'series',
                        'incidents',
                    ]"
                />
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
                <!-- "Check now" is rate limited to a handful a minute, so an
                     impatient second click would spend the budget on a 429. -->
                <Button
                    variant="outline"
                    :disabled="checking"
                    @click="runCheck"
                >
                    <Spinner v-if="checking" />
                    <PlayIcon v-else />
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
                <Button
                    v-can="'monitors.delete'"
                    variant="ghost"
                    @click="confirmingDelete = true"
                >
                    <Trash2Icon />
                    <span class="sr-only">{{
                        $t('monitors.actions.delete')
                    }}</span>
                </Button>
            </div>
        </div>

        <!-- A monitor nobody is listening to will detect an outage and then
             tell no one, which is worse than not having it. -->
        <div
            v-if="!alertsCovered"
            class="flex flex-wrap items-center justify-between gap-3 rounded-md border border-amber-600/30 bg-amber-500/5 px-4 py-3 text-sm"
        >
            <div class="flex items-start gap-2.5">
                <BellOffIcon
                    class="mt-0.5 size-4 shrink-0 text-amber-700 dark:text-amber-400"
                    aria-hidden="true"
                />
                <div class="min-w-0">
                    <p class="font-medium">
                        {{ $t('monitors.show.no_alerts.title') }}
                    </p>
                    <p class="mt-0.5 text-muted-foreground">
                        {{
                            hasChannels
                                ? $t('monitors.show.no_alerts.unattached')
                                : $t('monitors.show.no_alerts.none_exist')
                        }}
                    </p>
                </div>
            </div>
            <!-- With no integrations at all, the edit form only offers an
                 empty list, so that case goes where one can be created. -->
            <Button
                :as="Link"
                variant="outline"
                size="sm"
                :href="
                    hasChannels
                        ? monitorsRoute.edit(monitor.uuid).url
                        : integrationsRoute.index().url
                "
            >
                {{
                    hasChannels
                        ? $t('monitors.show.no_alerts.attach')
                        : $t('monitors.show.no_alerts.connect')
                }}
            </Button>
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
                        : '-'
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
                <!-- Labels ellipsize, values do not: some of these settings
                     have long names, and the value is the information. -->
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="truncate text-muted-foreground">
                            {{ $t('monitors.show.last_checked') }}
                        </dt>
                        <dd class="shrink-0">
                            {{
                                monitor.last_checked_at
                                    ? formatRelative(monitor.last_checked_at)
                                    : $t('monitors.never_checked')
                            }}
                        </dd>
                    </div>
                    <div v-if="nextCheckAt" class="flex justify-between gap-3">
                        <dt class="truncate text-muted-foreground">
                            {{ $t('monitors.show.next_check') }}
                        </dt>
                        <dd class="shrink-0">
                            {{ formatDateTime(nextCheckAt) }}
                        </dd>
                    </div>
                    <div
                        v-for="row in details"
                        :key="row.label"
                        class="flex justify-between gap-3"
                    >
                        <dt
                            class="truncate text-muted-foreground"
                            :title="row.label"
                        >
                            {{ row.label }}
                        </dt>
                        <dd class="shrink-0">{{ row.value }}</dd>
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
                            {{ incident.cause ?? '-' }}
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ formatDateTime(incident.started_at) }} ·
                            {{
                                $t(
                                    'monitors.show.failed_checks',
                                    { count: incident.failed_checks },
                                    incident.failed_checks,
                                )
                            }}
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

    <ConfirmDialog
        v-model:open="confirmingDelete"
        :title="$t('monitors.actions.delete')"
        :description="
            $t('monitors.actions.confirm_delete', { name: monitor.name })
        "
        :confirm-label="$t('base.delete')"
        destructive
        @confirm="destroy"
    />
</template>

<script setup lang="ts">
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import {
    BellOffIcon,
    PauseIcon,
    PencilIcon,
    PlayCircleIcon,
    PlayIcon,
    Trash2Icon,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import LiveIndicator from '@/components/LiveIndicator.vue';
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
import { Spinner } from '@/components/ui/spinner';
import {
    formatDateTime,
    formatDuration,
    formatInterval,
    formatRelative,
    formatResponseMs,
    formatUptime,
} from '@/lib/format';
import { trans } from '@/lib/i18n';
import * as integrationsRoute from '@/routes/integrations';
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
    alertsCovered: boolean;
    hasChannels: boolean;
}>();

const checking = ref(false);
const confirmingDelete = ref(false);

/**
 * The details panel used to print the raw config map, so it read out
 * `verify_ssl: true` and `headers: { "Accept": "…" }` — and the masked
 * credentials with them. Only settings a reader can act on get a row, under
 * the same label the form uses, and defaults stay quiet.
 */
const details = computed(() => {
    const monitor = props.monitor;
    const config = monitor.config ?? {};
    const rows: { label: string; value: string }[] = [];

    const add = (label: string, value: string | number | null | undefined) => {
        if (value !== null && value !== undefined && value !== '') {
            rows.push({ label, value: String(value) });
        }
    };

    const configLabel = (key: string) =>
        trans(`monitors.form.config.${key}.title`);

    add(
        trans('monitors.form.check_interval.title'),
        formatInterval(monitor.interval_seconds),
    );
    add(trans('monitors.form.timeout.title'), `${monitor.timeout}s`);
    add(
        trans('monitors.form.confirmation_threshold.title'),
        monitor.confirmation_threshold,
    );
    add(
        trans('monitors.form.recovery_threshold.title'),
        monitor.recovery_threshold,
    );
    add(
        trans('monitors.form.degraded_response_ms.title'),
        monitor.degraded_response_ms
            ? formatResponseMs(monitor.degraded_response_ms)
            : null,
    );

    add(configLabel('keyword'), config.keyword);

    // Only worth a row when it flips the meaning of the keyword above it.
    if (config.invert) {
        add(configLabel('invert'), trans('base.on'));
    }

    add(configLabel('port'), config.port);
    add(configLabel('record_type'), config.record_type);
    add(configLabel('expected'), config.expected);
    add(configLabel('warn_days'), config.warn_days);
    add(configLabel('method'), config.method);
    add(
        configLabel('expected_status_codes'),
        config.expected_status_codes?.join(', '),
    );

    // Defaults say nothing; it is turning them off that a reader needs to see.
    if (config.verify_ssl === false) {
        add(configLabel('verify_ssl'), trans('base.off'));
    }

    if (config.follow_redirects === false) {
        add(configLabel('follow_redirects'), trans('base.off'));
    }

    if (config.auth_type && config.auth_type !== 'none') {
        add(
            trans('monitors.form.config.auth.title'),
            trans(`monitors.form.config.auth.options.${config.auth_type}`),
        );
    }

    // The values are credentials or masks, so the count is all we can show.
    const headers = Object.keys(config.headers ?? {}).length;

    if (headers > 0) {
        add(configLabel('headers'), headers);
    }

    return rows;
});

/**
 * Derived rather than sent: during an outage "when does it try again" is the
 * question, and a paused or never-checked monitor has no answer.
 */
const nextCheckAt = computed(() => {
    if (!props.monitor.is_active || !props.monitor.last_checked_at) {
        return null;
    }

    return new Date(
        Date.parse(props.monitor.last_checked_at) +
            props.monitor.interval_seconds * 1000,
    ).toISOString();
});

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
        {
            preserveScroll: true,
            onStart: () => (checking.value = true),
            onFinish: () => (checking.value = false),
        },
    );
}

function toggleState() {
    router.patch(
        monitorsRoute.state(props.monitor.uuid).url,
        {},
        { preserveScroll: true },
    );
}

function destroy() {
    router.delete(monitorsRoute.destroy(props.monitor.uuid).url);
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
