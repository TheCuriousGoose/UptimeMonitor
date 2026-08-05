<template>
    <MarketingPage
        :title="$t('marketing.features.title')"
        :subtitle="$t('marketing.features.subtitle')"
        width="wide"
    >
        <div class="divide-y">
            <section
                v-for="section in sections"
                :key="section.key"
                class="grid gap-6 py-10 first:pt-0 md:grid-cols-[minmax(0,20rem)_minmax(0,1fr)] md:gap-12"
            >
                <div>
                    <component
                        :is="section.icon"
                        class="size-5 text-primary"
                        aria-hidden="true"
                    />
                    <h2 class="mt-4 text-lg font-medium tracking-tight">
                        {{
                            $t(
                                `marketing.features.sections.${section.key}.title`,
                            )
                        }}
                    </h2>
                    <p class="mt-2 text-sm text-muted-foreground">
                        {{
                            $t(
                                `marketing.features.sections.${section.key}.body`,
                            )
                        }}
                    </p>
                </div>

                <div>
                    <p
                        class="font-mono text-[10px] tracking-[0.12em] text-muted-foreground uppercase"
                    >
                        {{ $t('marketing.features.spec_label') }}
                    </p>
                    <ul class="mt-3 divide-y rounded-sm border">
                        <li
                            v-for="(point, index) in points(section.key)"
                            :key="index"
                            class="flex gap-3 px-4 py-3 text-sm"
                        >
                            <CheckIcon
                                class="mt-0.5 size-4 shrink-0 text-primary"
                                aria-hidden="true"
                            />
                            <span class="text-muted-foreground">{{
                                point
                            }}</span>
                        </li>
                    </ul>
                </div>
            </section>
        </div>

        <div
            class="mt-12 flex flex-col items-start gap-4 border-t pt-8 sm:flex-row sm:items-center sm:justify-between"
        >
            <p class="text-sm text-muted-foreground">
                {{ $t('marketing.home.closing.body') }}
            </p>
            <Button :as="Link" :href="register()" class="shrink-0">
                {{ $t('marketing.home.cta_primary') }}
                <ArrowRightIcon />
            </Button>
        </div>
    </MarketingPage>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRightIcon,
    BellIcon,
    CheckIcon,
    GlobeIcon,
    RadioIcon,
    ShieldCheckIcon,
    SirenIcon,
    TerminalIcon,
} from 'lucide-vue-next';
import MarketingPage from '@/components/marketing/MarketingPage.vue';
import { Button } from '@/components/ui/button';
import { tList } from '@/lib/i18n';
import { register } from '@/routes';

const sections = [
    { key: 'checks', icon: RadioIcon },
    { key: 'confirmation', icon: ShieldCheckIcon },
    { key: 'incidents', icon: SirenIcon },
    { key: 'alerts', icon: BellIcon },
    { key: 'status', icon: GlobeIcon },
    { key: 'api', icon: TerminalIcon },
];

const points = (key: string): string[] =>
    tList(`marketing.features.sections.${key}.points`);
</script>
