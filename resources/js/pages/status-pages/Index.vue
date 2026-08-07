<template>
    <Head :title="$t('status_pages.heading')" />

    <div class="flex flex-col gap-4 p-4">
        <PageHeader
            :title="$t('status_pages.heading')"
            :description="$t('status_pages.subtitle')"
        >
            <template #actions>
                <Button v-can="'status_pages.create'" @click="openCreate">
                    <PlusIcon />
                    {{ $t('status_pages.form.create') }}
                </Button>
            </template>
        </PageHeader>

        <EmptyState
            v-if="pages.length === 0"
            :icon="GlobeIcon"
            :title="$t('status_pages.empty.title')"
            :description="$t('status_pages.empty.description')"
        />

        <ul v-else class="divide-y rounded-sm border">
            <li
                v-for="page in pages"
                :key="page.uuid"
                class="flex items-start justify-between gap-3 px-4 py-3 transition-colors hover:bg-muted/40"
            >
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <p class="truncate font-medium">{{ page.title }}</p>
                        <Badge v-if="!page.is_published" variant="secondary"
                            >Draft</Badge
                        >
                    </div>
                    <a
                        :href="page.public_url"
                        target="_blank"
                        rel="noopener"
                        class="mt-1 block truncate font-mono text-sm text-muted-foreground hover:underline"
                    >
                        /status/{{ page.slug }}
                    </a>
                    <p
                        v-if="page.monitors_count !== undefined"
                        class="mt-1 text-xs text-muted-foreground"
                    >
                        {{
                            $t(
                                'status_pages.monitors_count',
                                { count: page.monitors_count },
                                page.monitors_count,
                            )
                        }}
                    </p>
                </div>

                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button variant="ghost" size="sm">
                            <MoreHorizontalIcon />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem
                            v-can="'status_pages.edit'"
                            @select="openEdit(page)"
                        >
                            <PencilIcon />
                            {{ $t('status_pages.actions.edit') }}
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            as="a"
                            :href="page.public_url"
                            target="_blank"
                            rel="noopener"
                        >
                            <ExternalLinkIcon />
                            {{ $t('status_pages.actions.visit') }}
                        </DropdownMenuItem>
                        <DropdownMenuSeparator v-can="'status_pages.delete'" />
                        <DropdownMenuItem
                            v-can="'status_pages.delete'"
                            variant="destructive"
                            @select="askDelete(page)"
                        >
                            <Trash2Icon />
                            {{ $t('status_pages.actions.delete') }}
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </li>
        </ul>
    </div>

    <Dialog v-model:open="formOpen">
        <DialogScrollContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>
                    {{
                        editing
                            ? $t('status_pages.form.edit')
                            : $t('status_pages.form.create')
                    }}
                </DialogTitle>
            </DialogHeader>

            <Tabs v-model="tab" class="py-1">
                <TabsList class="w-full">
                    <TabsTrigger value="general">{{
                        $t('status_pages.form.tabs.general')
                    }}</TabsTrigger>
                    <TabsTrigger value="branding">{{
                        $t('status_pages.form.tabs.branding')
                    }}</TabsTrigger>
                    <TabsTrigger value="layout">{{
                        $t('status_pages.form.tabs.layout')
                    }}</TabsTrigger>
                </TabsList>

                <TabsContent value="general" class="space-y-4">
                    <div class="grid gap-1.5">
                        <Label for="page-title">{{
                            $t('status_pages.form.title_field.title')
                        }}</Label>
                        <Input
                            id="page-title"
                            v-model="form.title"
                            :placeholder="
                                $t('status_pages.form.title_field.placeholder')
                            "
                        />
                        <InputError :message="form.errors.title" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="page-slug">{{
                            $t('status_pages.form.slug.title')
                        }}</Label>
                        <Input
                            id="page-slug"
                            v-model="form.slug"
                            :placeholder="
                                $t('status_pages.form.slug.placeholder')
                            "
                        />
                        <p class="text-xs text-muted-foreground">
                            {{ $t('status_pages.form.slug.description') }}
                        </p>
                        <InputError :message="form.errors.slug" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="page-description">{{
                            $t('status_pages.form.description.title')
                        }}</Label>
                        <Input
                            id="page-description"
                            v-model="form.description"
                            :placeholder="
                                $t('status_pages.form.description.placeholder')
                            "
                        />
                        <InputError :message="form.errors.description" />
                    </div>

                    <div class="grid gap-2">
                        <Label>{{
                            $t('status_pages.form.monitors.title')
                        }}</Label>
                        <p class="text-xs text-muted-foreground">
                            {{ $t('status_pages.form.monitors.description') }}
                        </p>
                        <p
                            v-if="monitors.length === 0"
                            class="text-sm text-muted-foreground"
                        >
                            {{ $t('status_pages.form.monitors.empty') }}
                        </p>
                        <div
                            v-else
                            class="max-h-60 space-y-2 overflow-y-auto pr-1"
                        >
                            <label
                                v-for="monitor in monitors"
                                :key="monitor.uuid"
                                class="flex cursor-pointer items-center gap-3 rounded-sm border p-2.5 transition-colors hover:bg-muted/40"
                            >
                                <Checkbox
                                    :model-value="
                                        form.monitors.includes(monitor.uuid)
                                    "
                                    @update:model-value="
                                        toggleMonitor(monitor.uuid)
                                    "
                                />
                                <span class="min-w-0">
                                    <span
                                        class="block truncate text-sm font-medium"
                                        >{{ monitor.name }}</span
                                    >
                                    <span
                                        class="block truncate text-xs text-muted-foreground"
                                        >{{ monitor.url }}</span
                                    >
                                </span>
                            </label>
                        </div>
                        <InputError :message="form.errors.monitors" />
                    </div>

                    <div
                        class="flex items-center justify-between gap-3 rounded-sm border p-3"
                    >
                        <div>
                            <p class="text-sm font-medium">
                                {{ $t('status_pages.form.is_published.title') }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{
                                    $t(
                                        'status_pages.form.is_published.description',
                                    )
                                }}
                            </p>
                        </div>
                        <Switch v-model:checked="form.is_published" />
                    </div>
                </TabsContent>

                <TabsContent value="branding">
                    <ThemeEditor
                        v-model:theme="form.theme"
                        section="branding"
                        :errors="form.errors"
                        :title="form.title"
                        @reset="resetTheme"
                    />
                </TabsContent>

                <TabsContent value="layout">
                    <ThemeEditor
                        v-model:theme="form.theme"
                        section="layout"
                        :errors="form.errors"
                        :title="form.title"
                        @reset="resetTheme"
                    />
                </TabsContent>
            </Tabs>

            <DialogFooter>
                <Button variant="outline" @click="formOpen = false">{{
                    $t('base.cancel')
                }}</Button>
                <Button :disabled="form.processing" @click="submit">
                    <Spinner v-if="form.processing" />
                    {{ $t('status_pages.form.submit') }}
                </Button>
            </DialogFooter>
        </DialogScrollContent>
    </Dialog>

    <ConfirmDialog
        v-model:open="confirmingDelete"
        :title="$t('status_pages.actions.delete')"
        :description="$t('status_pages.actions.confirm_delete')"
        :confirm-label="$t('base.delete')"
        destructive
        @confirm="destroy"
    />
