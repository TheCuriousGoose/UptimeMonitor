<script setup lang="ts">
import type { ComboboxContentProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { ComboboxContent, useForwardProps } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<
    ComboboxContentProps & { class?: HTMLAttributes['class'] }
>();

const forwarded = useForwardProps(reactiveOmit(props, 'class'));
</script>

<template>
    <ComboboxContent
        data-slot="command-list"
        v-bind="forwarded"
        :class="cn('max-h-80 overflow-y-auto overflow-x-hidden', props.class)"
    >
        <div role="presentation" class="p-1">
            <slot />
        </div>
    </ComboboxContent>
</template>
