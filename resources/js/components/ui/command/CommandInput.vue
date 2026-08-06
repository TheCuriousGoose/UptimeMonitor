<script setup lang="ts">
import type { ComboboxInputProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { SearchIcon } from 'lucide-vue-next';
import { ComboboxInput, useForwardProps } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<
    ComboboxInputProps & { class?: HTMLAttributes['class'] }
>();

const forwarded = useForwardProps(reactiveOmit(props, 'class'));
</script>

<template>
    <div class="flex items-center gap-2 border-b px-3">
        <SearchIcon class="size-4 shrink-0 opacity-50" aria-hidden="true" />
        <ComboboxInput
            data-slot="command-input"
            v-bind="forwarded"
            :class="
                cn(
                    'flex h-11 w-full bg-transparent py-3 text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50',
                    props.class,
                )
            "
        />
    </div>
</template>