</template>

<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    ExternalLinkIcon,
    GlobeIcon,
    MoreHorizontalIcon,
    PencilIcon,
    PlusIcon,
    Trash2Icon,
} from 'lucide-vue-next';
import { ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import ThemeEditor from '@/components/status-pages/ThemeEditor.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogFooter,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { trans } from '@/lib/i18n';
import { THEME_DEFAULTS } from '@/lib/statusPageTheme';
import * as statusPagesRoute from '@/routes/status-pages';
import type { Monitor, StatusPage, StatusPageTheme } from '@/types/monitors';

defineProps<{
    pages: StatusPage[];
    monitors: Monitor[];
}>();

const formOpen = ref(false);
const editing = ref<StatusPage | null>(null);
const confirmingDelete = ref(false);
const pendingDelete = ref<StatusPage | null>(null);
const tab = ref('general');

const form = useForm<{
    title: string;
    slug: string;
    description: string;
    is_published: boolean;
    monitors: string[];
    theme: StatusPageTheme;
}>({
    title: '',
    slug: '',
    description: '',
    is_published: true,
    monitors: [],
    theme: { ...THEME_DEFAULTS },
});

function openCreate() {
    editing.value = null;
    form.clearErrors();
    form.title = '';
    form.slug = '';
    form.description = '';
    form.is_published = true;
    form.monitors = [];
    resetTheme();
    tab.value = 'general';
    formOpen.value = true;
}

function openEdit(page: StatusPage) {
    editing.value = page;
    form.clearErrors();
    form.title = page.title;
    form.slug = page.slug;
    form.description = page.description ?? '';
    form.is_published = page.is_published;
    form.monitors = (page.monitors ?? []).map((monitor) => monitor.uuid);
    // The resource always sends a complete theme, but a page saved before
    // theming existed could still be missing keys if the payload is cached.
    form.theme = {
        ...THEME_DEFAULTS,
        ...page.theme,
        links: (page.theme?.links ?? []).map((link) => ({ ...link })),
    };
    tab.value = 'general';
    formOpen.value = true;
}

function resetTheme() {
    form.theme = { ...THEME_DEFAULTS, links: [] };
}

const LAYOUT_KEYS = [
    'font_family',
    'font_url',
    'radius',
    'width',
    'footer_text',
    'links',
];

function tabFor(key: string): string {
    if (!key.startsWith('theme.')) {
        return 'general';
    }

    const field = key.slice('theme.'.length);

    return LAYOUT_KEYS.some((name) => field.startsWith(name))
        ? 'layout'
        : 'branding';
}

function toggleMonitor(uuid: string) {
    form.monitors = form.monitors.includes(uuid)
        ? form.monitors.filter((value) => value !== uuid)
        : [...form.monitors, uuid];
}

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            formOpen.value = false;
        },
        // Theme fields live behind their own tabs, so an error there would
        // otherwise be reported on a panel nobody is looking at.
        onError: (errors: Record<string, string>) => {
            tab.value = tabFor(Object.keys(errors)[0] ?? '');
        },
    };

    if (editing.value) {
        form.put(statusPagesRoute.update(editing.value.uuid).url, options);

        return;
    }

    form.post(statusPagesRoute.store().url, options);
}

function askDelete(page: StatusPage) {
    pendingDelete.value = page;
    confirmingDelete.value = true;
}

function destroy() {
    if (!pendingDelete.value) {
        return;
    }

    router.delete(statusPagesRoute.destroy(pendingDelete.value.uuid).url, {
        preserveScroll: true,
    });
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: trans('status_pages.heading'),
                href: statusPagesRoute.index(),
            },
        ],
    },
});
</script>
