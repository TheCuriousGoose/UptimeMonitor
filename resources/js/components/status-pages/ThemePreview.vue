<template>
    <div class="grid gap-1.5">
        <Label>{{ $t('status_pages.theme.preview') }}</Label>

        <div
            class="overflow-hidden rounded-sm border"
            :style="vars"
            :aria-label="$t('status_pages.theme.preview')"
            role="img"
        >
            <div
                class="bg-[var(--sp-bg)] px-3 py-4 font-[family-name:var(--sp-font)] text-[var(--sp-fg)]"
            >
                <!-- The column takes the same share of the preview that the
                     chosen width takes of the maximum, so dragging the width
                     reads as a real change rather than nothing at all. -->
                <div class="mx-auto" :style="{ width: `${columnWidth}%` }">
                    <div
                        class="border-b border-[var(--sp-border)] pb-2.5"
                        :class="theme.logo_url ? 'text-center' : ''"
                    >
                        <img
                            v-if="theme.logo_url"
                            :src="theme.logo_url"
                            alt=""
                            class="mx-auto mb-2 h-6 w-auto max-w-[140px] object-contain"
                            referrerpolicy="no-referrer"
                        />
                        <p class="text-sm font-semibold tracking-tight">
                            {{ title || $t('status_pages.theme.sample.title') }}
                        </p>
                        <div
                            v-if="theme.links.length"
                            class="mt-1 flex flex-wrap gap-x-3 text-[10px]"
                            :class="theme.logo_url ? 'justify-center' : ''"
                        >
                            <span
                                v-for="link in theme.links"
                                :key="link.url"
                                class="text-[var(--sp-brand)] underline underline-offset-2"
                            >
                                {{ link.label }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="mt-3 flex items-center gap-2 rounded-[var(--sp-radius)] border border-l-2 px-2.5 py-2 text-xs font-medium"
                        :style="banner"
                    >
                        <CheckCircle2Icon class="size-3.5 shrink-0" />
                        {{ $t('status_pages.public.all_operational') }}
                    </div>

                    <div
                        class="mt-3 divide-y divide-[var(--sp-border)] overflow-hidden rounded-[var(--sp-radius)] border border-[var(--sp-border)] bg-[var(--sp-surface)]"
                    >
                        <div
                            v-for="row in rows"
                            :key="row.name"
                            class="px-2.5 py-2"
                        >
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <span
                                    class="flex items-center gap-1.5 text-xs font-medium"
                                >
                                    <component
                                        :is="
                                            row.down
                                                ? AlertTriangleIcon
                                                : CheckCircle2Icon
                                        "
                                        class="size-3 shrink-0"
                                        :style="{
                                            color: row.down
                                                ? 'var(--sp-down)'
                                                : 'var(--sp-up)',
                                        }"
                                    />
                                    {{ row.name }}
                                </span>
                                <span
                                    class="text-[10px] text-[var(--sp-muted-fg)] tabular-nums"
                                >
                                    {{ row.uptime }}
                                </span>
                            </div>
                            <div class="mt-1.5 flex h-4 items-end gap-px">
                                <span
                                    v-for="(bar, index) in row.bars"
                                    :key="index"
                                    class="flex-1 rounded-t-[1px]"
                                    :style="{
                                        height:
                                            bar === 0
                                                ? '30%'
                                                : bar === 1
                                                  ? '55%'
                                                  : '100%',
                                        backgroundColor:
                                            bar === 0
                                                ? 'var(--sp-empty)'
                                                : bar === 1
                                                  ? 'var(--sp-up)'
                                                  : 'var(--sp-down)',
                                    }"
                                />
                            </div>
                        </div>
                    </div>

                    <p
                        v-if="theme.footer_text"
                        class="mt-2.5 text-center text-[10px] text-[var(--sp-muted-fg)]"
                    >
                        {{ theme.footer_text }}
                    </p>
                </div>
            </div>
        </div>

        <p v-if="theme.mode === 'system'" class="text-xs text-muted-foreground">
            {{ $t('status_pages.theme.preview_system') }}
        </p>
    </div>
</template>

<script setup lang="ts">
import { AlertTriangleIcon, CheckCircle2Icon } from 'lucide-vue-next';
import type { CSSProperties } from 'vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Label } from '@/components/ui/label';
import { trans } from '@/lib/i18n';
import { palette, previewScheme, THEME_LIMITS } from '@/lib/statusPageTheme';
import type { StatusPageTheme } from '@/types/monitors';

const props = defineProps<{
    theme: StatusPageTheme;
    /** The page's own title, so the preview shows the real thing where set. */
    title?: string;
}>();

/**
 * Only consulted for `system` mode, where the answer is the visitor's and not
 * the owner's — the preview shows whichever the person editing is looking at.
 */
const prefersDark = ref(false);
let query: MediaQueryList | null = null;

function syncScheme(event: MediaQueryList | MediaQueryListEvent) {
    prefersDark.value = event.matches;
}

onMounted(() => {
    query = window.matchMedia('(prefers-color-scheme: dark)');
    syncScheme(query);
    query.addEventListener('change', syncScheme);
});

onUnmounted(() => query?.removeEventListener('change', syncScheme));

const vars = computed(
    () =>
        palette(
            props.theme,
            previewScheme(props.theme.mode, prefersDark.value),
        ) as CSSProperties,
);

const columnWidth = computed(() =>
    Math.round(
        (Math.min(props.theme.width, THEME_LIMITS.width.max) /
            THEME_LIMITS.width.max) *
            100,
    ),
);

const banner = computed<CSSProperties>(() => ({
    color: 'var(--sp-up)',
    borderColor: 'color-mix(in srgb, var(--sp-up) 30%, transparent)',
    backgroundColor: 'color-mix(in srgb, var(--sp-up) 10%, var(--sp-bg))',
}));

/** 0 = no data, 1 = up, 2 = down — enough to show all three bar colours. */
const rows = computed(() => [
    {
        name: trans('status_pages.theme.sample.api'),
        uptime: '100%',
        down: false,
        bars: [0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1],
    },
    {
        name: trans('status_pages.theme.sample.website'),
        uptime: '99.4%',
        down: true,
        bars: [1, 1, 1, 1, 1, 2, 1, 1, 1, 1, 1, 1, 2, 1, 1, 1, 1, 1],
    },
]);
</script>
