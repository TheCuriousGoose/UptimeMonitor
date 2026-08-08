<script setup lang="ts">
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatInterval } from '@/lib/format';
import { trans } from '@/lib/i18n';
import * as targetsRoute from '@/routes/admin/targets';

type TargetMonitor = {
    uuid: string;
    name: string;
    url: string;
    type: string;
    interval_seconds: number;
    requests_per_minute: number;
    is_active: boolean;
    paused_reason: string | null;
    owner: { name: string | null; email: string | null };
};

const props = defineProps<{
    domain: string;
    verified: boolean;
    monitors: TargetMonitor[];
    totals: {
        monitors: number;
        accounts: number;
        requests_per_minute: number;
    };
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Targets', href: targetsRoute.index().url },
        { title: props.domain, href: targetsRoute.show(props.domain).url },
    ],
});

const confirmingPause = ref(false);

function pauseAll() {
    router.delete(targetsRoute.destroy(props.domain).url, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="domain" />

    <div class="px-4 py-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold">
                    {{ domain }}
                    <Badge :variant="verified ? 'default' : 'outline'">
                        {{
                            verified
                                ? $t('admin.targets.verified')
                                : $t('admin.targets.unverified')
                        }}
                    </Badge>
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ totals.requests_per_minute.toFixed(3) }}
                    {{ $t('admin.targets.rate') }} ·
                    {{
                        $t('admin.targets.concentration', {
                            accounts: totals.accounts,
                        })
                    }}
                </p>
            </div>

            <Button
                variant="destructive"
                size="sm"
                @click="confirmingPause = true"
            >
                {{ $t('admin.targets.pause_all') }}
            </Button>
        </div>

        <div class="mt-6 overflow-x-auto rounded-sm border">
            <table class="w-full text-sm">
                <thead class="border-b bg-muted/40 text-left">
                    <tr>
                        <th class="px-4 py-2 font-medium">
                            {{ $t('admin.targets.domain') }}
                        </th>
                        <th class="px-4 py-2 font-medium">
                            {{ $t('admin.targets.owner') }}
                        </th>
                        <th class="px-4 py-2 font-medium">
                            {{ $t('admin.targets.interval') }}
                        </th>
                        <th class="px-4 py-2 text-right font-medium">
                            {{ $t('admin.targets.rate') }}
                        </th>
                        <th class="px-4 py-2 font-medium">
                            {{ $t('admin.targets.status') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="monitor in monitors" :key="monitor.uuid">
                        <td class="px-4 py-2">
                            <p class="font-medium">{{ monitor.name }}</p>
                            <p
                                class="truncate font-mono text-xs text-muted-foreground"
                            >
                                {{ monitor.url }}
                            </p>
                        </td>
                        <td class="px-4 py-2">
                            <p>{{ monitor.owner.name }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ monitor.owner.email }}
                            </p>
                        </td>
                        <td class="px-4 py-2">
                            {{ formatInterval(monitor.interval_seconds) }}
                        </td>
                        <td class="px-4 py-2 text-right font-mono">
                            {{ monitor.requests_per_minute.toFixed(3) }}
                        </td>
                        <td class="px-4 py-2">
                            <Badge
                                :variant="
                                    monitor.is_active ? 'default' : 'outline'
                                "
                            >
                                {{
                                    monitor.is_active
                                        ? $t('admin.targets.active')
                                        : $t('admin.targets.paused')
                                }}
                            </Badge>
                            <p
                                v-if="monitor.paused_reason"
                                class="mt-0.5 text-xs text-muted-foreground"
                            >
                                {{ monitor.paused_reason }}
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <ConfirmDialog
        v-model:open="confirmingPause"
        :title="trans('admin.targets.pause_all')"
        :description="
            trans('admin.targets.pause_all_confirm', { domain: props.domain })
        "
        :confirm-label="trans('admin.targets.pause_all')"
        destructive
        @confirm="pauseAll"
    />
</template>
