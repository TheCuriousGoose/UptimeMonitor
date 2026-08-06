<script setup lang="ts">
import type { ComboboxEmptyProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { ComboboxEmpty, useForwardProps } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<
    ComboboxEmptyProps & { class?: HTMLAttributes['class'] }
>();

const forwarded = useForwardProps(reactiveOmit(props, 'class'));
</script>

<template>
    <ComboboxEmpty
        data-slot="command-empty"
        v-bind="forwarded"
        :class="cn('py-6 text-center text-sm text-muted-foreground', props.class)"
    >
        <slot />
    </ComboboxEmpty>
</template>
