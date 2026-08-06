<template>
    <button
        type="button"
        class="-mx-2 inline-flex items-center gap-1 rounded-sm px-2 py-1 transition-colors hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-1 focus-visible:ring-offset-background focus-visible:outline-none"
        @click="$emit('sort')"
    >
        {{ label }}
        <component
            :is="icon"
            class="size-3.5 shrink-0"
            :class="active ? 'text-foreground' : 'text-muted-foreground/50'"
            aria-hidden="true"
        />
    </button>
</template>

<script setup lang="ts">
import { ArrowDownIcon, ArrowUpDownIcon, ArrowUpIcon } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    label: string;
    /** Whether this column is the one currently sorted. */
    active: boolean;
    direction: 'asc' | 'desc';
}>();

defineEmits<{ sort: [] }>();

// The sort state itself is announced by aria-sort on the header cell, which
// is where ARIA expects it. This icon is the sighted half of the same signal.
const icon = computed(() => {
    if (!props.active) {
        return ArrowUpDownIcon;
    }

    return props.direction === 'asc' ? ArrowUpIcon : ArrowDownIcon;
});
</script>
