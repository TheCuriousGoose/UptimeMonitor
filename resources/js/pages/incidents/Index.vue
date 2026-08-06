<script setup lang="ts">
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import {
    AlertTriangleIcon,
    ClockIcon,
    CalendarIcon,
    SirenIcon,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import LiveIndicator from '@/components/LiveIndicator.vue';
import StatTile from '@/components/StatTile.vue';
import IncidentsTable from '@/components/tables/incidents/IncidentsTable.vue';
import TableColumnFilter from '@/components/tables/TableColumnFilter.vue';
import TableFilterBar from '@/components/tables/TableFilterBar.vue';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { trans } from '@/lib/i18n';
import * as incidentsRoute from '@/routes/incidents';
import type { Incident } from '@/types/monitors';
import type { Pagination } from '@/types/pagination';
import debounce from '@/util/debounce';

const props = defineProps<{
    incidents: Pagination<Incident>;
    filters: {
        search: string | null;
        status: string | null;
    };
    summary: {
        ongoing: number;
        last_24h: number;
        last_7d: number;
        total: number;
    };
}>();

const ALL = 'all';

const search = ref<string>(props.filters.search ?? '');
const status = ref<string>(props.filters.status ?? ALL);

const statuses = ['ongoing', 'resolved'];

function reload() {
    router.get(
        incidentsRoute.index(),
        {
            search: search.value.trim() || undefined,
            status: status.value === ALL ? undefined : status.value,
        },
        {
            preserveState: true,
            replace: true,
            only: ['incidents', 'filters'],
        },
    );
}

watch(search, debounce(reload, 300));
watch(status, reload);

setLayoutProps({
    breadcrumbs: [
        {
            title: trans('incidents.breadcrumbs.index'),
            href: incidentsRoute.index(),
        },
    ],
});
</script>

<template>
    <Head :title="$t('incidents.title')" />

    <div class="flex flex-col gap-6">
        <div
            class="grid grid-cols-2 gap-px overflow-hidden rounded-md border bg-border lg:grid-cols-4"
        >
            <StatTile
                :label="$t('incidents.stats.ongoing')"
                :value="summary.ongoing"
                :icon="SirenIcon"
                :value-class="
                    summary.ongoing > 0 ? 'text-red-600 dark:text-red-400' : ''
                "
            />
            <StatTile
                :label="$t('incidents.stats.last_24h')"
                :value="summary.last_24h"
                :icon="ClockIcon"
            />
            <StatTile
                :label="$t('incidents.stats.last_7d')"
                :value="summary.last_7d"
                :icon="CalendarIcon"
            />
            <StatTile
                :label="$t('incidents.stats.total')"
                :value="summary.total"
                :icon="AlertTriangleIcon"
            />
        </div>

        <!-- A brand new account has never had an outage, so lead with that
             rather than an empty table with filters above it. -->
        <EmptyState
            v-if="summary.total === 0"
            :icon="AlertTriangleIcon"
            :title="$t('incidents.empty.title')"
            :description="$t('incidents.empty.description')"
        />

        <div v-else class="flex flex-col">
            <TableFilterBar>
                <template #filters>
                    <Input
                        v-model="search"
                        name="search"
                        type="search"
                        class="w-64"
                        :placeholder="
                            $t('incidents.table.filters.search.placeholder')
                        "
                    />
                    <Select v-model="status">
                        <SelectTrigger class="w-44">
                            <SelectValue
                                :placeholder="
                                    $t('incidents.table.filters.status.label')
                                "
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="ALL">{{
                                $t('incidents.table.filters.status.all')
                            }}</SelectItem>
                            <SelectItem
                                v-for="option in statuses"
                                :key="option"
                                :value="option"
                            >
                                {{ $t(`incidents.status.${option}`) }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </template>
                <template #actions>
                    <!-- Slower than the monitor lists: an incident is already
                         a confirmed outage, not a reading that moves. -->
                    <LiveIndicator
                        :interval="60000"
                        :only="['incidents', 'summary']"
                    />
                    <TableColumnFilter
                        table="incidents"
                        column-translations="incidents.table.columns"
                    />
                </template>
            </TableFilterBar>

            <IncidentsTable :incidents="incidents" />
        </div>
    </div>
</template>
