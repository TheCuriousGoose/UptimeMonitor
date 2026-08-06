<script setup lang="ts">
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import { CheckIcon, GlobeIcon, LockIcon } from 'lucide-vue-next';
import { ref } from 'vue';
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
import { formatDateTime, formatDuration } from '@/lib/format';
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

        <p
            v-if="incident.is_acknowledged"
            class="text-sm text-muted-foreground"
        >
            {{ incident.acknowledged_by?.name }} ·
            {{ formatDateTime(incident.acknowledged_at) }}
        </p>

        <section class="rounded-md border">
            <h2 class="border-b px-4 py-3 text-sm font-medium">
                {{ $t('incidents.updates.title') }}
            </h2>

            <ol v-if="incident.updates?.length" class="divide-y">
                <li
                    v-for="update in incident.updates"
                    :key="update.uuid"
                    class="px-4 py-3"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge v-if="update.status" variant="outline">
                            {{
                                $t(`incidents.updates.status.${update.status}`)
                            }}
                        </Badge>
                        <component
                            :is="update.is_public ? GlobeIcon : LockIcon"
                            class="size-3.5 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <span class="text-xs text-muted-foreground">
                            {{ update.author?.name }} ·
                            {{ formatDateTime(update.created_at) }}
                        </span>
                    </div>
                    <!-- Rendered server-side through MarkdownRenderer, which
                         strips raw HTML and unsafe link schemes. -->
                    <!-- eslint-disable-next-line vue/no-v-html -->
                    <div
                        class="prose prose-sm dark:prose-invert mt-2"
                        v-html="update.body_html"
                    />
                </li>
            </ol>

            <p v-else class="px-4 py-6 text-sm text-muted-foreground">
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
