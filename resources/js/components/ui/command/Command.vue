<script setup lang="ts">
import type { ComboboxRootEmits, ComboboxRootProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { ComboboxRoot, useForwardPropsEmits } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<ComboboxRootProps & { class?: HTMLAttributes['class'] }>(),
    {
        open: true,
        // Filtering is the consumer's job here: the palette mixes static
        // navigation entries with monitors the server already matched, and
        // re-filtering the server's results would drop fuzzy matches.
        ignoreFilter: true,
    },
);

const emits = defineEmits<ComboboxRootEmits>();

const delegated = reactiveOmit(props, 'class');
const forwarded = useForwardPropsEmits(delegated, emits);
</script>

<template>
    <ComboboxRoot
        data-slot="command"
        v-bind="forwarded"
        :class="
            cn(
                'flex h-full w-full flex-col overflow-hidden rounded-md bg-popover text-popover-foreground',
                props.class,
            )
        "
    >
        <slot />
    </ComboboxRoot>
</template>
