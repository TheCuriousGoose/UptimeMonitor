<template>
    <Head :title="title">
        <meta v-if="description" name="description" :content="description" />

        <!-- Open Graph and Twitter both, because the two crawlers do not read
             each other's tags and a link with no card is a link nobody clicks. -->
        <meta property="og:type" :content="type" head-key="og:type" />
        <meta property="og:site_name" :content="appName" head-key="og:site" />
        <meta property="og:title" :content="title" head-key="og:title" />
        <meta
            v-if="description"
            property="og:description"
            :content="description"
            head-key="og:description"
        />
        <meta property="og:url" :content="url" head-key="og:url" />

        <meta
            name="twitter:card"
            content="summary_large_image"
            head-key="tw:card"
        />
        <meta name="twitter:title" :content="title" head-key="tw:title" />
        <meta
            v-if="description"
            name="twitter:description"
            :content="description"
            head-key="tw:description"
        />
    </Head>
</template>

<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        title: string;
        description?: string;
        type?: string;
    }>(),
    { type: 'website' },
);

const page = usePage();

const appName = computed(() => (page.props.name as string) ?? '');

// The Inertia page url is path-only, so it needs the origin to be absolute —
// a relative og:url is ignored by every crawler that reads it.
const url = computed(() =>
    typeof window === 'undefined'
        ? page.url
        : new URL(page.url, window.location.origin).toString(),
);

defineExpose({ description: props.description });
</script>
