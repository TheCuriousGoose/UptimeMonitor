<template>
    <MarketingPage
        :title="$t('marketing.roadmap.title')"
        :subtitle="$t('marketing.roadmap.subtitle')"
    >
        <div class="divide-y rounded-sm border">
            <div
                v-for="item in items"
                :key="item.key"
                class="flex items-center justify-between gap-3 px-4 py-3"
            >
                <span class="text-sm">{{
                    $t(`marketing.roadmap.items.${item.key}.title`)
                }}</span>
                <Badge :variant="variants[item.status]">
                    {{ $t(`marketing.roadmap.status.${item.status}`) }}
                </Badge>
            </div>
        </div>
    </MarketingPage>
</template>

<script setup lang="ts">
import MarketingPage from '@/components/marketing/MarketingPage.vue';
import { Badge } from '@/components/ui/badge';

type Status = 'shipped' | 'building' | 'planned';

const variants: Record<Status, 'success' | 'default' | 'outline'> = {
    shipped: 'success',
    building: 'default',
    planned: 'outline',
};

const items: { key: string; status: Status }[] = [
    { key: 'monitors', status: 'shipped' },
    { key: 'incidents', status: 'shipped' },
    { key: 'status_pages', status: 'shipped' },
    { key: 'api', status: 'shipped' },
    { key: 'integrations', status: 'shipped' },
    { key: 'maintenance', status: 'planned' },
    { key: 'regions', status: 'planned' },
    { key: 'sla', status: 'planned' },
];
</script>
