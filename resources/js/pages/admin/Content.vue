<template>
    <Head :title="$t('content.title')" />

    <div class="flex flex-col gap-6 p-4">
        <PageHeader
            :title="$t('content.heading')"
            :description="$t('content.subtitle')"
        >
            <template #actions>
                <Button @click="openCreate">
                    <PlusIcon />
                    {{ $t('content.form.create') }}
                </Button>
            </template>
        </PageHeader>

        <TableFilterBar>
            <template #filters>
                <Input
                    v-model="search"
                    name="search"
                    type="search"
                    class="w-64"
                    :placeholder="$t('content.table.filters.search.placeholder')"
                />
                <Select v-model="type">
                    <SelectTrigger class="w-48">
                        <SelectValue
                            :placeholder="$t('content.table.filters.type.label')"
                        />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="ALL">{{
                            $t('content.table.filters.type.all')
                        }}</SelectItem>
                        <SelectItem
                            v-for="option in types"
                            :key="option"
                            :value="option"
                        >
                            {{ $t(`content.types.${option}`) }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </template>
        </TableFilterBar>

        <EmptyState
            v-if="entries.data.length === 0"
            :icon="FileTextIcon"
            :title="$t('content.empty.title')"
            :description="$t('content.empty.description')"
        />

        <div v-else class="flex flex-col gap-4">
            <ul class="divide-y rounded-sm border">
                <li
                    v-for="entry in entries.data"
                    :key="entry.uuid"
                    class="flex items-start justify-between gap-3 px-4 py-3 transition-colors hover:bg-muted/40"
                >
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="truncate font-medium">
                                {{ entry.title }}
                            </p>
                            <Badge :variant="statusVariant(entry)">
                                {{ $t(`content.status.${statusOf(entry)}`) }}
                            </Badge>
                        </div>
                        <p
                            class="mt-1 truncate font-mono text-xs text-muted-foreground"
                        >
                            {{ publicPath(entry) ?? `(${entry.type}/${entry.slug})` }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ $t(`content.types.${entry.type}`) }}
                            <template v-if="entry.published_at">
                                · {{ formatDateTime(entry.published_at) }}
                            </template>
                        </p>
                    </div>

                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="ghost" size="sm">
                                <MoreHorizontalIcon />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem @select="openEdit(entry)">
                                <PencilIcon />
                                {{ $t('content.actions.edit') }}
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                v-if="entry.is_published && publicPath(entry)"
                                as="a"
                                :href="publicPath(entry)!"
                                target="_blank"
                                rel="noopener"
                            >
                                <ExternalLinkIcon />
                                {{ $t('content.actions.view') }}
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                variant="destructive"
                                @select="askDelete(entry)"
                            >
                                <Trash2Icon />
                                {{ $t('content.actions.delete') }}
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </li>
            </ul>
        </div>
    </div>

    <Dialog v-model:open="formOpen">
        <DialogScrollContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>
                    {{
                        editing
                            ? $t('content.form.edit')
                            : $t('content.form.create')
                    }}
                </DialogTitle>
            </DialogHeader>

            <div class="space-y-4 py-1">
                <div class="grid gap-1.5">
                    <Label for="entry-type">{{
                        $t('content.form.type.title')
                    }}</Label>
                    <Select v-model="form.type">
                        <SelectTrigger id="entry-type"
                            ><SelectValue
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="option in types"
                                :key="option"
                                :value="option"
                            >
                                {{ $t(`content.types.${option}`) }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.type" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="entry-title">{{
                        $t('content.form.title.title')
                    }}</Label>
                    <Input
                        id="entry-title"
                        v-model="form.title"
                        :placeholder="$t('content.form.title.placeholder')"
                    />
                    <InputError :message="form.errors.title" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="entry-slug">{{
                        $t('content.form.slug.title')
                    }}</Label>
                    <Input
                        id="entry-slug"
                        v-model="form.slug"
                        class="font-mono"
                        :placeholder="$t('content.form.slug.placeholder')"
                    />
                    <p class="text-xs text-muted-foreground">
                        {{ $t('content.form.slug.description') }}
                    </p>
                    <InputError :message="form.errors.slug" />
                </div>

                <div v-if="form.type === 'changelog'" class="grid gap-1.5">
                    <Label for="entry-version">{{
                        $t('content.form.version.title')
                    }}</Label>
                    <Input
                        id="entry-version"
                        v-model="form.version"
                        class="font-mono"
                        :placeholder="$t('content.form.version.placeholder')"
                    />
                    <InputError :message="form.errors.version" />
                </div>

                <div v-if="form.type === 'doc'" class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-1.5">
                        <Label for="entry-category">{{
                            $t('content.form.category.title')
                        }}</Label>
                        <Input
                            id="entry-category"
                            v-model="form.category"
                            :placeholder="
                                $t('content.form.category.placeholder')
                            "
                        />
                        <InputError :message="form.errors.category" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="entry-order">{{
                            $t('content.form.sort_order.title')
                        }}</Label>
                        <Input
                            id="entry-order"
                            v-model="form.sort_order"
                            type="number"
                            min="0"
                        />
                        <InputError :message="form.errors.sort_order" />
                    </div>
                </div>

                <div class="grid gap-1.5">
                    <Label for="entry-excerpt">{{
                        $t('content.form.excerpt.title')
                    }}</Label>
                    <Input
                        id="entry-excerpt"
                        v-model="form.excerpt"
                        :placeholder="$t('content.form.excerpt.placeholder')"
                    />
                    <InputError :message="form.errors.excerpt" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="entry-body">{{
                        $t('content.form.body.title')
                    }}</Label>
                    <textarea
                        id="entry-body"
                        v-model="form.body"
                        rows="14"
                        class="w-full rounded-sm border border-input bg-transparent px-2.5 py-2 font-mono text-sm transition-colors outline-none focus-visible:border-ring focus-visible:ring-1 focus-visible:ring-ring dark:bg-input/20"
                    />
                    <p class="text-xs text-muted-foreground">
                        {{ $t('content.form.body.description') }}
                    </p>
                    <InputError :message="form.errors.body" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="entry-published">{{
                        $t('content.form.published_at.title')
                    }}</Label>
                    <Input
                        id="entry-published"
                        v-model="form.published_at"
                        type="datetime-local"
                    />
                    <p class="text-xs text-muted-foreground">
                        {{ $t('content.form.published_at.description') }}
                    </p>
                    <InputError :message="form.errors.published_at" />
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="formOpen = false">{{
                    $t('base.cancel')
                }}</Button>
                <Button :disabled="form.processing" @click="submit">
                    <Spinner v-if="form.processing" />
                    {{ $t('content.form.submit') }}
                </Button>
            </DialogFooter>
        </DialogScrollContent>
    </Dialog>

    <ConfirmDialog
        v-model:open="confirmingDelete"
        :title="$t('content.actions.delete')"
        :description="$t('content.actions.confirm_delete')"
        :confirm-label="$t('base.delete')"
        destructive
        @confirm="destroy"
    />
