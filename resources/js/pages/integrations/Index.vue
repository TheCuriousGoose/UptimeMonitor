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
                        {{ $t(`integrations.providers.${provider}.description`) }}
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
                            <template
                                v-if="integration.monitors_count !== undefined"
                            >
                                ·
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
        <DialogContent class="sm:max-w-lg">
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
                        :type="form.type === 'teams' ? 'url' : 'text'"
                        autocomplete="off"
                        :placeholder="
                            $t(`integrations.providers.${form.type}.placeholder`)
                        "
                    />
                    <p class="text-xs text-muted-foreground">
                        {{ $t(`integrations.providers.${form.type}.hint`) }}
                    </p>
                    <InputError :message="secretError" />
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
import type { NotificationChannel } from '@/types/monitors';

defineProps<{
    integrations: NotificationChannel[];
    providers: string[];
}>();

const providerIcons: Record<string, unknown> = {
    pagerduty: SirenIcon,
    opsgenie: PlugIcon,
    teams: MessageSquareIcon,
};

/** The config key each provider stores its credential under. */
const secretKeys: Record<string, string> = {
    pagerduty: 'routing_key',
    opsgenie: 'api_key',
    teams: 'url',
};

const formOpen = ref(false);
const editing = ref<NotificationChannel | null>(null);
const confirmingDisconnect = ref(false);
const pendingDisconnect = ref<NotificationChannel | null>(null);

const form = useForm({
    name: '',
    type: 'pagerduty',
    secret: '',
    is_active: true,
});

// The server validates the nested config payload, so its error keys are
// dotted and sit outside the flat shape useForm infers.
const secretError = computed(() => {
    const errors = form.errors as unknown as Record<string, string | undefined>;

    return errors[`config.${secretKeys[form.type]}`];
});

function openConnect(provider: string) {
    editing.value = null;
    form.clearErrors();
    form.name = trans(`integrations.providers.${provider}.name`);
    form.type = provider;
    form.secret = '';
    form.is_active = true;
    formOpen.value = true;
}

function openEdit(integration: NotificationChannel) {
    editing.value = integration;
    form.clearErrors();
    form.name = integration.name;
    form.type = integration.type;
    // Credentials are masked server-side and never sent back, so editing
    // always means re-entering the secret rather than revealing the stored one.
    form.secret = '';
    form.is_active = integration.is_active;
    formOpen.value = true;
}

function payload() {
    return {
        name: form.name,
        type: form.type,
        is_active: form.is_active,
        config: { [secretKeys[form.type]]: form.secret },
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
