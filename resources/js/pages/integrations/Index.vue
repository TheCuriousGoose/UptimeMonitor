<template>
    <Head :title="$t('integrations.title')" />

    <div class="flex flex-col gap-8 p-4">
        <PageHeader
            :title="$t('integrations.heading')"
            :description="$t('integrations.subtitle')"
        />

        <Section :title="$t('integrations.available')">
            <div class="grid gap-3 md:grid-cols-3">
                <button
                    v-for="provider in providers"
                    :key="provider"
                    type="button"
                    class="flex flex-col items-start rounded-sm border p-4 text-left transition-colors hover:border-foreground/25 hover:bg-accent focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                    @click="openConnect(provider)"
                >
                    <span class="flex items-center gap-2">
                        <component
                            :is="providerIcons[provider]"
                            class="size-4 shrink-0 text-muted-foreground"
                        />
                        <span class="text-sm font-medium">{{
                            $t(`integrations.providers.${provider}.name`)
                        }}</span>
                    </span>
                    <span class="mt-1.5 text-xs text-muted-foreground">
                        {{
                            $t(`integrations.providers.${provider}.description`)
                        }}
                    </span>
                    <span
                        class="mt-3 font-mono text-[10px] tracking-wide text-primary uppercase"
                    >
                        {{ $t('integrations.form.connect') }}
                    </span>
                </button>
            </div>
        </Section>

        <Section :title="$t('integrations.connected')">
            <EmptyState
                v-if="integrations.length === 0"
                :icon="PlugIcon"
                :title="$t('integrations.empty.title')"
                :description="$t('integrations.empty.description')"
            />

            <ul v-else class="divide-y rounded-sm border">
                <li
                    v-for="integration in integrations"
                    :key="integration.uuid"
                    class="flex items-start justify-between gap-3 px-4 py-3 transition-colors hover:bg-muted/40"
                >
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <component
                                :is="providerIcons[integration.type]"
                                class="size-4 shrink-0 text-muted-foreground"
                            />
                            <p class="truncate font-medium">
                                {{ integration.name }}
                            </p>
                            <Badge
                                v-if="!integration.is_active"
                                variant="secondary"
                            >
                                {{ $t('integrations.form.is_active.title') }}:
                                off
                            </Badge>
                        </div>
                        <p
                            class="mt-1 truncate font-mono text-sm text-muted-foreground"
                        >
                            {{ integration.destination }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{
                                $t(
                                    `integrations.providers.${integration.type}.name`,
                                )
                            }}
                            ·
                            <template v-if="integration.alert_scope === 'all'">
                                {{ $t('integrations.all_monitors') }}
                            </template>
                            <template
                                v-else-if="
                                    integration.monitors_count !== undefined
                                "
                            >
                                {{
                                    $t(
                                        'integrations.attached',
                                        { count: integration.monitors_count },
                                        integration.monitors_count,
                                    )
                                }}
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
                            <DropdownMenuItem @select="openEdit(integration)">
                                <PencilIcon />
                                {{ $t('integrations.actions.edit') }}
                            </DropdownMenuItem>
                            <DropdownMenuItem @select="sendTest(integration)">
                                <SendIcon />
                                {{ $t('integrations.actions.test') }}
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                variant="destructive"
                                @select="askDisconnect(integration)"
                            >
                                <Trash2Icon />
                                {{ $t('integrations.actions.disconnect') }}
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </li>
            </ul>
        </Section>
    </div>

    <Dialog v-model:open="formOpen">
        <DialogContent class="max-h-[85vh] overflow-y-auto sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>
                    {{
                        editing
                            ? $t('integrations.form.edit')
                            : $t(`integrations.providers.${form.type}.name`)
                    }}
                </DialogTitle>
                <DialogDescription>
                    {{ $t(`integrations.providers.${form.type}.description`) }}
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4 py-1">
                <div class="grid gap-1.5">
                    <Label for="integration-name">{{
                        $t('integrations.form.name.title')
                    }}</Label>
                    <Input
                        id="integration-name"
                        v-model="form.name"
                        :placeholder="$t('integrations.form.name.placeholder')"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="integration-secret">{{
                        $t(`integrations.providers.${form.type}.field`)
                    }}</Label>
                    <Input
                        id="integration-secret"
                        v-model="form.secret"
                        :type="inputType"
                        autocomplete="off"
                        :placeholder="
                            $t(
                                `integrations.providers.${form.type}.placeholder`,
                            )
                        "
                    />
                    <p class="text-xs text-muted-foreground">
                        {{ $t(`integrations.providers.${form.type}.hint`) }}
                    </p>
                    <InputError :message="secretError" />
                </div>

                <!-- Alert scope -->
                <div class="grid gap-2 rounded-sm border p-3">
                    <div>
                        <p class="text-sm font-medium">
                            {{ $t('integrations.form.scope.title') }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ $t('integrations.form.scope.description') }}
                        </p>
                    </div>

                    <label
                        v-for="scope in scopes"
                        :key="scope"
                        class="flex cursor-pointer items-start gap-2.5"
                    >
                        <input
                            v-model="form.alert_scope"
                            type="radio"
                            :value="scope"
                            class="mt-1 accent-primary"
                        />
                        <span>
                            <span class="block text-sm">{{
                                $t(`integrations.form.scope.${scope}`)
                            }}</span>
                            <span class="block text-xs text-muted-foreground">{{
                                $t(`integrations.form.scope.${scope}_hint`)
                            }}</span>
                        </span>
                    </label>

                    <div v-if="form.alert_scope === 'selected'" class="mt-1">
                        <p
                            v-if="monitors.length === 0"
                            class="text-xs text-muted-foreground"
                        >
                            {{ $t('integrations.form.scope.empty') }}
                        </p>
                        <div
                            v-else
                            class="max-h-48 divide-y overflow-y-auto rounded-sm border"
                        >
                            <label
                                v-for="monitor in monitors"
                                :key="monitor.uuid"
                                class="flex cursor-pointer items-center gap-3 px-3 py-2 transition-colors hover:bg-muted/40"
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
                </div>

                <!-- Custom message templates -->
                <div class="grid gap-3 rounded-sm border p-3">
                    <div>
                        <p class="text-sm font-medium">
                            {{ $t('integrations.form.templates.title') }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ $t('integrations.form.templates.description') }}
                        </p>
                    </div>

                    <div
                        v-for="event in events"
                        :key="event"
                        class="grid gap-1.5"
                    >
                        <p class="text-xs font-medium">
                            {{ $t(`integrations.form.templates.${event}`) }}
                        </p>
                        <Input
                            v-model="form.templates[event].title"
                            :placeholder="
                                $t('integrations.form.templates.subject')
                            "
                        />
                        <InputError :message="templateError(event, 'title')" />
                        <textarea
                            v-model="form.templates[event].body"
                            rows="2"
                            :placeholder="
                                $t('integrations.form.templates.body')
                            "
                            class="w-full rounded-sm border border-input bg-transparent px-2.5 py-2 text-sm transition-colors outline-none focus-visible:border-ring focus-visible:ring-1 focus-visible:ring-ring dark:bg-input/20"
                        />
                        <InputError :message="templateError(event, 'body')" />
                    </div>

                    <div>
                        <p class="text-xs text-muted-foreground">
                            {{ $t('integrations.form.templates.placeholders') }}
                        </p>
                        <div class="mt-1 flex flex-wrap gap-1">
                            <code
                                v-for="placeholder in placeholders"
                                :key="placeholder"
                                class="rounded-sm bg-muted px-1.5 py-0.5 font-mono text-[10px]"
                            >
                                {{ braced(placeholder) }}
                            </code>
                        </div>
                    </div>

                    <p v-if="ignoresBody" class="text-xs text-muted-foreground">
                        {{ $t('integrations.form.templates.unsupported') }}
                    </p>
                </div>

                <div
                    class="flex items-center justify-between gap-3 rounded-sm border p-3"
                >
                    <div>
                        <p class="text-sm font-medium">
                            {{ $t('integrations.form.is_active.title') }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ $t('integrations.form.is_active.description') }}
                        </p>
                    </div>
                    <Switch v-model:checked="form.is_active" />
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="formOpen = false">{{
                    $t('base.cancel')
                }}</Button>
                <Button :disabled="form.processing" @click="submit">
                    <Spinner v-if="form.processing" />
                    {{ $t('integrations.form.submit') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <ConfirmDialog
        v-model:open="confirmingDisconnect"
        :title="$t('integrations.actions.disconnect')"
        :description="$t('integrations.actions.confirm_disconnect')"
        :confirm-label="$t('integrations.actions.disconnect')"
        destructive
        @confirm="disconnect"
    />
</template>

<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    BellIcon,
    GlobeIcon,
    HashIcon,
    MailIcon,
    MessageSquareIcon,
    MoreHorizontalIcon,
    PencilIcon,
    PlugIcon,
    SendIcon,
    SirenIcon,
    Trash2Icon,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import Section from '@/components/Section.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
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
import { trans } from '@/lib/i18n';
import * as integrationsRoute from '@/routes/integrations';
import type { Monitor, NotificationChannel } from '@/types/monitors';

defineProps<{
    integrations: NotificationChannel[];
    providers: string[];
    scopes: string[];
    placeholders: string[];
    monitors: Monitor[];
}>();

const events = ['down', 'recovered'] as const;

type AlertEvent = (typeof events)[number];

const providerIcons: Record<string, unknown> = {
    email: MailIcon,
    webhook: GlobeIcon,
    slack: HashIcon,
    discord: MessageSquareIcon,
    pagerduty: SirenIcon,
    opsgenie: BellIcon,
    teams: MessageSquareIcon,
};

/** The config key each provider stores its destination under. */
const secretKeys: Record<string, string> = {
    email: 'email',
    webhook: 'url',
    slack: 'url',
    discord: 'url',
    pagerduty: 'routing_key',
    opsgenie: 'api_key',
    teams: 'url',
};

/**
 * These store a credential rather than an address, so the server masks it and
 * never sends it back — editing means re-entering it.
 */
const credentialTypes = ['pagerduty', 'opsgenie'];

/** Neither carries free-form body text on the wire. */
const bodylessTypes = ['pagerduty', 'opsgenie'];

const formOpen = ref(false);
const editing = ref<NotificationChannel | null>(null);
const confirmingDisconnect = ref(false);
const pendingDisconnect = ref<NotificationChannel | null>(null);

const form = useForm({
    name: '',
    type: 'email',
    secret: '',
    is_active: true,
    alert_scope: 'all',
    monitors: [] as string[],
    templates: emptyTemplates(),
});

const inputType = computed(() => {
    if (form.type === 'email') {
        return 'email';
    }

    return credentialTypes.includes(form.type) ? 'text' : 'url';
});

const ignoresBody = computed(() => bodylessTypes.includes(form.type));

// The server validates nested payloads, so its error keys are dotted and sit
// outside the flat shape useForm infers.
function dottedError(key: string) {
    const errors = form.errors as unknown as Record<string, string | undefined>;

    return errors[key];
}

const secretError = computed(() =>
    dottedError(`config.${secretKeys[form.type]}`),
);

function templateError(event: AlertEvent, field: 'title' | 'body') {
    return dottedError(`templates.${event}.${field}`);
}

// Built here rather than inline: a literal `{{` inside a template expression
// is a Vue parsing error.
function braced(placeholder: string) {
    return `{{${placeholder}}}`;
}

function emptyTemplates() {
    return {
        down: { title: '', body: '' },
        recovered: { title: '', body: '' },
    };
}

function templatesFrom(integration: NotificationChannel) {
    const templates = emptyTemplates();

    for (const event of events) {
        templates[event].title = integration.templates?.[event]?.title ?? '';
        templates[event].body = integration.templates?.[event]?.body ?? '';
    }

    return templates;
}

function toggleMonitor(uuid: string) {
    form.monitors = form.monitors.includes(uuid)
        ? form.monitors.filter((value) => value !== uuid)
        : [...form.monitors, uuid];
}

function openConnect(provider: string) {
    editing.value = null;
    form.clearErrors();
    form.name = trans(`integrations.providers.${provider}.name`);
    form.type = provider;
    form.secret = '';
    form.is_active = true;
    form.alert_scope = 'all';
    form.monitors = [];
    form.templates = emptyTemplates();
    formOpen.value = true;
}

function openEdit(integration: NotificationChannel) {
    editing.value = integration;
    form.clearErrors();
    form.name = integration.name;
    form.type = integration.type;
    // Addresses and webhook URLs come back intact and can be edited in place.
    // Credentials are masked server-side, so those start blank and must be
    // re-entered rather than round-tripping the mask back as the new value.
    form.secret = credentialTypes.includes(integration.type)
        ? ''
        : integration.destination;
    form.is_active = integration.is_active;
    form.alert_scope = integration.alert_scope;
    form.monitors = [...(integration.monitors ?? [])];
    form.templates = templatesFrom(integration);
    formOpen.value = true;
}

function payload() {
    return {
        name: form.name,
        type: form.type,
        is_active: form.is_active,
        config: { [secretKeys[form.type]]: form.secret },
        alert_scope: form.alert_scope,
        monitors: form.monitors,
        templates: form.templates,
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
            integrationsRoute.update(editing.value.uuid).url,
            options,
        );

        return;
    }

    form.transform(payload).post(integrationsRoute.store().url, options);
}

function sendTest(integration: NotificationChannel) {
    router.post(
        integrationsRoute.test(integration.uuid).url,
        {},
        { preserveScroll: true },
    );
}

function askDisconnect(integration: NotificationChannel) {
    pendingDisconnect.value = integration;
    confirmingDisconnect.value = true;
}

function disconnect() {
    if (!pendingDisconnect.value) {
        return;
    }

    router.delete(integrationsRoute.destroy(pendingDisconnect.value.uuid).url, {
        preserveScroll: true,
    });
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: trans('integrations.breadcrumbs.index'),
                href: integrationsRoute.index(),
            },
        ],
    },
});
</script>
