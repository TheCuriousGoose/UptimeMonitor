<template>
    <Head title="Monitors" />

    <Card>
        <CardHeader>{{ $t('monitors.table.header') }}</CardHeader>
        <CardContent>
            <TableFilterBar>
                <template #filters>
                    <Input name="search" type="search" class="w-64" :placeholder="$t('monitors.table.filters.search.placeholder')" />
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
        </CardContent>
    </Card>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { PlusIcon } from 'lucide-vue-next';
import MonitorsTable from '@/components/tables/monitors/MonitorsTable.vue';
import TableColumnFilter from '@/components/tables/TableColumnFilter.vue';
import TableFilterBar from '@/components/tables/TableFilterBar.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import * as monitorsRoute from '@/routes/monitors';
import type { Monitor } from '@/types/monitors';
import type { Pagination } from '@/types/pagination';

defineProps<{
    monitors: Pagination<Monitor>;
}>()

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
