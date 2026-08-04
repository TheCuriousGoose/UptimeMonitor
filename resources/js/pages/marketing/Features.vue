<template>
    <MarketingPage
        :title="$t('marketing.features.title')"
        :subtitle="$t('marketing.features.subtitle')"
    >
        <div class="divide-y">
            <article
                v-for="feature in features"
                :key="feature.key"
                class="flex gap-4 py-6 first:pt-0"
            >
                <component
                    :is="feature.icon"
                    class="mt-0.5 size-5 shrink-0 text-primary"
                    aria-hidden="true"
                />
                <div>
                    <h2 class="font-medium">
                        {{ $t(`marketing.home.features.${feature.key}.title`) }}
                    </h2>
                    <p class="mt-1.5 text-sm text-muted-foreground">
                        {{ $t(`marketing.home.features.${feature.key}.body`) }}
                    </p>
                </div>
            </article>
        </div>
    </MarketingPage>
</template>

<script setup lang="ts">
import {
    BellIcon,
    GlobeIcon,
    RadioIcon,
    ShieldCheckIcon,
    SirenIcon,
    TerminalIcon,
} from 'lucide-vue-next';
import MarketingPage from '@/components/marketing/MarketingPage.vue';

const features = [
    { key: 'checks', icon: RadioIcon },
    { key: 'confirmation', icon: ShieldCheckIcon },
    { key: 'incidents', icon: SirenIcon },
    { key: 'alerts', icon: BellIcon },
    { key: 'status', icon: GlobeIcon },
    { key: 'api', icon: TerminalIcon },
];
</script>
