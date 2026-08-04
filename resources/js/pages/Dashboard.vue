<template>
    <Head :title="$t('dashboards.title')" />

    <div class="flex flex-col gap-6 p-4">
        <!-- A brand new account has nothing to show, so lead with the one
             action that makes the rest of the app useful. -->
        <div
            v-if="summary.total === 0"
            class="rounded-xl border bg-card p-10 text-center"
        >
            <ActivityIcon class="mx-auto size-8 text-muted-foreground" />
            <h2 class="mt-4 text-lg font-semibold">
                {{ $t('dashboards.empty.title') }}
            </h2>
            <p class="mx-auto mt-1 max-w-md text-sm text-muted-foreground">
                {{ $t('dashboards.empty.description') }}
            </p>
            <Button :as="Link" :href="monitorsRoute.create()" class="mt-5">
                <PlusIcon />
                {{ $t('dashboards.empty.action') }}
            </Button>
        </div>

        <template v-else>
            <PageHeader
                :title="$t('dashboards.title')"
                :description="$t('dashboards.subtitle')"
            >
                <template #actions>
                    <Button
                        :as="Link"
                        variant="outline"
                        :href="monitorsRoute.create()"
                    >
                        <PlusIcon />
                        {{ $t('monitors.create.label') }}
                    </Button>
                </template>
            </PageHeader>

            <div
                class="grid grid-cols-2 gap-px overflow-hidden rounded-md border bg-border lg:grid-cols-4"
            >
                <StatTile
                    :label="$t('dashboards.cards.uptime')"
                    :value="formatUptime(summary.uptime_percentage)"
                    :icon="TrendingUpIcon"
                />
                <StatTile
                    :label="$t('dashboards.cards.down')"
                    :value="summary.down"
                    :icon="XCircleIcon"
                    :value-class="
                        summary.down > 0 ? 'text-red-600 dark:text-red-400' : ''
                    "
                    :hint="`${summary.up} ${$t('dashboards.cards.up').toLowerCase()}`"
                />
                <StatTile
                    :label="$t('dashboards.cards.response')"
                    :value="formatResponseMs(summary.avg_response_ms)"
                    :icon="GaugeIcon"
                />
                <StatTile
                    :label="$t('dashboards.cards.incidents')"
                    :value="summary.ongoing_incidents"
                    :icon="SirenIcon"
                    :hint="
                        summary.paused > 0
                            ? `${summary.paused} ${$t('dashboards.cards.paused').toLowerCase()}`
                            : undefined
                    "
                />
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader class="border-b">
                        <CardTitle
                            class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >{{ $t('dashboards.attention.title') }}</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            v-if="attention.length === 0"
                            class="py-6 text-center text-sm text-muted-foreground"
                        >
                            {{ $t('dashboards.attention.empty') }}
                        </p>
                        <ul v-else class="divide-y">
                            <li
                                v-for="monitor in attention"
                                :key="monitor.uuid"
                                class="flex items-center justify-between gap-3 py-2.5"
                            >
                                <div class="min-w-0">
                                    <Link
                                        :href="
                                            monitorsRoute.show(monitor.uuid).url
                                        "
                                        class="block truncate font-medium hover:underline"
                                    >
                                        {{ monitor.name }}
                                    </Link>
                                    <p
                                        class="truncate text-xs text-muted-foreground"
                                    >
                                        {{
                                            $t(
                                                'dashboards.attention.down_since',
                                                {
                                                    time: formatRelative(
                                                        monitor.status_changed_at,
                                                    ),
                                                },
                                            )
                                        }}
                                    </p>
                                </div>
                                <MonitorStatusBadge :status="monitor.status" />
                            </li>
                        </ul>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="border-b">
                        <CardTitle
                            class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >{{ $t('dashboards.incidents.title') }}</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p
                            v-if="recentIncidents.length === 0"
                            class="py-6 text-center text-sm text-muted-foreground"
                        >
                            {{ $t('dashboards.incidents.empty') }}
                        </p>
                        <ul v-else class="divide-y">
                            <li
                                v-for="incident in recentIncidents"
                                :key="incident.uuid"
                                class="flex items-center justify-between gap-3 py-2.5"
                            >
                                <div class="min-w-0">
                                    <p class="truncate font-medium">
                                        {{ incident.monitor?.name }}
                                    </p>
                                    <p
                                        class="truncate text-xs text-muted-foreground"
                                    >
                                        {{ incident.cause ?? '—' }} ·
                                        {{
                                            formatDateTime(incident.started_at)
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
                                            ? $t('dashboards.incidents.ongoing')
                                            : formatDuration(
                                                  incident.duration_seconds,
                                              )
                                    }}
                                </span>
                            </li>
                        </ul>
                    </CardContent>
                </Card>
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ActivityIcon,
    GaugeIcon,
    PlusIcon,
    SirenIcon,
    TrendingUpIcon,
    XCircleIcon,
} from 'lucide-vue-next';
import MonitorStatusBadge from '@/components/monitors/MonitorStatusBadge.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatTile from '@/components/StatTile.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    formatDateTime,
    formatDuration,
    formatRelative,
    formatResponseMs,
    formatUptime,
} from '@/lib/format';
import { dashboard } from '@/routes';
import * as monitorsRoute from '@/routes/monitors';
import type { DashboardSummary, Incident, Monitor } from '@/types/monitors';

defineProps<{
    summary: DashboardSummary;
    attention: Monitor[];
    recentIncidents: Incident[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});
</script>
