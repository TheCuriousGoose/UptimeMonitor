<template>
    <div class="space-y-4">
        <div class="flex justify-end">
            <Button variant="ghost" size="sm" @click="emit('reset')">
                <RotateCcwIcon />
                {{ $t('status_pages.theme.reset') }}
            </Button>
        </div>

        <template v-if="section === 'branding'">
            <div class="grid gap-1.5">
                <Label for="theme-logo">{{
                    $t('status_pages.theme.logo_url.title')
                }}</Label>
                <Input
                    id="theme-logo"
                    :model-value="theme.logo_url ?? ''"
                    :placeholder="$t('status_pages.theme.logo_url.placeholder')"
                    spellcheck="false"
                    @update:model-value="set('logo_url', text($event))"
                />
                <p class="text-xs text-muted-foreground">
                    {{ $t('status_pages.theme.logo_url.description') }}
                </p>
                <InputError :message="error('logo_url')" />
            </div>

            <div class="grid gap-1.5">
                <Label for="theme-favicon">{{
                    $t('status_pages.theme.favicon_url.title')
                }}</Label>
                <Input
                    id="theme-favicon"
                    :model-value="theme.favicon_url ?? ''"
                    :placeholder="
                        $t('status_pages.theme.favicon_url.placeholder')
                    "
                    spellcheck="false"
                    @update:model-value="set('favicon_url', text($event))"
                />
                <InputError :message="error('favicon_url')" />
            </div>

            <div class="grid gap-1.5">
                <Label for="theme-mode">{{
                    $t('status_pages.theme.mode.title')
                }}</Label>
                <Select
                    :model-value="theme.mode"
                    @update:model-value="set('mode', $event as StatusPageMode)"
                >
                    <SelectTrigger id="theme-mode"
                        ><SelectValue
                    /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="light">{{
                            $t('status_pages.theme.mode.light')
                        }}</SelectItem>
                        <SelectItem value="dark">{{
                            $t('status_pages.theme.mode.dark')
                        }}</SelectItem>
                        <SelectItem value="system">{{
                            $t('status_pages.theme.mode.system')
                        }}</SelectItem>
                    </SelectContent>
                </Select>
                <p class="text-xs text-muted-foreground">
                    {{ $t('status_pages.theme.mode.description') }}
                </p>
                <InputError :message="error('mode')" />
            </div>

            <ColorField
                :model-value="theme.brand_color"
                :label="$t('status_pages.theme.brand_color.title')"
                :description="$t('status_pages.theme.brand_color.description')"
                :fallback="THEME_DEFAULTS.brand_color"
                :error="error('brand_color')"
                @update:model-value="
                    set('brand_color', $event ?? THEME_DEFAULTS.brand_color)
                "
            />

            <div class="grid gap-4 sm:grid-cols-2">
                <ColorField
                    :model-value="theme.background"
                    :label="$t('status_pages.theme.background.title')"
                    :description="
                        $t('status_pages.theme.background.description')
                    "
                    :fallback="schemeIsDark ? '#0b0b0f' : '#ffffff'"
                    :error="error('background')"
                    clearable
                    @update:model-value="set('background', $event)"
                />
                <ColorField
                    :model-value="theme.foreground"
                    :label="$t('status_pages.theme.foreground.title')"
                    :description="
                        $t('status_pages.theme.foreground.description')
                    "
                    :fallback="schemeIsDark ? '#f4f4f6' : '#111114'"
                    :error="error('foreground')"
                    clearable
                    @update:model-value="set('foreground', $event)"
                />
            </div>

            <div class="grid gap-2 rounded-sm border p-3">
                <p class="text-sm font-medium">
                    {{ $t('status_pages.theme.status_colors.title') }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ $t('status_pages.theme.status_colors.description') }}
                </p>
                <div class="mt-1 grid gap-3">
                    <ColorField
                        v-for="field in statusFields"
                        :key="field.key"
                        :model-value="theme[field.key]"
                        :label="field.label"
                        :fallback="THEME_DEFAULTS[field.key]"
                        :error="error(field.key)"
                        @update:model-value="
                            set(field.key, $event ?? THEME_DEFAULTS[field.key])
                        "
                    />
                </div>
            </div>
        </template>

        <template v-else>
            <div class="grid gap-1.5">
                <Label for="theme-font">{{
                    $t('status_pages.theme.font_family.title')
                }}</Label>
                <Input
                    id="theme-font"
                    class="font-mono"
                    :model-value="theme.font_family"
                    :placeholder="
                        $t('status_pages.theme.font_family.placeholder')
                    "
                    spellcheck="false"
                    @update:model-value="
                        set(
                            'font_family',
                            String($event).trim() === ''
                                ? THEME_DEFAULTS.font_family
                                : String($event),
                        )
                    "
                />
                <p class="text-xs text-muted-foreground">
                    {{ $t('status_pages.theme.font_family.description') }}
                </p>
                <InputError :message="error('font_family')" />
            </div>

            <div class="grid gap-1.5">
                <Label for="theme-font-url">{{
                    $t('status_pages.theme.font_url.title')
                }}</Label>
                <Input
                    id="theme-font-url"
                    class="font-mono"
                    :model-value="theme.font_url ?? ''"
                    :placeholder="$t('status_pages.theme.font_url.placeholder')"
                    spellcheck="false"
                    @update:model-value="set('font_url', text($event))"
                />
                <p class="text-xs text-muted-foreground">
                    {{ $t('status_pages.theme.font_url.description') }}
                </p>
                <InputError :message="error('font_url')" />
            </div>

            <div class="grid gap-1.5">
                <Label for="theme-radius">{{
                    $t('status_pages.theme.radius.title')
                }}</Label>
                <div class="flex items-center gap-3">
                    <input
                        type="range"
                        class="h-9 flex-1 accent-primary"
                        :min="THEME_LIMITS.radius.min"
                        :max="THEME_LIMITS.radius.max"
                        :value="theme.radius"
                        :aria-label="$t('status_pages.theme.radius.title')"
                        @input="
                            set(
                                'radius',
                                number(
                                    ($event.target as HTMLInputElement).value,
                                    THEME_LIMITS.radius,
                                    THEME_DEFAULTS.radius,
                                ),
                            )
                        "
                    />
                    <Input
                        id="theme-radius"
                        type="number"
                        class="w-20"
                        :min="THEME_LIMITS.radius.min"
                        :max="THEME_LIMITS.radius.max"
                        :model-value="theme.radius"
                        @update:model-value="
                            set(
                                'radius',
                                number(
                                    $event,
                                    THEME_LIMITS.radius,
                                    THEME_DEFAULTS.radius,
                                ),
                            )
                        "
                    />
                    <span class="text-sm text-muted-foreground">px</span>
                </div>
                <p class="text-xs text-muted-foreground">
                    {{ $t('status_pages.theme.radius.description') }}
                </p>
                <InputError :message="error('radius')" />
            </div>

            <div class="grid gap-1.5">
                <Label for="theme-width">{{
                    $t('status_pages.theme.width.title')
                }}</Label>
                <div class="flex items-center gap-3">
                    <input
                        type="range"
                        class="h-9 flex-1 accent-primary"
                        :min="THEME_LIMITS.width.min"
                        :max="THEME_LIMITS.width.max"
                        step="16"
                        :value="theme.width"
                        :aria-label="$t('status_pages.theme.width.title')"
                        @input="
                            set(
                                'width',
                                number(
                                    ($event.target as HTMLInputElement).value,
                                    THEME_LIMITS.width,
                                    THEME_DEFAULTS.width,
                                ),
                            )
                        "
                    />
                    <Input
                        id="theme-width"
                        type="number"
                        class="w-24"
                        :min="THEME_LIMITS.width.min"
                        :max="THEME_LIMITS.width.max"
                        :model-value="theme.width"
                        @update:model-value="
                            set(
                                'width',
                                number(
                                    $event,
                                    THEME_LIMITS.width,
                                    THEME_DEFAULTS.width,
                                ),
                            )
                        "
                    />
                    <span class="text-sm text-muted-foreground">px</span>
                </div>
                <p class="text-xs text-muted-foreground">
                    {{ $t('status_pages.theme.width.description') }}
                </p>
                <InputError :message="error('width')" />
            </div>

            <div class="grid gap-1.5">
                <Label for="theme-footer">{{
                    $t('status_pages.theme.footer_text.title')
                }}</Label>
                <Input
                    id="theme-footer"
                    :model-value="theme.footer_text ?? ''"
                    :placeholder="
                        $t('status_pages.theme.footer_text.placeholder')
                    "
                    @update:model-value="set('footer_text', text($event))"
                />
                <p class="text-xs text-muted-foreground">
                    {{ $t('status_pages.theme.footer_text.description') }}
                </p>
                <InputError :message="error('footer_text')" />
            </div>

            <div class="grid gap-2 rounded-sm border p-3">
                <p class="text-sm font-medium">
                    {{ $t('status_pages.theme.links.title') }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{
                        $t('status_pages.theme.links.description', {
                            count: THEME_LIMITS.links,
                        })
                    }}
                </p>

                <div
                    v-for="(link, index) in theme.links"
                    :key="index"
                    class="mt-1 flex items-start gap-2"
                >
                    <div
                        class="grid flex-1 gap-2 sm:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]"
                    >
                        <div class="grid gap-1">
                            <Input
                                :model-value="link.label"
                                :placeholder="
                                    $t('status_pages.theme.links.label')
                                "
                                :aria-label="
                                    $t('status_pages.theme.links.label')
                                "
                                @update:model-value="
                                    updateLink(index, 'label', String($event))
                                "
                            />
                            <InputError
                                :message="error(`links.${index}.label`)"
                            />
                        </div>
                        <div class="grid gap-1">
                            <Input
                                class="font-mono"
                                :model-value="link.url"
                                :placeholder="
                                    $t('status_pages.theme.links.url')
                                "
                                :aria-label="$t('status_pages.theme.links.url')"
                                spellcheck="false"
                                @update:model-value="
                                    updateLink(index, 'url', String($event))
                                "
                            />
                            <InputError
                                :message="error(`links.${index}.url`)"
                            />
                        </div>
                    </div>
                    <Button
                        variant="ghost"
                        size="sm"
                        :title="$t('status_pages.theme.links.remove')"
                        @click="removeLink(index)"
                    >
                        <XIcon />
                    </Button>
                </div>

                <div>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="theme.links.length >= THEME_LIMITS.links"
                        @click="addLink"
                    >
                        <PlusIcon />
                        {{ $t('status_pages.theme.links.add') }}
                    </Button>
                </div>
                <InputError :message="error('links')" />
            </div>
        </template>

        <ThemePreview :theme="theme" :title="title" />
    </div>
