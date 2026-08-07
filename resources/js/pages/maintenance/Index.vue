<script setup lang="ts">
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import { PlusIcon, WrenchIcon } from 'lucide-vue-next';
import { ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import MaintenanceWindowDialog from '@/components/maintenance/MaintenanceWindowDialog.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { describeCron } from '@/lib/cron';
import { formatDateTime, formatDuration } from '@/lib/format';
import { trans } from '@/lib/i18n';
import * as maintenanceRoute from '@/routes/maintenance-windows';
import type { MaintenanceWindow } from '@/types/maintenance';
import type { Monitor } from '@/types/monitors';

const props = defineProps<{
    windows: MaintenanceWindow[];
    monitors: Monitor[];
}>();

const open = ref(false);
const editing = ref<MaintenanceWindow | null>(null);
const confirmOpen = ref(false);
const pendingDelete = ref<MaintenanceWindow | null>(null);

function create() {
    editing.value = null;
    open.value = true;
}

function edit(window: MaintenanceWindow) {
    editing.value = window;
    open.value = true;
}

// Pluralised in the template through the component's own $t — the standalone
// trans() helper takes no count argument.
function silencedCount(window: MaintenanceWindow) {
    return window.monitors?.length ?? 0;
}

function askDelete(window: MaintenanceWindow) {
    pendingDelete.value = window;
    confirmOpen.value = true;
}

function confirmDelete() {
    if (pendingDelete.value) {
        router.delete(maintenanceRoute.destroy(pendingDelete.value.uuid).url, {
            preserveScroll: true,
        });
    }
}

setLayoutProps({
    breadcrumbs: [
        { title: trans('maintenance.title'), href: maintenanceRoute.index() },
    ],
});
</script>

<template>
    <Head :title="$t('maintenance.title')" />

    <div class="flex flex-col gap-6">
        <PageHeader
            :title="$t('maintenance.title')"
            :description="$t('maintenance.subtitle')"
        >
            <template #actions>
                <Button v-can="'maintenance.create'" @click="create">
                    <PlusIcon />
                    {{ $t('maintenance.create') }}
                </Button>
            </template>
        </PageHeader>

        <EmptyState
            v-if="props.windows.length === 0"
            :icon="WrenchIcon"
            :title="$t('maintenance.empty.title')"
            :description="$t('maintenance.empty.description')"
        >
            <template #actions>
                <Button v-can="'maintenance.create'" @click="create">
                    <PlusIcon />
                    {{ $t('maintenance.create') }}
                </Button>
            </template>
        </EmptyState>

        <div v-else class="divide-y rounded-md border">
            <div
                v-for="window in props.windows"
                :key="window.uuid"
                class="flex flex-wrap items-center justify-between gap-3 px-4 py-3"
            >
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-medium">{{ window.name }}</span>
                        <Badge v-if="window.is_active_now" variant="default">
                            {{ $t('maintenance.active_now') }}
                        </Badge>
                        <Badge v-else-if="!window.is_active" variant="outline">
                            {{ $t('maintenance.paused') }}
                        </Badge>
                    </div>

                    <p class="mt-1 text-xs text-muted-foreground">
                        <template v-if="window.recurrence === 'recurring'">
                            {{ describeCron(window.cron) }}
                            <template v-if="window.duration_minutes">
                                ·
                                {{
                                    $t('maintenance.duration', {
                                        duration: formatDuration(
                                            window.duration_minutes * 60,
                                        ),
                                    })
                                }}
                            </template>
                            · {{ window.timezone }}
                        </template>
                        <template v-else-if="window.starts_at">
                            {{
                                $t('maintenance.window', {
                                    start: formatDateTime(window.starts_at),
                                    end: formatDateTime(window.ends_at),
                                })
                            }}
                        </template>
                    </p>

                    <p class="mt-0.5 text-xs text-muted-foreground">
                        {{
                            $t(
                                'maintenance.silences',
                                { count: silencedCount(window) },
                                silencedCount(window),
                            )
                        }}
                        <template v-if="window.next_occurrence_at">
                            ·
                            {{
                                $t('maintenance.next_occurrence', {
                                    time: formatDateTime(
                                        window.next_occurrence_at,
                                    ),
                                })
                            }}
                        </template>
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        v-can="'maintenance.edit'"
                        variant="outline"
                        size="sm"
                        @click="edit(window)"
                    >
                        {{ $t('monitors.actions.edit') }}
                    </Button>
                    <Button
                        v-can="'maintenance.delete'"
                        variant="ghost"
                        size="sm"
                        @click="askDelete(window)"
                    >
                        {{ $t('base.delete') }}
                    </Button>
                </div>
            </div>
        </div>
    </div>

    <MaintenanceWindowDialog
        v-model:open="open"
        :window="editing"
        :monitors="props.monitors"
    />

    <ConfirmDialog
        v-model:open="confirmOpen"
        :title="$t('base.delete')"
        :description="
            $t('maintenance.confirm_delete', {
                name: pendingDelete?.name ?? '',
            })
        "
        :confirm-label="$t('base.delete')"
        destructive
        @confirm="confirmDelete"
    />
</template>
