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

        <section class="mt-12">
            <h2 class="text-lg font-medium tracking-tight">
                {{ $t('marketing.contact.routes_heading') }}
            </h2>

            <dl class="mt-4 divide-y rounded-sm border">
                <div
                    v-for="route in routes"
                    :key="route.key"
                    class="flex gap-4 px-4 py-4"
                >
                    <component
                        :is="route.icon"
                        class="mt-0.5 size-4 shrink-0 text-primary"
                        aria-hidden="true"
                    />
                    <div>
                        <dt class="text-sm font-medium">
                            {{
                                $t(
                                    `marketing.contact.routes.${route.key}.title`,
                                )
                            }}
                        </dt>
                        <dd class="mt-1.5 text-sm text-muted-foreground">
                            {{
                                $t(`marketing.contact.routes.${route.key}.body`)
                            }}
                        </dd>
                    </div>
                </div>
            </dl>
        </section>

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
import {
    BookOpenIcon,
    BugIcon,
    ExternalLinkIcon,
    LightbulbIcon,
    ShieldAlertIcon,
} from 'lucide-vue-next';
import MarketingPage from '@/components/marketing/MarketingPage.vue';
import { Button } from '@/components/ui/button';

const routes = [
    { key: 'bug', icon: BugIcon },
    { key: 'feature', icon: LightbulbIcon },
    { key: 'docs', icon: BookOpenIcon },
    { key: 'security', icon: ShieldAlertIcon },
];
</script>
