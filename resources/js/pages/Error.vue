<template>
    <Head :title="title" />

    <div
        class="flex min-h-screen items-center justify-center bg-background px-4 text-foreground"
    >
        <div class="w-full max-w-md text-center">
            <p
                class="font-mono text-5xl font-semibold tracking-tight tabular-nums"
            >
                {{ status }}
            </p>
            <h1
                class="mt-4 font-mono text-xs font-semibold tracking-[0.12em] uppercase"
            >
                {{ title }}
            </h1>
            <p class="mt-2 text-sm text-muted-foreground">
                {{ description }}
            </p>

            <!-- A retry countdown is only meaningful for a throttle. -->
            <p
                v-if="retryAfter"
                class="mt-4 font-mono text-sm tabular-nums text-muted-foreground"
            >
                {{ remaining }}s
            </p>

            <!-- Plain "/" rather than the dashboard: a guest can be throttled
                 too, and must not be sent somewhere they cannot reach. -->
            <Button :as="Link" href="/" variant="outline" class="mt-6">
                {{ $t('base.rate_limited.back') }}
            </Button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, onUnmounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';

const props = withDefaults(
    defineProps<{
        status: number;
        retryAfter?: number;
    }>(),
    { retryAfter: undefined },
);

const remaining = ref(props.retryAfter ?? 0);
let timer: ReturnType<typeof setInterval> | undefined;

watch(
    () => props.retryAfter,
    (value) => {
        clearInterval(timer);
        remaining.value = value ?? 0;

        if (!value) {
            return;
        }

        timer = setInterval(() => {
            remaining.value = Math.max(0, remaining.value - 1);

            if (remaining.value === 0) {
                clearInterval(timer);
            }
        }, 1000);
    },
    { immediate: true },
);

onUnmounted(() => clearInterval(timer));

const titles: Record<number, string> = {
    403: 'Forbidden',
    404: 'Not found',
    429: 'Too many requests',
    500: 'Server error',
    503: 'Unavailable',
};

const title = computed(() => titles[props.status] ?? 'Something went wrong');

const description = computed(() =>
    props.status === 429
        ? 'You have made too many requests in a short period. Give it a moment and try again.'
        : 'That request could not be completed.',
);
</script>
