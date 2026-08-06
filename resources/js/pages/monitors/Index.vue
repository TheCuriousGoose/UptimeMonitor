<script setup lang="ts">
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import { ActivityIcon, PlusIcon } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import LiveIndicator from '@/components/LiveIndicator.vue';
import MonitorsTable from '@/components/tables/monitors/MonitorsTable.vue';
import TableColumnFilter from '@/components/tables/TableColumnFilter.vue';
import TableFilterBar from '@/components/tables/TableFilterBar.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { trans } from '@/lib/i18n';
import * as monitorsRoute from '@/routes/monitors';
import type { Monitor, MonitorStatus } from '@/types/monitors';
import type { Pagination } from '@/types/pagination';
import type { SortDirection } from '@/types/tables';
import debounce from '@/util/debounce';

const props = defineProps<{
    monitors: Pagination<Monitor>;
    filters: {
        search: string | null;
        status: MonitorStatus | null;
        sort: string | null;
        direction: SortDirection;
    };
}>();

const ALL = 'all';

const search = ref<string>(props.filters.search ?? '');
const status = ref<string>(props.filters.status ?? ALL);
const loading = ref(false);

const statuses: MonitorStatus[] = ['up', 'down', 'paused', 'pending'];

// A filter is the user waiting on their own action, so the table shows
// skeletons. The background poll deliberately does not set this.
const hasFilters = computed(
    () => search.value.trim() !== '' || status.value !== ALL,
);

function reload() {
    router.get(
        monitorsRoute.index(),
        {
            search: search.value.trim() || undefined,
            status: status.value === ALL ? undefined : status.value,
            sort: props.filters.sort ?? undefined,
            direction: props.filters.sort ? props.filters.direction : undefined,
        },
        {
            preserveState: true,
            replace: true,
            only: ['monitors', 'filters'],
            onStart: () => (loading.value = true),
            onFinish: () => (loading.value = false),
        },
    );
}

function clearFilters() {
    search.value = '';
    status.value = ALL;
}

watch(search, debounce(reload, 300));
watch(status, reload);

setLayoutProps({
    breadcrumbs: [
        {
            title: trans('monitors.breadcrumbs.index'),
            href: monitorsRoute.index(),
        },
    ],
});
</script>

<template>
    <Head :title="$t('monitors.table.header')" />

    <!-- An account with no monitors at all gets the one action that makes
         the rest of the app useful, not an empty grid under a filter bar. -->
    <EmptyState
        v-if="monitors.meta.total === 0 && !hasFilters"
        :icon="ActivityIcon"
        :title="$t('monitors.empty.title')"
        :description="$t('monitors.empty.description')"
    >
        <template #actions>
            <Button :as="Link" :href="monitorsRoute.create()">
                <PlusIcon />
                {{ $t('monitors.create.label') }}
            </Button>
        </template>
    </EmptyState>

    <template v-else>
        <TableFilterBar>
            <template #filters>
                <Input
                    v-model="search"
                    name="search"
                    type="search"
                    class="w-full min-w-48 sm:w-64"
                    :placeholder="
                        $t('monitors.table.filters.search.placeholder')
                    "
                />
                <Select v-model="status">
                    <SelectTrigger class="w-full min-w-40 sm:w-44">
                        <SelectValue
                            :placeholder="
                                $t('monitors.table.filters.status.label')
                            "
                        />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="ALL">{{
                            $t('monitors.table.filters.status.all')
                        }}</SelectItem>
                        <SelectItem
                            v-for="option in statuses"
                            :key="option"
                            :value="option"
                        >
                            {{ $t(`monitors.status.${option}`) }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </template>
            <template #actions>
                <LiveIndicator :interval="30000" :only="['monitors']" />
                <Button :as="Link" :href="monitorsRoute.create()">
                    <PlusIcon />
                    {{ $t('monitors.create.label') }}
                </Button>
                <TableColumnFilter
                    table="monitors"
                    column-translations="monitors.table.columns"
                />
            </template>
        </TableFilterBar>

        <!-- "Nothing here yet" and "your search matched nothing" are different
         problems and need different exits. -->
        <EmptyState
            v-if="monitors.meta.total === 0"
            :title="$t('tables.empty.filtered.title')"
            :description="$t('tables.empty.filtered.description')"
        >
            <template #actions>
                <Button variant="outline" @click="clearFilters">
                    {{ $t('tables.empty.filtered.action') }}
                </Button>
            </template>
        </EmptyState>

        <MonitorsTable
            v-else
            :monitors="monitors"
            :sort="filters.sort"
            :direction="filters.direction"
            :loading="loading"
        />
    </template>
</template>
