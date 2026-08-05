<template>
    <MarketingPage
        width="wide"
        :title="$t('content.public.blog.title')"
        :subtitle="$t('content.public.blog.subtitle')"
    >
        <p
            v-if="entries.length === 0"
            class="rounded-sm border border-dashed p-10 text-center text-sm text-muted-foreground"
        >
            {{ $t('content.public.blog.empty') }}
        </p>

        <ul v-else class="grid gap-4 sm:grid-cols-2">
            <li v-for="entry in entries" :key="entry.uuid" class="flex">
                <Link
                    :href="show({ segment: 'blog', slug: entry.slug }).url"
                    class="group flex w-full flex-col rounded-sm border p-5 transition-colors hover:border-foreground/25 hover:bg-accent focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                >
                    <p
                        class="font-mono text-xs tabular-nums text-muted-foreground"
                    >
                        {{ formatDateTime(entry.published_at) }}
                    </p>
                    <h2
                        class="mt-2 text-lg font-medium tracking-tight underline-offset-4 group-hover:underline"
                    >
                        {{ entry.title }}
                    </h2>
                    <p
                        v-if="entry.excerpt"
                        class="mt-2 text-sm text-muted-foreground"
                    >
                        {{ entry.excerpt }}
                    </p>
                    <span
                        class="mt-4 font-mono text-[10px] tracking-wide text-primary uppercase"
                    >
                        {{ $t('content.public.blog.read') }}
                    </span>
                </Link>
            </li>
        </ul>
    </MarketingPage>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import MarketingPage from '@/components/marketing/MarketingPage.vue';
import { formatDateTime } from '@/lib/format';
import { show } from '@/routes/content';
import type { ContentEntry } from '@/types/content';

defineProps<{ entries: ContentEntry[] }>();
</script>
