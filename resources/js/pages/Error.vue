<template>
    <Head :title="title" />

    <div
        class="flex min-h-screen items-center justify-center bg-background px-4 text-foreground"
    >
        <div class="w-full max-w-md text-center">
            <component
                :is="icon"
                class="mx-auto size-8 text-muted-foreground"
                aria-hidden="true"
            />

            <p
                class="mt-6 font-mono text-5xl font-semibold tracking-tight tabular-nums"
            >
                {{ status }}
            </p>

            <!-- Announced once, as a block. The countdown below is deliberately
                 left out of it: re-announcing every second is unusable. -->
            <div role="alert">
                <h1
                    class="mt-4 font-mono text-xs font-semibold tracking-[0.12em] uppercase"
                >
                    {{ title }}
                </h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ description }}
                </p>
            </div>

            <!-- A retry countdown is only meaningful for a throttle. -->
            <p
                v-if="retryAfter"
                class="mt-4 font-mono text-sm text-muted-foreground tabular-nums"
            >
                {{ $t('errors.retry_in', { seconds: remaining }) }}
            </p>

            <div class="mt-6 flex flex-wrap items-center justify-center gap-2">
                <Button
                    v-if="canReload"
                    :disabled="remaining > 0"
                    @click="reload"
                >
                    {{ $t('errors.actions.reload') }}
                </Button>

                <Button
                    :as="Link"
                    :href="homeHref"
                    :variant="canReload ? 'outline' : 'default'"
                >
                    {{ homeLabel }}
                </Button>

                <Button v-if="canGoBack" variant="ghost" @click="goBack">
                    {{ $t('errors.actions.back') }}
                </Button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ClockIcon,
    SearchXIcon,
    ServerCrashIcon,
    ShieldOffIcon,
    TriangleAlertIcon,
    WrenchIcon,
} from 'lucide-vue-next';
import type { Component } from 'vue';
import { computed, onUnmounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { t } from '@/lib/i18n';
import { dashboard } from '@/routes';
import type { Auth } from '@/types';

const props = withDefaults(
    defineProps<{
        status: number;
        retryAfter?: number;
    }>(),
    { retryAfter: undefined },
);

const page = usePage();

/**
 * A 404 for an unmatched URI never runs the "web" middleware group, so the
 * Inertia middleware never shares anything — auth included. Every shared prop
 * read on this page has to tolerate being absent.
 */
const isAuthenticated = computed(
    () => (page.props.auth as Auth | undefined)?.user != null,
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

/** Named keys rather than numbers, matching lang/en/errors.php. */
const keys: Record<number, string> = {
    403: 'forbidden',
    404: 'not_found',
    419: 'expired',
    429: 'rate_limited',
    500: 'server',
    503: 'unavailable',
};

const icons: Record<number, Component> = {
    403: ShieldOffIcon,
    404: SearchXIcon,
    419: ClockIcon,
    429: ClockIcon,
    500: ServerCrashIcon,
    503: WrenchIcon,
};

const key = computed(() => keys[props.status] ?? 'default');
const icon = computed(() => icons[props.status] ?? TriangleAlertIcon);

const title = computed(() => t(`errors.${key.value}.title`));
const description = computed(() => t(`errors.${key.value}.description`));

/**
 * Retrying only makes sense where the same URL might now work. For a 403 or a
 * 404 it never will, so those get navigation instead.
 */
// 503 is normally served by the blade fallback rather than this page, but the
// mapping stays complete in case one ever reaches Inertia.
const canReload = computed(() => [419, 429, 500, 503].includes(props.status));
const canGoBack = computed(() => [403, 404].includes(props.status));

// A guest can be throttled too, and must not be sent somewhere they cannot
// reach — so the dashboard is only offered once we know someone is signed in.
const homeHref = computed(() =>
    isAuthenticated.value ? dashboard().url : '/',
);
const homeLabel = computed(() =>
    isAuthenticated.value
        ? t('errors.actions.dashboard')
        : t('errors.actions.home'),
);

// A full load, not an Inertia visit: the failure may have been the XHR itself.
const reload = () => window.location.reload();
const goBack = () => window.history.back();
</script>
