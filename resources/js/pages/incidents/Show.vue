<script setup lang="ts">
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import { CheckIcon, GlobeIcon, LockIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import IncidentStatusBadge from '@/components/incidents/IncidentStatusBadge.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatTile from '@/components/StatTile.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatDateTime, formatDuration, formatRelative } from '@/lib/format';
import { trans } from '@/lib/i18n';
import * as incidentsRoute from '@/routes/incidents';
import * as acknowledgeRoute from '@/routes/incidents/acknowledge';
import * as incidentUpdatesRoute from '@/routes/incidents/updates';
import type { Incident } from '@/types/monitors';

type IncidentUpdate = {
    uuid: string;
    body: string;
    body_html: string;
    status: string | null;
    is_public: boolean;
    created_at: string | null;
    author?: { name: string };
};

type DetailedIncident = Incident & {
    is_acknowledged: boolean;
    acknowledged_at: string | null;
    acknowledged_by?: { name: string };
    updates?: IncidentUpdate[];
};

const props = defineProps<{ incident: DetailedIncident }>();

const errors = ref<Record<string, string>>({});
const body = ref('');
const status = ref<string>('none');
const isPublic = ref(false);

const statuses = ['investigating', 'identified', 'monitoring', 'resolved'];

type TimelineEntry = {
    key: string;
    kind: 'event' | 'update';
    title: string;
    at: string | null;
    dot: string;
    author?: string;
    status?: string | null;
    isPublic?: boolean;
    bodyHtml?: string;
};

/**
 * The notes the user wrote, folded in with the incident's own lifecycle so the
 * sequence reads as one story rather than a list of comments floating next to
 * a start time.
 */
const timeline = computed<TimelineEntry[]>(() => {
    const entries: TimelineEntry[] = [
        {
            key: 'started',
            kind: 'event',
            title: trans('incidents.updates.events.started'),
            at: props.incident.started_at,
            dot: 'bg-destructive',
        },
    ];

    if (props.incident.is_acknowledged) {
        entries.push({
            key: 'acknowledged',
            kind: 'event',
            title: trans('incidents.updates.events.acknowledged', {
                name: props.incident.acknowledged_by?.name ?? '',
            }),
            at: props.incident.acknowledged_at,
            dot: 'bg-amber-500',
        });
    }

    for (const update of props.incident.updates ?? []) {
        entries.push({
            key: update.uuid,
            kind: 'update',
            title: update.author?.name ?? '',
            at: update.created_at,
            dot: 'bg-muted-foreground',
            status: update.status,
            isPublic: update.is_public,
            bodyHtml: update.body_html,
        });
    }

    // Sorted by time rather than appended in source order: an update can be
    // written before the acknowledgement, and the resolve is not always last
    // in the payload.
    entries.sort((a, b) => Date.parse(a.at ?? '') - Date.parse(b.at ?? ''));

    entries.push(
        props.incident.resolved_at
            ? {
                  key: 'resolved',
                  kind: 'event',
                  title: trans('incidents.updates.events.resolved'),
                  at: props.incident.resolved_at,
                  dot: 'bg-emerald-500',
              }
            : {
                  key: 'ongoing',
                  kind: 'event',
                  title: trans('incidents.updates.events.ongoing'),
                  at: null,
                  dot: 'bg-destructive animate-pulse',
              },
    );

    return entries;
});

function acknowledge() {
    router.post(
        acknowledgeRoute.store(props.incident.uuid).url,
        {},
        { preserveScroll: true },
    );
}

function unacknowledge() {
    router.delete(acknowledgeRoute.destroy(props.incident.uuid).url, {
        preserveScroll: true,
    });
}

function addUpdate() {
    router.post(
        incidentUpdatesRoute.store(props.incident.uuid).url,
        {
            body: body.value,
            status: status.value === 'none' ? null : status.value,
            is_public: isPublic.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                body.value = '';
                status.value = 'none';
                isPublic.value = false;
                errors.value = {};
            },
            onError: (bag) => (errors.value = bag),
        },
    );
}

setLayoutProps({
    breadcrumbs: [
        { title: trans('incidents.title'), href: incidentsRoute.index() },
        { title: props.incident.monitor?.name ?? '' },
    ],
});
</script>

