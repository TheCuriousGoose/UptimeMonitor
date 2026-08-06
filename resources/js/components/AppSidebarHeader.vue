<script setup lang="ts">
import { SearchIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useCommandPalette } from '@/composables/useCommandPalette';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const { show } = useCommandPalette();

const shortcut = computed(() =>
    typeof navigator !== 'undefined' &&
    /Mac|iPhone|iPad/.test(navigator.platform)
        ? '⌘K'
        : 'Ctrl K',
);
</script>

<template>
    <header
        class="sticky top-0 z-20 flex h-12 shrink-0 items-center gap-2 border-b bg-background/85 px-4 backdrop-blur-sm transition-[width,height] ease-linear"
    >
        <div class="flex min-w-0 flex-1 items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <!-- The shortcut is otherwise undiscoverable; showing the chord is
             most of the point of having a button at all. -->
        <Button
            variant="outline"
            size="sm"
            class="gap-2 text-muted-foreground"
            @click="show"
        >
            <SearchIcon />
            <span class="hidden sm:inline">{{ $t('command.trigger') }}</span>
            <kbd
                class="hidden rounded-sm border px-1 font-mono text-[0.6875rem] sm:inline"
            >
                {{ shortcut }}
            </kbd>
        </Button>
    </header>
</template>