</template>

<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    ExternalLinkIcon,
    FileTextIcon,
    MoreHorizontalIcon,
    PencilIcon,
    PlusIcon,
    Trash2Icon,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import TableFilterBar from '@/components/tables/TableFilterBar.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { formatDateTime } from '@/lib/format';
import { trans } from '@/lib/i18n';
import content from '@/routes/admin/content';
import { show as contentShow } from '@/routes/content';
import type { ContentEntry } from '@/types/content';
import type { Pagination } from '@/types/pagination';
import debounce from '@/util/debounce';

const props = defineProps<{
    entries: Pagination<ContentEntry>;
    types: string[];
    filters: { type: string | null; search: string | null };
}>();

const ALL = 'all';

const segments: Record<string, string> = {
    doc: 'docs',
    post: 'blog',
    changelog: 'changelog',
};

/**
 * Legal pages are not served under /{segment}/{slug} like the rest — they have
 * their own top-level routes, and only for the two slugs those routes define.
 * Anything else has no public URL to link to.
 */
const legalPaths: Record<string, string> = {
    privacy: '/privacy',
    terms: '/terms',
};

/**
 * The public URL for an entry, or null when it has none. Returning null rather
 * than building a URL from an undefined segment is the point: passing one to
 * the route helper throws while the dropdown is rendering, taking the whole
 * menu down with it.
 */
