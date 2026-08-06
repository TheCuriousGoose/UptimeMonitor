<script setup lang="ts">
import type { ComboboxGroupProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { ComboboxGroup, ComboboxLabel, useForwardProps } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<
    ComboboxGroupProps & { class?: HTMLAttributes['class'], heading?: string }
>();

const forwarded = useForwardProps(reactiveOmit(props, 'class', 'heading'));
</script>

<template>
    <ComboboxGroup
        data-slot="command-group"
        v-bind="forwarded"
        :class="cn('overflow-hidden p-1 text-foreground', props.class)"
    >
        <ComboboxLabel
            v-if="heading"
            class="px-2 py-1.5 font-mono text-[0.6875rem] font-semibold tracking-[0.12em] text-muted-foreground uppercase"
        >
            {{ heading }}
        </ComboboxLabel>
        <slot />
    </ComboboxGroup>
</template>
