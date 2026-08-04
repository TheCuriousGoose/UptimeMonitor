<template>
    <div class="flex items-center gap-1">
        <Button
            :as="Link"
            variant="ghost"
            size="sm"
            :href="monitorsRoute.show(monitor.uuid).url"
        >
            <EyeIcon />
            <span class="sr-only">{{ $t('monitors.actions.view') }}</span>
        </Button>

        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button variant="ghost" size="sm">
                    <MoreHorizontalIcon />
                    <span class="sr-only">{{
                        $t('monitors.table.columns.actions')
                    }}</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuItem
                    :as="Link"
                    :href="monitorsRoute.edit(monitor.uuid).url"
                >
                    <PencilIcon />
                    {{ $t('monitors.actions.edit') }}
                </DropdownMenuItem>
                <DropdownMenuItem @select="runCheck">
                    <PlayIcon />
                    {{ $t('monitors.actions.check_now') }}
                </DropdownMenuItem>
                <DropdownMenuItem @select="toggleState">
                    <component
                        :is="monitor.is_active ? PauseIcon : PlayCircleIcon"
                    />
                    {{
                        monitor.is_active
                            ? $t('monitors.actions.pause')
                            : $t('monitors.actions.resume')
                    }}
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    variant="destructive"
                    @select="confirmingDelete = true"
                >
                    <Trash2Icon />
                    {{ $t('monitors.actions.delete') }}
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>

        <ConfirmDialog
            v-model:open="confirmingDelete"
            :title="$t('monitors.actions.delete')"
            :description="$t('monitors.actions.confirm_delete')"
            :confirm-label="$t('base.delete')"
            destructive
            @confirm="destroy"
        />
    </div>
</template>

<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import {
    EyeIcon,
    MoreHorizontalIcon,
    PauseIcon,
    PencilIcon,
    PlayCircleIcon,
    PlayIcon,
    Trash2Icon,
} from 'lucide-vue-next';
import { ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import * as monitorsRoute from '@/routes/monitors';
import type { Monitor } from '@/types/monitors';

const props = defineProps<{
    monitor: Monitor;
}>();

const confirmingDelete = ref(false);

function runCheck() {
    router.post(
        monitorsRoute.check(props.monitor.uuid).url,
        {},
        { preserveScroll: true },
    );
}

function toggleState() {
    router.patch(
        monitorsRoute.state(props.monitor.uuid).url,
        {},
        { preserveScroll: true },
    );
}

function destroy() {
    router.delete(monitorsRoute.destroy(props.monitor.uuid).url);
}
</script>
