<script setup lang="ts">
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import { PlusIcon } from 'lucide-vue-next';
import { ref, watch } from 'vue';
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
import debounce from '@/util/debounce';

const props = defineProps<{
    monitors: Pagination<Monitor>;
    filters: {
        search: string | null;
        status: MonitorStatus | null;
    };
}>();

const ALL = 'all';

const search = ref<string>(props.filters.search ?? '');
const status = ref<string>(props.filters.status ?? ALL);

const statuses: MonitorStatus[] = ['up', 'down', 'paused', 'pending'];

function reload() {
    router.get(
        monitorsRoute.index(),
        {
            search: search.value.trim() || undefined,
            status: status.value === ALL ? undefined : status.value,
        },
        {
            preserveState: true,
            replace: true,
            only: ['monitors', 'filters'],
        },
    );
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

    <TableFilterBar>
        <template #filters>
            <Input
                v-model="search"
                name="search"
                type="search"
                class="w-64"
                :placeholder="$t('monitors.table.filters.search.placeholder')"
            />
            <Select v-model="status">
                <SelectTrigger class="w-44">
                    <SelectValue
                        :placeholder="$t('monitors.table.filters.status.label')"
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

    <MonitorsTable :monitors="monitors" />
</template>
