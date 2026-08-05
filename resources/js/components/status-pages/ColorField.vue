<template>
    <div class="grid gap-1.5">
        <Label :for="id">{{ label }}</Label>

        <div class="flex items-center gap-2">
            <!--
                The swatch and the text field edit the same value from two
                directions: the picker for choosing, the text for pasting the
                hex straight out of a brand guide.
            -->
            <input
                :id="id"
                type="color"
                class="size-9 shrink-0 cursor-pointer rounded-sm border bg-transparent p-0.5"
                :value="swatch"
                @input="commit(($event.target as HTMLInputElement).value)"
            />
            <Input
                :model-value="modelValue ?? ''"
                class="font-mono"
                :placeholder="placeholder ?? fallback"
                spellcheck="false"
                @update:model-value="commit(String($event))"
            />
            <Button
                v-if="clearable && modelValue"
                variant="ghost"
                size="sm"
                :title="$t('base.clear')"
                @click="emit('update:modelValue', null)"
            >
                <XIcon />
            </Button>
        </div>

        <p v-if="description" class="text-xs text-muted-foreground">
            {{ description }}
        </p>
        <InputError :message="error" />
    </div>
</template>

<script setup lang="ts">
import { XIcon } from 'lucide-vue-next';
import { computed, useId } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { parseHex } from '@/lib/statusPageTheme';

const props = withDefaults(
    defineProps<{
        modelValue: string | null;
        label: string;
        /** Shown in the swatch when the value is empty or half-typed. */
        fallback: string;
        description?: string;
        placeholder?: string;
        clearable?: boolean;
        error?: string;
    }>(),
    { clearable: false },
);

const emit = defineEmits<{ 'update:modelValue': [string | null] }>();

const id = useId();

/**
 * `<input type="color">` only accepts a full `#rrggbb`. While someone is
 * mid-way through typing one, keep showing the fallback rather than letting
 * the swatch jump to black.
 */
const swatch = computed(() => {
    const parsed = parseHex(props.modelValue);

    if (!parsed) {
        return props.fallback;
    }

    return `#${[parsed.r, parsed.g, parsed.b]
        .map((channel) => channel.toString(16).padStart(2, '0'))
        .join('')}`;
});

function commit(value: string) {
    const trimmed = value.trim();

    emit('update:modelValue', trimmed === '' ? null : trimmed);
}
</script>
