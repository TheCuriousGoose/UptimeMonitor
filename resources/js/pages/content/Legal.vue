<template>
    <Head :title="entry.title" />

    <div class="mx-auto w-full max-w-3xl px-4 py-16">
        <header class="border-b pb-6">
            <h1 class="text-3xl font-semibold tracking-tight">
                {{ entry.title }}
            </h1>
            <p
                v-if="entry.published_at"
                class="mt-2 font-mono text-xs tabular-nums text-muted-foreground"
            >
                {{
                    $t('marketing.legal.updated', {
                        date: formatDateTime(entry.published_at),
                    })
                }}
            </p>
        </header>

        <!-- Rendered server-side from markdown with raw HTML stripped and
             unsafe link schemes dropped; see MarkdownRenderer. -->
        <div class="prose-console mt-8" v-html="bodyHtml" />
    </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { formatDateTime } from '@/lib/format';
import type { ContentEntry } from '@/types/content';

defineProps<{
    entry: ContentEntry;
    bodyHtml: string;
}>();
</script>
