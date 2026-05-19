<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { PlusIcon } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import MonitorsTable from '@/components/tables/monitors/MonitorsTable.vue';
import TableColumnFilter from '@/components/tables/TableColumnFilter.vue';
import TableFilterBar from '@/components/tables/TableFilterBar.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import * as monitorsRoute from '@/routes/monitors';
import type { Monitor } from '@/types/monitors';
import type { Pagination } from '@/types/pagination';
import debounce from '@/util/debounce';

defineProps<{
    monitors: Pagination<Monitor>;
}>()

const search = ref<string>('');

watch(search, debounce((value: string) => {
    const query = value.trim();

    router.get(monitorsRoute.index(), { search: query || undefined }, {
        preserveState: true,
        replace: true,
        only: ['monitors'],
    });
}, 300));

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: trans('monitors.breadcrumbs.index'),
                href: monitorsRoute.index(),
            },
        ],
    },
});
</script>


<template>

    <Head :title="$t('monitors.table.header')" />

    <TableFilterBar>
        <template #filters>
            <Input name="search" type="search" class="w-64" v-model="search"
                :placeholder="$t('monitors.table.filters.search.placeholder')" />
        </template>
        <template #actions>
            <Button :as="Link" :href="monitorsRoute.create()">
                <PlusIcon />
                {{ $t('monitors.create') }}
            </Button>
            <TableColumnFilter table="monitors" column-translations="monitors.table.columns" />
        </template>
    </TableFilterBar>
    <MonitorsTable :monitors="monitors" />
</template>
