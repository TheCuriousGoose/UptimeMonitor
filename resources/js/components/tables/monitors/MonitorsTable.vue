<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { PauseIcon, PlayIcon, Trash2Icon } from 'lucide-vue-next';
import { ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import DataTable from '@/components/tables/DataTable.vue';
import { Button } from '@/components/ui/button';
import { trans } from '@/lib/i18n';
import * as monitorsRoute from '@/routes/monitors';
import type { Monitor } from '@/types/monitors';
import type { Pagination } from '@/types/pagination';
import type { SortDirection } from '@/types/tables';
import { columns } from './columns';

defineProps<{
    monitors: Pagination<Monitor>;
    sort?: string | null;
    direction?: SortDirection;
    loading?: boolean;
}>();

const pendingDelete = ref<string[]>([]);
const confirmOpen = ref(false);

function apply(
    action: 'pause' | 'resume',
    selected: string[],
    clear: () => void,
) {
    router.post(
        monitorsRoute.bulk().url,
        { action, monitors: selected },
        { preserveScroll: true, onSuccess: clear },
    );
}

function askDelete(selected: string[]) {
    pendingDelete.value = [...selected];
    confirmOpen.value = true;
}

function confirmDelete() {
    router.post(
        monitorsRoute.bulk().url,
        { action: 'delete', monitors: pendingDelete.value },
        { preserveScroll: true },
    );
}
</script>

<template>
    <DataTable
        :columns="columns"
        :pagination="monitors"
        table-key="monitors"
        :row-id="(row) => row.uuid"
        :empty-text="$t('monitors.table.empty')"
        :item-label="$t('monitors.title')"
        :sort="sort"
        :direction="direction"
        :loading="loading"
        selectable
    >
        <template #selection="{ selected, clear }">
            <Button
                variant="outline"
                size="sm"
                @click="apply('resume', selected, clear)"
            >
                <PlayIcon />
                {{ $t('monitors.actions.resume') }}
            </Button>
            <Button
                variant="outline"
                size="sm"
                @click="apply('pause', selected, clear)"
            >
                <PauseIcon />
                {{ $t('monitors.actions.pause') }}
            </Button>
            <Button
                variant="destructive"
                size="sm"
                @click="askDelete(selected)"
            >
                <Trash2Icon />
                {{ $t('monitors.actions.delete') }}
            </Button>
        </template>
    </DataTable>

    <ConfirmDialog
        v-model:open="confirmOpen"
        :title="$t('monitors.actions.delete')"
        :description="
            trans('monitors.actions.confirm_bulk_delete', {
                count: String(pendingDelete.length),
            })
        "
        :confirm-label="$t('base.delete')"
        destructive
        @confirm="confirmDelete"
    />
</template>
