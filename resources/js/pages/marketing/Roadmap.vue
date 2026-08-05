<template>
    <MarketingPage
        :title="$t('marketing.roadmap.title')"
        :subtitle="$t('marketing.roadmap.subtitle')"
    >
        <p class="text-sm text-muted-foreground">
            {{ $t('marketing.roadmap.note') }}
        </p>

        <section
            v-for="group in groups"
            :key="group.status"
            class="mt-10 first:mt-8"
        >
            <div class="flex items-center gap-3">
                <h2 class="text-lg font-medium tracking-tight">
                    {{ $t(`marketing.roadmap.groups.${group.status}`) }}
                </h2>
                <Badge :variant="variants[group.status]">
                    {{ $t(`marketing.roadmap.status.${group.status}`) }}
                </Badge>
            </div>

            <dl class="mt-4 divide-y rounded-sm border">
                <div v-for="key in group.items" :key="key" class="px-4 py-4">
                    <dt class="text-sm font-medium">
                        {{ $t(`marketing.roadmap.items.${key}.title`) }}
                    </dt>
                    <dd class="mt-1.5 text-sm text-muted-foreground">
                        {{ $t(`marketing.roadmap.items.${key}.body`) }}
                    </dd>
                </div>
            </dl>
        </section>
    </MarketingPage>
</template>

<script setup lang="ts">
import { computed } from 'vue';
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

/**
 * Grouped by status so the page reads as three answers rather than one list.
 * Empty groups are dropped — an "in progress" heading with nothing under it
 * says the wrong thing.
 */
const groups = computed(() =>
    (['building', 'planned', 'shipped'] as Status[])
        .map((status) => ({
            status,
            items: items
                .filter((item) => item.status === status)
                .map((item) => item.key),
        }))
        .filter((group) => group.items.length > 0),
);
</script>
