<template>
    <MarketingPage
        :title="$t('content.public.changelog.title')"
        :subtitle="$t('content.public.changelog.subtitle')"
    >
        <p
            v-if="entries.length === 0"
            class="rounded-sm border border-dashed p-10 text-center text-sm text-muted-foreground"
        >
            {{ $t('content.public.changelog.empty') }}
        </p>

        <ul v-else class="divide-y">
            <li v-for="entry in entries" :key="entry.uuid" class="py-6 first:pt-0">
                <Link :href="show({ segment: 'changelog', slug: entry.slug }).url" class="block group">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge v-if="entry.version" variant="outline">{{
                            entry.version
                        }}</Badge>
                        <span
                            class="font-mono text-xs tabular-nums text-muted-foreground"
                        >
                            {{ formatDateTime(entry.published_at) }}
                        </span>
                    </div>
                    <h2
                        class="mt-2 font-medium group-hover:underline underline-offset-4"
                    >
                        {{ entry.title }}
                    </h2>
                    <p
                        v-if="entry.excerpt"
                        class="mt-1.5 text-sm text-muted-foreground"
                    >
                        {{ entry.excerpt }}
                    </p>
                </Link>
            </li>
        </ul>
    </MarketingPage>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import MarketingPage from '@/components/marketing/MarketingPage.vue';
import { Badge } from '@/components/ui/badge';
import { formatDateTime } from '@/lib/format';
import { show } from '@/routes/content';
import type { ContentEntry } from '@/types/content';

defineProps<{ entries: ContentEntry[] }>();
</script>