</template>

<script setup lang="ts">
import { PlusIcon, RotateCcwIcon, XIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import ColorField from '@/components/status-pages/ColorField.vue';
import ThemePreview from '@/components/status-pages/ThemePreview.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { trans } from '@/lib/i18n';
import { THEME_DEFAULTS, THEME_LIMITS } from '@/lib/statusPageTheme';
import type {
    StatusPageLink,
    StatusPageMode,
    StatusPageTheme,
} from '@/types/monitors';

const props = defineProps<{
    theme: StatusPageTheme;
    /** Which half of the theme this instance edits — the dialog has a tab each. */
    section: 'branding' | 'layout';
    /** The whole form's errors, keyed the way Laravel sends them. */
    errors: Partial<Record<string, string>>;
    title?: string;
}>();

const emit = defineEmits<{
    'update:theme': [StatusPageTheme];
    reset: [];
}>();

const statusFields = computed(
    () =>
        [
            {
                key: 'up_color',
                label: trans('status_pages.theme.status_colors.up'),
            },
            {
                key: 'down_color',
                label: trans('status_pages.theme.status_colors.down'),
            },
            {
                key: 'warning_color',
                label: trans('status_pages.theme.status_colors.warning'),
            },
        ] as const,
);

/**
 * Which scheme the empty background/text fields would fall back to, so their
 * swatches show what leaving them blank actually produces.
 */
const schemeIsDark = computed(() => props.theme.mode === 'dark');

function set<K extends keyof StatusPageTheme>(
    key: K,
    value: StatusPageTheme[K],
) {
    emit('update:theme', { ...props.theme, [key]: value });
}

function error(key: string): string | undefined {
    return props.errors[`theme.${key}`];
}

/** Empty inputs mean "unset", not an empty string. */
function text(value: string | number): string | null {
    const trimmed = String(value).trim();

    return trimmed === '' ? null : trimmed;
}

function number(
    value: string | number,
    bounds: { min: number; max: number },
    fallback: number,
): number {
    const parsed = Number.parseInt(String(value), 10);

    if (Number.isNaN(parsed)) {
        return fallback;
    }

    return Math.min(bounds.max, Math.max(bounds.min, parsed));
}

function updateLink(index: number, key: keyof StatusPageLink, value: string) {
    set(
        'links',
        props.theme.links.map((link, position) =>
            position === index ? { ...link, [key]: value } : link,
        ),
    );
}

function addLink() {
    if (props.theme.links.length >= THEME_LIMITS.links) {
        return;
    }

    set('links', [...props.theme.links, { label: '', url: '' }]);
}

function removeLink(index: number) {
    set(
        'links',
        props.theme.links.filter((_, position) => position !== index),
    );
}
</script>