function publicPath(entry: ContentEntry): string | null {
    if (entry.type === 'legal') {
        return legalPaths[entry.slug] ?? null;
    }

    const segment = segments[entry.type];

    if (!segment) {
        return null;
    }

    return contentShow({ segment, slug: entry.slug }).url;
}

const search = ref(props.filters.search ?? '');
const type = ref(props.filters.type ?? ALL);

function reload() {
    router.get(
        content.index(),
        {
            search: search.value.trim() || undefined,
            type: type.value === ALL ? undefined : type.value,
        },
        { preserveState: true, replace: true, only: ['entries', 'filters'] },
    );
}

watch(search, debounce(reload, 300));
watch(type, reload);

const formOpen = ref(false);
const editing = ref<ContentEntry | null>(null);

const form = useForm({
    type: 'doc',
    title: '',
    slug: '',
    excerpt: '',
    body: '',
    version: '',
    category: '',
    sort_order: 0,
    published_at: '',
});

/**
 * The date input wants "YYYY-MM-DDTHH:mm" in local time; the API hands back
 * an ISO-8601 string with an offset.
 */
function toDateInput(value: string | null): string {
    if (!value) {
        return '';
    }

    const date = new Date(value);
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function statusOf(entry: ContentEntry): string {
    if (entry.is_published) {
        return 'published';
    }

    return entry.published_at ? 'scheduled' : 'draft';
}

function statusVariant(entry: ContentEntry) {
    return (
        { published: 'success', scheduled: 'default', draft: 'outline' } as const
    )[statusOf(entry) as 'published' | 'scheduled' | 'draft'];
}

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    formOpen.value = true;
}

function openEdit(entry: ContentEntry) {
    editing.value = entry;
    form.clearErrors();
    form.type = entry.type;
    form.title = entry.title;
    form.slug = entry.slug;
    form.excerpt = entry.excerpt ?? '';
    form.body = entry.body ?? '';
    form.version = entry.version ?? '';
    form.category = entry.category ?? '';
    form.sort_order = entry.sort_order;
    form.published_at = toDateInput(entry.published_at);
    formOpen.value = true;
}

function payload() {
    return {
        type: form.type,
        title: form.title,
        slug: form.slug,
        excerpt: form.excerpt || null,
        body: form.body,
        version: form.version || null,
        category: form.category || null,
        sort_order: Number(form.sort_order) || 0,
        published_at: form.published_at || null,
    };
}

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            formOpen.value = false;
        },
    };

    if (editing.value) {
        form.transform(payload).put(
            content.update(editing.value.uuid).url,
            options,
        );

        return;
    }

    form.transform(payload).post(content.store().url, options);
}

const confirmingDelete = ref(false);
const pendingDelete = ref<ContentEntry | null>(null);

function askDelete(entry: ContentEntry) {
    pendingDelete.value = entry;
    confirmingDelete.value = true;
}

function destroy() {
    if (!pendingDelete.value) {
        return;
    }

    router.delete(content.destroy(pendingDelete.value.uuid).url, {
        preserveScroll: true,
    });
}

defineOptions({
    layout: {
        breadcrumbs: [{ title: trans('content.heading'), href: content.index() }],
    },
});
</script>
