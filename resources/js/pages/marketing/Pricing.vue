<template>
    <MarketingPage
        :title="$t('marketing.pricing.title')"
        :subtitle="$t('marketing.pricing.subtitle')"
    >
        <div class="rounded-md border">
            <div class="border-b p-6">
                <p
                    class="font-mono text-[11px] tracking-[0.14em] text-muted-foreground uppercase"
                >
                    {{ $t('marketing.pricing.plans.self_hosted.name') }}
                </p>
                <p class="mt-3 flex items-baseline gap-2">
                    <span class="text-4xl font-semibold tracking-tight">{{
                        $t('marketing.pricing.plans.self_hosted.price')
                    }}</span>
                    <span class="text-sm text-muted-foreground">{{
                        $t('marketing.pricing.plans.self_hosted.period')
                    }}</span>
                </p>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ $t('marketing.pricing.plans.self_hosted.description') }}
                </p>
                <Button :as="Link" :href="register()" class="mt-6 w-full">
                    {{ $t('marketing.pricing.plans.self_hosted.cta') }}
                </Button>
            </div>

            <ul class="divide-y">
                <li
                    v-for="(feature, index) in planFeatures"
                    :key="index"
                    class="flex items-center gap-3 px-6 py-3 text-sm"
                >
                    <CheckIcon class="size-4 shrink-0 text-primary" />
                    {{ feature }}
                </li>
            </ul>
        </div>

        <p class="mt-6 text-sm text-muted-foreground">
            {{ $t('marketing.pricing.note') }}
        </p>
    </MarketingPage>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CheckIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import MarketingPage from '@/components/marketing/MarketingPage.vue';
import { Button } from '@/components/ui/button';
import { tList } from '@/lib/i18n';
import { register } from '@/routes';

const planFeatures = computed(() =>
    tList('marketing.pricing.plans.self_hosted.features'),
);
</script>
