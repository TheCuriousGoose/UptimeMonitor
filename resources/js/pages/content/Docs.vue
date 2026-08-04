<template>
    <Head :title="$t('content.public.docs.title')" />

    <div class="mx-auto w-full max-w-6xl px-4 py-16">
        <header class="border-b pb-6">
            <h1 class="text-3xl font-semibold tracking-tight">
                {{ $t('content.public.docs.title') }}
            </h1>
            <p class="mt-2 text-muted-foreground">
                {{ $t('content.public.docs.subtitle') }}
            </p>
        </header>

        <p
            v-if="entries.length === 0"
            class="mt-8 rounded-sm border border-dashed p-10 text-center text-sm text-muted-foreground"
        >
            {{ $t('content.public.docs.empty') }}
        </p>

        <div v-else class="mt-8 grid gap-10 md:grid-cols-2 lg:grid-cols-3">
            <section v-for="group in grouped" :key="group.category">
                <h2
                    class="border-b pb-2 font-mono text-[11px] font-semibold tracking-[0.12em] text-muted-foreground uppercase"
                >
                    {{ group.category }}
                </h2>
                <ul class="mt-3 space-y-2">
                    <li v-for="entry in group.entries" :key="entry.uuid">
                        <Link
                            :href="show({ segment: 'docs', slug: entry.slug }).url"
                            class="text-sm underline-offset-4 hover:underline"
                        >
                            {{ entry.title }}
                        </Link>
                        <p
                            v-if="entry.excerpt"
                            class="mt-0.5 text-xs text-muted-foreground"
                        >
                            {{ entry.excerpt }}
                        </p>
                    </li>
                </ul>
            </section>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { groupByCategory } from '@/lib/content';
import { show } from '@/routes/content';
import type { ContentEntry } from '@/types/content';

const props = defineProps<{ entries: ContentEntry[] }>();

const grouped = computed(() => groupByCategory(props.entries));
</script>
