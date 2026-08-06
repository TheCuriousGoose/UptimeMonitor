<template>
    <SeoHead
        :title="entry.title"
        :description="entry.excerpt ?? undefined"
        type="article"
    />

    <div class="mx-auto w-full max-w-6xl px-4 py-16">
        <div class="grid gap-12 lg:grid-cols-[16rem_minmax(0,1fr)]">
            <!-- Docs read as a manual, so they keep a sibling index beside
                 them; blog and changelog posts stand alone. -->
            <aside v-if="entry.type === 'doc'" class="hidden lg:block">
                <nav class="sticky top-24 space-y-6">
                    <!-- Grouped, not flat: the list is ordered by category, so
                         without the headings the sequence looks arbitrary. -->
                    <div v-for="group in grouped" :key="group.category">
                        <p
                            class="mb-2 font-mono text-[10px] font-semibold tracking-[0.12em] text-muted-foreground uppercase"
                        >
                            {{ group.category }}
                        </p>
                        <div class="border-l">
                            <Link
                                v-for="sibling in group.entries"
                                :key="sibling.uuid"
                                :href="
                                    show({
                                        segment: 'docs',
                                        slug: sibling.slug,
                                    }).url
                                "
                                class="-ml-px block border-l-2 py-1.5 pl-3 text-sm transition-colors"
                                :class="
                                    sibling.uuid === entry.uuid
                                        ? 'border-primary font-medium text-foreground'
                                        : 'border-transparent text-muted-foreground hover:text-foreground'
                                "
                            >
                                {{ sibling.title }}
                            </Link>
                        </div>
                    </div>
                </nav>
            </aside>

            <article class="max-w-3xl min-w-0">
                <Link
                    :href="indexHref"
                    class="inline-flex items-center gap-1.5 font-mono text-[11px] tracking-wide text-muted-foreground uppercase hover:text-foreground"
                >
                    <ArrowLeftIcon class="size-3.5" />
                    {{ $t(`content.types_plural.${entry.type}`) }}
                </Link>

                <header class="mt-4 border-b pb-6">
                    <div
                        v-if="entry.version || entry.published_at"
                        class="flex flex-wrap items-center gap-2"
                    >
                        <Badge v-if="entry.version" variant="outline">{{
                            entry.version
                        }}</Badge>
                        <span
                            v-if="entry.published_at"
                            class="font-mono text-xs text-muted-foreground tabular-nums"
                        >
                            {{ formatDateTime(entry.published_at) }}
                        </span>
                    </div>
                    <h1
                        class="mt-2 text-3xl font-semibold tracking-tight text-balance"
                    >
                        {{ entry.title }}
                    </h1>
                    <p v-if="entry.excerpt" class="mt-2 text-muted-foreground">
                        {{ entry.excerpt }}
                    </p>
                </header>

                <!-- Rendered server-side from markdown with raw HTML stripped
                     and unsafe link schemes dropped; see MarkdownRenderer. -->
                <div class="prose-console mt-8" v-html="bodyHtml" />
            </article>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeftIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import SeoHead from '@/components/SeoHead.vue';
import { Badge } from '@/components/ui/badge';
import { groupByCategory } from '@/lib/content';
import { formatDateTime } from '@/lib/format';
import blog from '@/routes/blog';
import changelog from '@/routes/changelog';
import { show } from '@/routes/content';
import docs from '@/routes/docs';
import type { ContentEntry } from '@/types/content';

const props = defineProps<{
    entry: ContentEntry;
    bodyHtml: string;
    siblings: ContentEntry[];
}>();

const grouped = computed(() => groupByCategory(props.siblings));

const indexHref = computed(() => {
    switch (props.entry.type) {
        case 'doc':
            return docs.index().url;
        case 'changelog':
            return changelog.index().url;
        default:
            return blog.index().url;
    }
});
</script>
