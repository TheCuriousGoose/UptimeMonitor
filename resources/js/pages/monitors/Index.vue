<template>

    <Head :title="$t('monitors.table.header')" />

    <TableFilterBar>
        <template #filters>
            <Input name="search" type="search" class="w-64" v-model="search"
                :placeholder="$t('monitors.table.filters.search.placeholder')" />
        </template>
        <template #actions>
            <Button as="a">
                <PlusIcon />
                {{ $t('monitors.create') }}
            </Button>
            <TableColumnFilter table="monitors" column-translations="monitors.table.columns" />
        </template>
    </TableFilterBar>
    <MonitorsTable :monitors="monitors" />
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
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

watch(
    search,
    debounce((value) => {
        console.log('test', monitorsRoute.index());
        router.get(monitorsRoute.index(), { search: value }, { preserveState: true });
    }, 300)
);

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Monitors',
                href: monitorsRoute.index(),
            },
        ],
    },
});
</script>
