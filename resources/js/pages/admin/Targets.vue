<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { CrosshairIcon } from 'lucide-vue-next';
import EmptyState from '@/components/EmptyState.vue';
import { Badge } from '@/components/ui/badge';
import * as targetsRoute from '@/routes/admin/targets';

type Target = {
    domain: string;
    monitor_count: number;
    account_count: number;
    requests_per_minute: number;
    verified: boolean;
};

const props = defineProps<{
    targets: Target[];
    limits: {
        per_domain: number | null;
        per_domain_per_user: number | null;
    };
}>();

setLayoutProps({
    breadcrumbs: [{ title: 'Targets', href: targetsRoute.index().url }],
});

function overLimit(target: Target): boolean {
    return (
        props.limits.per_domain !== null &&
        target.requests_per_minute > props.limits.per_domain
    );
}
</script>

<template>
    <Head :title="$t('admin.targets.title')" />

    <div class="px-4 py-6">
        <h1 class="text-xl font-semibold">{{ $t('admin.targets.title') }}</h1>
        <p class="mt-1 text-sm text-muted-foreground">
            {{ $t('admin.targets.description') }}
        </p>

        <p
            v-if="limits.per_domain === null"
            class="mt-3 rounded-sm border px-3 py-2 text-xs text-muted-foreground"
        >
            {{ $t('admin.targets.no_limit') }}
        </p>

        <EmptyState
            v-if="targets.length === 0"
            class="mt-6"
            :icon="CrosshairIcon"
            :title="$t('admin.targets.empty')"
            :description="$t('admin.targets.description')"
        />

        <div v-else class="mt-6 overflow-x-auto rounded-sm border">
            <table class="w-full text-sm">
                <thead class="border-b bg-muted/40 text-left">
                    <tr>
                        <th class="px-4 py-2 font-medium">
                            {{ $t('admin.targets.domain') }}
                        </th>
                        <th class="px-4 py-2 text-right font-medium">
                            {{ $t('admin.targets.rate') }}
                        </th>
                        <th class="px-4 py-2 text-right font-medium">
                            {{ $t('admin.targets.monitors') }}
                        </th>
                        <th class="px-4 py-2 text-right font-medium">
                            {{ $t('admin.targets.accounts') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="target in targets"
                        :key="target.domain"
                        class="transition-colors hover:bg-muted/30"
                    >
                        <td class="px-4 py-2">
                            <Link
                                :href="targetsRoute.show(target.domain).url"
                                class="font-medium hover:underline"
                            >
                                {{ target.domain }}
                            </Link>
                            <Badge
                                :variant="
                                    target.verified ? 'default' : 'outline'
                                "
                                class="ml-2"
                            >
                                {{
                                    target.verified
                                        ? $t('admin.targets.verified')
                                        : $t('admin.targets.unverified')
                                }}
                            </Badge>
                            <p
                                v-if="overLimit(target)"
                                class="mt-0.5 text-xs text-destructive"
                            >
                                {{
                                    $t('admin.targets.over_limit', {
                                        limit: limits.per_domain,
                                    })
                                }}
                            </p>
                        </td>
                        <td
                            class="px-4 py-2 text-right font-mono"
                            :class="overLimit(target) ? 'text-destructive' : ''"
                        >
                            {{ target.requests_per_minute.toFixed(3) }}
                        </td>
                        <td class="px-4 py-2 text-right font-mono">
                            {{ target.monitor_count }}
                        </td>
                        <td class="px-4 py-2 text-right font-mono">
                            {{ target.account_count }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
