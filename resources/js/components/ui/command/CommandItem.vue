<script setup lang="ts">
import type { ComboboxItemEmits, ComboboxItemProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { ComboboxItem, useForwardPropsEmits } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<
    ComboboxItemProps & { class?: HTMLAttributes['class'] }
>();

const emits = defineEmits<ComboboxItemEmits>();

const forwarded = useForwardPropsEmits(reactiveOmit(props, 'class'), emits);
</script>

<template>
    <ComboboxItem
        data-slot="command-item"
        v-bind="forwarded"
        :class="
            cn(
                'relative flex cursor-default items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none select-none data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground [&_svg]:size-4 [&_svg]:shrink-0 [&_svg]:text-muted-foreground',
                props.class,
            )
        "
    >
        <slot />
    </ComboboxItem>
</template>
