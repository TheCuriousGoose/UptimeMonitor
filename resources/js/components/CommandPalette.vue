<template>
    <Dialog v-model:open="open">
        <DialogContent
            class="overflow-hidden p-0 sm:max-w-xl"
            :show-close-button="false"
        >
            <DialogHeader class="sr-only">
                <DialogTitle>{{ $t('command.title') }}</DialogTitle>
                <DialogDescription>
                    {{ $t('command.description') }}
                </DialogDescription>
            </DialogHeader>

            <Command @update:model-value="run">
                <CommandInput
                    v-model="query"
                    :placeholder="$t('command.placeholder')"
                    :auto-focus="true"
                />
                <CommandList>
                    <CommandEmpty>{{ $t('command.empty') }}</CommandEmpty>

                    <CommandGroup
                        v-if="matchedPages.length"
                        :heading="$t('command.groups.navigate')"
                    >
                        <CommandItem
                            v-for="page in matchedPages"
                            :key="page.href"
                            :value="page"
                        >
                            <component :is="page.icon" aria-hidden="true" />
                            {{ page.label }}
                        </CommandItem>
                    </CommandGroup>

                    <CommandGroup
                        v-if="monitors.length"
                        :heading="$t('command.groups.monitors')"
                    >
                        <CommandItem
                            v-for="monitor in monitors"
                            :key="monitor.uuid"
                            :value="monitor"
                        >
                            <ActivityIcon aria-hidden="true" />
                            <span class="flex-1 truncate">{{
                                monitor.name
                            }}</span>
                            <MonitorStatusBadge :status="monitor.status" />
                        </CommandItem>
                    </CommandGroup>
                </CommandList>
            </Command>
        </DialogContent>
    </Dialog>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useMagicKeys } from '@vueuse/core';
import {
    ActivityIcon,
    LayoutDashboardIcon,
    PlugIcon,
    SettingsIcon,
    SirenIcon,
    GlobeIcon,
} from 'lucide-vue-next';
import type { Component } from 'vue';
import { computed, ref, watch } from 'vue';
import MonitorStatusBadge from '@/components/monitors/MonitorStatusBadge.vue';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useCommandPalette } from '@/composables/useCommandPalette';
import { trans } from '@/lib/i18n';
import { dashboard } from '@/routes';
import * as incidentsRoute from '@/routes/incidents';
import * as integrationsRoute from '@/routes/integrations';
import * as monitorsRoute from '@/routes/monitors';
import * as statusPagesRoute from '@/routes/status-pages';
import type { MonitorStatus } from '@/types/monitors';
import debounce from '@/util/debounce';

type Page = { label: string; href: string; icon: Component };
type Hit = { uuid: string; name: string; status: MonitorStatus };

const { open, toggle } = useCommandPalette();
const query = ref('');
const monitors = ref<Hit[]>([]);

const { meta_k, ctrl_k } = useMagicKeys({
    // Otherwise the browser's own find-in-page or address bar wins.
    passive: false,
    onEventFired(event) {
        if (event.key === 'k' && (event.metaKey || event.ctrlKey)) {
            event.preventDefault();
        }
    },
});

watch([meta_k, ctrl_k], ([meta, ctrl]) => {
    if (meta || ctrl) {
        toggle();
    }
});

const pages = computed<Page[]>(() => [
    {
        label: trans('command.pages.dashboard'),
        href: dashboard().url,
        icon: LayoutDashboardIcon,
    },
    {
        label: trans('command.pages.monitors'),
        href: monitorsRoute.index().url,
        icon: ActivityIcon,
    },
    {
        label: trans('command.pages.incidents'),
        href: incidentsRoute.index().url,
        icon: SirenIcon,
    },
    {
        label: trans('command.pages.integrations'),
        href: integrationsRoute.index().url,
        icon: PlugIcon,
    },
    {
        label: trans('command.pages.status_pages'),
        href: statusPagesRoute.index().url,
        icon: GlobeIcon,
    },
    {
        label: trans('command.pages.settings'),
        href: '/settings/profile',
        icon: SettingsIcon,
    },
]);

// Navigation entries are a short fixed list, so matching them here avoids a
// round trip. Monitors are matched by the server, which already knows how.
const matchedPages = computed(() => {
    const term = query.value.trim().toLowerCase();

    if (term === '') {
        return pages.value;
    }

    return pages.value.filter((page) =>
        page.label.toLowerCase().includes(term),
    );
});

async function fetchMonitors(term: string) {
    try {
        const response = await fetch(
            `${monitorsRoute.search().url}?q=${encodeURIComponent(term)}`,
            { headers: { Accept: 'application/json' } },
        );

        if (!response.ok) {
            return;
        }

        monitors.value = (await response.json()) as Hit[];
    } catch {
        // A failed lookup leaves the navigation entries usable; the palette
        // is a shortcut, never the only way to reach anything.
    }
}

const fetchMonitorsDebounced = debounce(fetchMonitors, 200);

watch(query, (term) => fetchMonitorsDebounced(term.trim()));

// Load the first page of monitors as soon as it opens, so the palette is
// useful before anything is typed.
watch(open, (isOpen) => {
    if (isOpen) {
        query.value = '';
        fetchMonitors('');
    }
});

function run(value: unknown) {
    if (!value) {
        return;
    }

    const target = value as Partial<Page> & Partial<Hit>;

    open.value = false;

    router.visit(target.href ?? monitorsRoute.show(target.uuid as string).url);
}
</script>
