<template>
    <MarketingPage
        :title="$t('marketing.about.title')"
        :subtitle="$t('marketing.about.subtitle')"
    >
        <div class="space-y-4">
            <p
                v-for="(paragraph, index) in paragraphs"
                :key="index"
                class="text-muted-foreground"
            >
                {{ paragraph }}
            </p>
        </div>
    </MarketingPage>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import MarketingPage from '@/components/marketing/MarketingPage.vue';
import { trans } from '@/lib/i18n';

const paragraphs = computed(() =>
    trans('marketing.about.body').split('\n').filter(Boolean),
);
</script>
