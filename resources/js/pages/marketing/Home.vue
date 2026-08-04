<template>
    <Head :title="$t('marketing.home.title')" />

    <!-- Hero -->
    <section class="border-b">
        <div class="mx-auto w-full max-w-6xl px-4 py-20 md:py-28">
            <p
                class="font-mono text-[11px] tracking-[0.14em] text-primary uppercase"
            >
                {{ $t('marketing.home.eyebrow') }}
            </p>
            <h1
                class="mt-4 max-w-3xl text-4xl font-semibold tracking-tight text-balance md:text-5xl"
            >
                {{ $t('marketing.home.title') }}
            </h1>
            <p class="mt-5 max-w-2xl text-lg text-muted-foreground">
                {{ $t('marketing.home.subtitle') }}
            </p>
            <div class="mt-8 flex flex-wrap items-center gap-3">
                <Button :as="Link" :href="register()" size="lg">
                    {{ $t('marketing.home.cta_primary') }}
                    <ArrowRightIcon />
                </Button>
                <Button
                    :as="Link"
                    :href="docs.index()"
                    variant="outline"
                    size="lg"
                >
                    {{ $t('marketing.home.cta_secondary') }}
                </Button>
            </div>
        </div>
    </section>

    <!-- Ledger strip, same language as the in-app dashboard -->
    <section class="border-b">
        <div class="mx-auto w-full max-w-6xl px-4">
            <dl class="grid grid-cols-2 divide-x divide-y lg:grid-cols-4 lg:divide-y-0">
                <div
                    v-for="stat in stats"
                    :key="stat.key"
                    class="flex flex-col gap-1.5 px-5 py-6 first:pl-0 lg:last:pr-0"
                >
                    <dd
                        class="font-mono text-3xl leading-none font-semibold tabular-nums"
                    >
                        {{ $t(`marketing.home.stats.${stat.key}.value`) }}
                    </dd>
                    <dt
                        class="text-xs tracking-wide text-muted-foreground uppercase"
                    >
                        {{ $t(`marketing.home.stats.${stat.key}.label`) }}
                    </dt>
                </div>
            </dl>
        </div>
    </section>

    <!-- Features -->
    <section class="mx-auto w-full max-w-6xl px-4 py-20">
        <h2 class="max-w-2xl text-2xl font-semibold tracking-tight md:text-3xl">
            {{ $t('marketing.home.features_heading') }}
        </h2>

        <div class="mt-10 grid gap-px overflow-hidden rounded-md border bg-border md:grid-cols-2 lg:grid-cols-3">
            <article
                v-for="feature in features"
                :key="feature.key"
                class="flex flex-col bg-background p-6"
            >
                <component
                    :is="feature.icon"
                    class="size-5 text-primary"
                    aria-hidden="true"
                />
                <h3 class="mt-4 font-medium">
                    {{ $t(`marketing.home.features.${feature.key}.title`) }}
                </h3>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ $t(`marketing.home.features.${feature.key}.body`) }}
                </p>
            </article>
        </div>
    </section>

    <!-- Closing -->
    <section class="border-t">
        <div
            class="mx-auto flex w-full max-w-6xl flex-col items-start gap-6 px-4 py-16 md:flex-row md:items-center md:justify-between"
        >
            <div>
                <h2 class="text-2xl font-semibold tracking-tight">
                    {{ $t('marketing.home.closing.title') }}
                </h2>
                <p class="mt-2 text-muted-foreground">
                    {{ $t('marketing.home.closing.body') }}
                </p>
            </div>
            <Button :as="Link" :href="register()" size="lg" class="shrink-0">
                {{ $t('marketing.home.cta_primary') }}
                <ArrowRightIcon />
            </Button>
        </div>
    </section>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRightIcon,
    BellIcon,
    GlobeIcon,
    RadioIcon,
    ShieldCheckIcon,
    SirenIcon,
    TerminalIcon,
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { register } from '@/routes';
import docs from '@/routes/docs';

defineProps<{
    canRegister?: boolean;
}>();

const stats = [
    { key: 'interval' },
    { key: 'types' },
    { key: 'channels' },
    { key: 'retention' },
];

const features = [
    { key: 'checks', icon: RadioIcon },
    { key: 'confirmation', icon: ShieldCheckIcon },
    { key: 'incidents', icon: SirenIcon },
    { key: 'alerts', icon: BellIcon },
    { key: 'status', icon: GlobeIcon },
    { key: 'api', icon: TerminalIcon },
];
</script>
