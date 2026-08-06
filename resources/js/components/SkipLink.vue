<template>
    <!-- Visually hidden until focused: a keyboard user tabbing into the page
         should not have to walk the whole sidebar to reach the content, but a
         permanently visible link would be noise for everyone else. -->
    <a
        :href="`#${target}`"
        class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-sm focus:bg-background focus:px-3 focus:py-2 focus:text-sm focus:font-medium focus:ring-2 focus:ring-ring focus:outline-none"
        @click="focusTarget"
    >
        {{ $t('base.skip_to_content') }}
    </a>
</template>

<script setup lang="ts">
withDefaults(defineProps<{ target?: string }>(), {
    target: 'main-content',
});

/**
 * A bare fragment link moves the viewport but not focus in several browsers,
 * which leaves the next Tab back at the top of the page — exactly what the
 * link exists to avoid.
 */
function focusTarget(event: MouseEvent) {
    const id = (event.currentTarget as HTMLAnchorElement).hash.slice(1);

    document.getElementById(id)?.focus();
}
</script>