<template>
    <Head :title="incident.monitor?.name ?? $t('incidents.title')" />

    <div class="flex flex-col gap-6">
        <PageHeader :title="incident.monitor?.name ?? $t('incidents.title')">
            <template #actions>
                <IncidentStatusBadge :is-ongoing="incident.is_ongoing" />
                <Button
                    v-if="!incident.is_acknowledged"
                    variant="outline"
                    @click="acknowledge"
                >
                    <CheckIcon />
                    {{ $t('incidents.actions.acknowledge') }}
                </Button>
                <Button v-else variant="ghost" @click="unacknowledge">
                    {{ $t('incidents.actions.unacknowledge') }}
                </Button>
            </template>
        </PageHeader>

        <div
            class="grid grid-cols-2 gap-px overflow-hidden rounded-md border bg-border lg:grid-cols-4"
        >
            <StatTile
                :label="$t('incidents.table.columns.started')"
                :value="formatDateTime(incident.started_at)"
            />
            <StatTile
                :label="$t('incidents.table.columns.duration')"
                :value="formatDuration(incident.duration_seconds)"
            />
            <StatTile
                :label="$t('incidents.table.columns.failed_checks')"
                :value="incident.failed_checks"
            />
            <StatTile
                :label="$t('incidents.table.columns.cause')"
                :value="incident.cause ?? '—'"
            />
        </div>

        <section class="rounded-md border">
            <h2 class="border-b px-4 py-3 text-sm font-medium">
                {{ $t('incidents.updates.title') }}
            </h2>

            <ol class="px-4 py-4">
                <li
                    v-for="(entry, index) in timeline"
                    :key="entry.key"
                    class="relative flex gap-3 pb-5 last:pb-0"
                >
                    <!-- The rail is drawn per row rather than as one element
                         behind the list, so it stops at the final dot instead
                         of trailing past it. -->
                    <span
                        v-if="index < timeline.length - 1"
                        class="absolute top-5 bottom-0 left-[5px] w-px bg-border"
                        aria-hidden="true"
                    />
                    <span
                        class="mt-1 size-2.5 shrink-0 rounded-full ring-4 ring-background"
                        :class="entry.dot"
                        aria-hidden="true"
                    />

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                            <span class="text-sm font-medium">{{
                                entry.title
                            }}</span>
                            <Badge
                                v-if="entry.status"
                                variant="outline"
                                class="font-normal"
                            >
                                {{
                                    $t(
                                        `incidents.updates.status.${entry.status}`,
                                    )
                                }}
                            </Badge>
                            <Badge
                                v-if="entry.kind === 'update'"
                                variant="outline"
                                class="gap-1 font-normal"
                            >
                                <component
                                    :is="entry.isPublic ? GlobeIcon : LockIcon"
                                    class="size-3"
                                    aria-hidden="true"
                                />
                                {{
                                    entry.isPublic
                                        ? $t('incidents.updates.public')
                                        : $t('incidents.updates.internal')
                                }}
                            </Badge>
                        </div>

                        <p class="mt-0.5 text-xs text-muted-foreground">
                            <template v-if="entry.author"
                                >{{ entry.author }} · </template
                            >
                            <time
                                v-if="entry.at"
                                :datetime="entry.at"
                                :title="formatDateTime(entry.at)"
                                >{{ formatRelative(entry.at) }}</time
                            >
                        </p>

                        <!-- Rendered server-side through MarkdownRenderer,
                             which strips raw HTML and unsafe link schemes. -->
                        <!-- eslint-disable-next-line vue/no-v-html -->
                        <div
                            v-if="entry.bodyHtml"
                            class="prose prose-sm dark:prose-invert mt-2"
                            v-html="entry.bodyHtml"
                        />
                    </div>
                </li>
            </ol>

            <p
                v-if="!incident.updates?.length"
                class="border-t px-4 py-3 text-sm text-muted-foreground"
            >
                {{ $t('incidents.updates.empty') }}
            </p>

            <div class="space-y-3 border-t px-4 py-4">
                <Field>
                    <FieldLabel for="body">{{
                        $t('incidents.updates.body')
                    }}</FieldLabel>
                    <textarea
                        id="body"
                        v-model="body"
                        rows="3"
                        class="w-full rounded-sm border bg-transparent px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    ></textarea>
                    <FieldError>{{ errors.body }}</FieldError>
                </Field>

                <div class="flex flex-wrap items-center gap-3">
                    <Select v-model="status">
                        <SelectTrigger class="w-52"
                            ><SelectValue
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="none">—</SelectItem>
                            <SelectItem
                                v-for="option in statuses"
                                :key="option"
                                :value="option"
                            >
                                {{ $t(`incidents.updates.status.${option}`) }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <label class="flex items-center gap-2 text-sm">
                        <Checkbox v-model="isPublic" />
                        {{ $t('incidents.updates.is_public') }}
                    </label>

                    <Button class="ml-auto" @click="addUpdate">
                        {{ $t('incidents.updates.add') }}
                    </Button>
                </div>
            </div>
        </section>

        <Button :as="Link" :href="incidentsRoute.index()" variant="ghost">
            {{ $t('incidents.title') }}
        </Button>
    </div>
</template>
