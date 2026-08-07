<template>
    <Head :title="$t('dashboards.title')" />

    <!-- Page padding belongs to the layout's <main>; adding it here too is
         what left this screen inset further than the rest of the app. -->
    <div class="flex flex-col gap-6">
        <!-- A brand new account has nothing to plot, so the dashboard is the
             setup path instead: the whole route to a working alert, not just
             the first step of it. -->
        <OnboardingChecklist
            v-if="summary.total === 0 && showOnboarding"
            :progress="onboarding"
            hero
            @dismiss="dismissed = true"
        />

        <EmptyState
            v-else-if="summary.total === 0"
            :icon="ActivityIcon"
            :title="$t('dashboards.empty.title')"
            :description="$t('dashboards.empty.description')"
        >
            <template #actions>
                <Button :as="Link" :href="monitorsRoute.create()">
                    <PlusIcon />
                    {{ $t('dashboards.empty.action') }}
                </Button>
            </template>
        </EmptyState>

        <template v-else>
            <!-- Still worth finishing once monitors exist — a monitor with no
                 integration has nobody to tell. -->
            <OnboardingChecklist
                v-if="showOnboarding"
                :progress="onboarding"
                @dismiss="dismissed = true"
            />

            <PageHeader
                :title="$t('dashboards.title')"
                :description="$t('dashboards.subtitle')"
            >
                <template #actions>
                    <LiveIndicator
                        :interval="30000"
                        :only="['summary', 'attention', 'recentIncidents']"
                    />
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

            <div
                class="grid gap-6 divide-y divide-border lg:grid-cols-2 lg:divide-x lg:divide-y-0"
            >
                <Section :title="$t('dashboards.attention.title')">
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
                                    :href="monitorsRoute.show(monitor.uuid).url"
                                    class="block truncate font-medium hover:underline"
                                >
                                    {{ monitor.name }}
                                </Link>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{
                                        $t('dashboards.attention.down_since', {
                                            time: formatRelative(
                                                monitor.status_changed_at,
                                            ),
                                        })
                                    }}
                                </p>
                            </div>
                            <MonitorStatusBadge :status="monitor.status" />
                        </li>
                    </ul>
                </Section>

                <Section
                    :title="$t('dashboards.incidents.title')"
                    class="lg:pl-6"
                >
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
                                    {{ incident.cause ?? '-' }} ·
                                    {{ formatDateTime(incident.started_at) }}
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
                </Section>
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
import { computed, ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import LiveIndicator from '@/components/LiveIndicator.vue';
import MonitorStatusBadge from '@/components/monitors/MonitorStatusBadge.vue';
import OnboardingChecklist from '@/components/OnboardingChecklist.vue';
import PageHeader from '@/components/PageHeader.vue';
import Section from '@/components/Section.vue';
import StatTile from '@/components/StatTile.vue';
import { Button } from '@/components/ui/button';
import {
    formatDateTime,
    formatDuration,
    formatRelative,
    formatResponseMs,
    formatUptime,
} from '@/lib/format';
import { trans } from '@/lib/i18n';
import { dashboard } from '@/routes';
import * as monitorsRoute from '@/routes/monitors';
import type { DashboardSummary, Incident, Monitor } from '@/types/monitors';
import type { OnboardingProgress } from '@/types/onboarding';

const props = defineProps<{
    summary: DashboardSummary;
    attention: Monitor[];
    recentIncidents: Incident[];
    onboarding: OnboardingProgress;
}>();

const dismissed = ref(false);

const showOnboarding = computed(
    () =>
        !dismissed.value &&
        !props.onboarding.dismissed &&
        !(
            props.onboarding.has_monitor &&
            props.onboarding.has_channel &&
            props.onboarding.has_status_page
        ),
);

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: trans('dashboards.title'),
                href: dashboard(),
            },
        ],
    },
});
</script>
