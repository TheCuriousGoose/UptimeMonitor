<template>
    <MarketingPage
        :title="$t('marketing.contact.title')"
        :subtitle="$t('marketing.contact.subtitle')"
    >
        <p class="text-muted-foreground">{{ $t('marketing.contact.body') }}</p>

        <Button
            as="a"
            href="https://github.com/TheCuriousGoose/UptimeMonitor/issues"
            target="_blank"
            rel="noopener noreferrer"
            variant="outline"
            class="mt-6"
        >
            <ExternalLinkIcon />
            {{ $t('marketing.contact.issues') }}
        </Button>

        <div class="mt-10 rounded-sm border border-l-2 border-l-primary p-4">
            <h2 class="text-sm font-medium">
                {{ $t('marketing.contact.security_heading') }}
            </h2>
            <p class="mt-1.5 text-sm text-muted-foreground">
                {{ $t('marketing.contact.security_body') }}
            </p>
        </div>
    </MarketingPage>
</template>

<script setup lang="ts">
import { ExternalLinkIcon } from 'lucide-vue-next';
import MarketingPage from '@/components/marketing/MarketingPage.vue';
import { Button } from '@/components/ui/button';
</script>
