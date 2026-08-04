<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <!--
        A shield for the "watch" half of the name, carrying an uptime pulse.
        Stroked rather than filled so it reads on any background and stays
        legible down to 16px.
    -->
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.9"
        stroke-linecap="round"
        stroke-linejoin="round"
        :class="className"
        v-bind="$attrs"
    >
        <path
            d="M12 2.4 20 5.5v6.1c0 4.8-3.4 8.8-8 10.1-4.6-1.3-8-5.3-8-10.1V5.5Z"
        />
        <path d="M7.4 11.7h2.3l1.5-3.2 2.1 6.6 1.4-3.4h2.1" />
    </svg>
</template>
