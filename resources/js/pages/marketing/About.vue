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

        <section class="mt-12">
            <h2 class="text-lg font-medium tracking-tight">
                {{ $t('marketing.about.principles_heading') }}
            </h2>

            <dl class="mt-6 divide-y border-y">
                <div
                    v-for="principle in principles"
                    :key="principle"
                    class="py-5"
                >
                    <dt class="font-medium">
                        {{
                            $t(`marketing.about.principles.${principle}.title`)
                        }}
                    </dt>
                    <dd class="mt-1.5 text-sm text-muted-foreground">
                        {{ $t(`marketing.about.principles.${principle}.body`) }}
                    </dd>
                </div>
            </dl>
        </section>

        <section class="mt-12">
            <p
                class="font-mono text-[10px] tracking-[0.12em] text-muted-foreground uppercase"
            >
                {{ $t('marketing.about.stack_heading') }}
            </p>
            <ul class="mt-3 flex flex-wrap gap-1.5">
                <li v-for="item in stack" :key="item">
                    <Badge variant="secondary">{{ item }}</Badge>
                </li>
            </ul>
        </section>

        <section
            class="mt-12 flex flex-col items-start gap-4 border-t pt-8 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h2 class="font-medium">
                    {{ $t('marketing.about.cta_heading') }}
                </h2>
                <p class="mt-1.5 text-sm text-muted-foreground">
                    {{ $t('marketing.about.cta_body') }}
                </p>
            </div>
            <Button :as="Link" :href="register()" class="shrink-0">
                {{ $t('marketing.home.cta_primary') }}
                <ArrowRightIcon />
            </Button>
        </section>
    </MarketingPage>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRightIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import MarketingPage from '@/components/marketing/MarketingPage.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { tList, trans } from '@/lib/i18n';
import { register } from '@/routes';

const paragraphs = computed(() =>
    trans('marketing.about.body').split('\n').filter(Boolean),
);

const principles = ['honest', 'quiet', 'boring', 'yours'];

const stack = computed(() => tList('marketing.about.stack'));
</script>
