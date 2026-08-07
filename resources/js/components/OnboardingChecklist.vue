<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import {
    ActivityIcon,
    BellIcon,
    CheckIcon,
    GlobeIcon,
    SparklesIcon,
    XIcon,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { csrfHeaders } from '@/lib/http';
import * as integrationsRoute from '@/routes/integrations';
import * as monitorsRoute from '@/routes/monitors';
import * as onboardingRoute from '@/routes/onboarding';
import * as statusPagesRoute from '@/routes/status-pages';
import type { OnboardingProgress } from '@/types/onboarding';

const props = defineProps<{
    progress: OnboardingProgress;
    /** Nothing exists yet, so this stands in for the dashboard rather than sitting above it. */
    hero?: boolean;
}>();

// Whether to show at all is the page's call — dismissing here would leave the
// dashboard with nothing in its place.
const emit = defineEmits<{ dismiss: [] }>();

const steps = computed(() => [
    {
        key: 'monitor',
        done: props.progress.has_monitor,
        icon: ActivityIcon,
        href: monitorsRoute.create().url,
    },
    {
        key: 'channel',
        done: props.progress.has_channel,
        icon: BellIcon,
        href: integrationsRoute.index().url,
    },
    {
        key: 'status_page',
        done: props.progress.has_status_page,
        icon: GlobeIcon,
        href: statusPagesRoute.index().url,
    },
]);

const completed = computed(
    () => steps.value.filter((step) => step.done).length,
);

// The first thing still outstanding, which is the one worth a button.
const next = computed(() => steps.value.find((step) => !step.done));

function dismiss() {
    emit('dismiss');

    fetch('/me/preferences', {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', ...csrfHeaders() },
        body: JSON.stringify({ onboarding_dismissed: true }),
    })
        // The panel is already gone locally; a failed write only means it
        // comes back on the next visit, which beats blocking on it.
        .catch(() => {});
}

function start() {
    if (next.value) {
        router.visit(next.value.href);
    }
}
</script>

<template>
    <section
        class="rounded-md border"
        :class="hero ? 'p-6' : 'p-4'"
        :aria-label="$t('onboarding.title')"
    >
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 :class="hero ? 'text-lg font-semibold' : 'font-medium'">
                    {{ $t('onboarding.title') }}
                </h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{
                        hero
                            ? $t('onboarding.description')
                            : $t('onboarding.progress', {
                                  done: completed,
                                  total: steps.length,
                              })
                    }}
                </p>
            </div>
            <Button variant="ghost" size="sm" @click="dismiss">
                <XIcon />
                <span class="sr-only">{{ $t('onboarding.dismiss') }}</span>
            </Button>
        </div>

        <ol class="mt-4 flex flex-col gap-2">
            <li
                v-for="step in steps"
                :key="step.key"
                class="flex items-start gap-3 rounded-sm border p-3 transition-colors"
                :class="step.done ? 'bg-muted/30' : 'hover:bg-accent'"
            >
                <span
                    class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full border"
                    :class="
                        step.done
                            ? 'border-emerald-600/40 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400'
                            : 'text-muted-foreground'
                    "
                >
                    <CheckIcon v-if="step.done" class="size-3" />
                    <component :is="step.icon" v-else class="size-3" />
                </span>

                <div class="min-w-0 flex-1">
                    <p
                        class="text-sm font-medium"
                        :class="
                            step.done
                                ? 'text-muted-foreground line-through'
                                : ''
                        "
                    >
                        {{ $t(`onboarding.steps.${step.key}.title`) }}
                    </p>
                    <p
                        v-if="!step.done"
                        class="mt-0.5 text-xs text-muted-foreground"
                    >
                        {{ $t(`onboarding.steps.${step.key}.description`) }}
                    </p>
                </div>

                <Link
                    v-if="!step.done"
                    :href="step.href"
                    class="shrink-0 self-center text-sm font-medium underline-offset-4 hover:underline"
                >
                    {{ $t(`onboarding.steps.${step.key}.action`) }}
                </Link>
            </li>
        </ol>

        <div v-if="next" class="mt-4 flex flex-wrap items-center gap-2">
            <Button @click="start">
                {{ $t(`onboarding.steps.${next.key}.action`) }}
            </Button>
            <!-- The guided flow covers the first two steps in one pass, so it
                 is only worth offering while they are both outstanding. -->
            <Button
                v-if="!progress.has_monitor && !progress.has_channel"
                :as="Link"
                variant="outline"
                :href="onboardingRoute.show().url"
            >
                <SparklesIcon />
                {{ $t('onboarding.launch') }}
            </Button>
        </div>
    </section>
</template>
