<template>
    <Head :title="$t('channels.heading')" />

    <div class="flex flex-col gap-4 p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold">
                    {{ $t('channels.heading') }}
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ $t('channels.subtitle') }}
                </p>
            </div>
            <Button @click="openCreate">
                <PlusIcon />
                {{ $t('channels.form.create') }}
            </Button>
        </div>

        <div
            v-if="channels.length === 0"
            class="rounded-xl border bg-card p-10 text-center"
        >
            <BellIcon class="mx-auto size-8 text-muted-foreground" />
            <h2 class="mt-4 text-lg font-semibold">
                {{ $t('channels.empty.title') }}
            </h2>
            <p class="mx-auto mt-1 max-w-md text-sm text-muted-foreground">
                {{ $t('channels.empty.description') }}
            </p>
        </div>

        <div v-else class="grid gap-3 md:grid-cols-2">
            <Card v-for="channel in channels" :key="channel.uuid">
                <CardContent
                    class="flex items-start justify-between gap-3 pt-6"
                >
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <component
                                :is="typeIcons[channel.type]"
                                class="size-4 shrink-0 text-muted-foreground"
                            />
                            <p class="truncate font-medium">
                                {{ channel.name }}
                            </p>
                            <Badge
                                v-if="!channel.is_active"
                                variant="secondary"
                            >
                                {{ $t('channels.form.is_active.title') }}: off
                            </Badge>
                        </div>
                        <p class="mt-1 truncate text-sm text-muted-foreground">
                            {{ channel.destination }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ $t('channels.types.' + channel.type) }}
                            <template
                                v-if="channel.monitors_count !== undefined"
                            >
                                ·
                                {{
                                    $t(
                                        'channels.attached',
                                        { count: channel.monitors_count },
                                        channel.monitors_count,
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
                            <DropdownMenuItem @select="openEdit(channel)">
                                <PencilIcon />
                                {{ $t('channels.actions.edit') }}
                            </DropdownMenuItem>
                            <DropdownMenuItem @select="sendTest(channel)">
                                <SendIcon />
                                {{ $t('channels.actions.test') }}
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                variant="destructive"
                                @select="askDelete(channel)"
                            >
                                <Trash2Icon />
                                {{ $t('channels.actions.delete') }}
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </CardContent>
            </Card>
        </div>
    </div>

    <Dialog v-model:open="formOpen">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>
                    {{
                        editing
                            ? $t('channels.form.edit')
                            : $t('channels.form.create')
                    }}
                </DialogTitle>
            </DialogHeader>

            <div class="space-y-4 py-1">
                <div class="grid gap-1.5">
                    <Label for="channel-name">{{
                        $t('channels.form.name.title')
                    }}</Label>
                    <Input
                        id="channel-name"
                        v-model="form.name"
                        :placeholder="$t('channels.form.name.placeholder')"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="channel-type">{{
                        $t('channels.form.type.title')
                    }}</Label>
                    <Select v-model="form.type">
                        <SelectTrigger id="channel-type"
                            ><SelectValue
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="option in types"
                                :key="option"
                                :value="option"
                            >
                                {{ $t('channels.types.' + option) }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p class="text-xs text-muted-foreground">
                        {{ $t('channels.hints.' + form.type) }}
                    </p>
                    <InputError :message="form.errors.type" />
                </div>

                <div v-if="form.type === 'email'" class="grid gap-1.5">
                    <Label for="channel-email">{{
                        $t('channels.form.email.title')
                    }}</Label>
                    <Input
                        id="channel-email"
                        v-model="form.email"
                        type="email"
                        :placeholder="$t('channels.form.email.placeholder')"
                    />
                    <InputError :message="configErrors['config.email']" />
                </div>

                <div v-else class="grid gap-1.5">
                    <Label for="channel-url">{{
                        $t('channels.form.url.title')
                    }}</Label>
                    <Input
                        id="channel-url"
                        v-model="form.url"
                        type="url"
                        :placeholder="$t('channels.form.url.placeholder')"
                    />
                    <InputError :message="configErrors['config.url']" />
                </div>

                <div
                    class="flex items-center justify-between gap-3 rounded-lg border p-3"
                >
                    <div>
                        <p class="text-sm font-medium">
                            {{ $t('channels.form.is_active.title') }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ $t('channels.form.is_active.description') }}
                        </p>
                    </div>
                    <Switch v-model="form.is_active" />
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="formOpen = false">{{
                    $t('base.cancel')
                }}</Button>
                <Button :disabled="form.processing" @click="submit">
                    <Spinner v-if="form.processing" />
                    {{ $t('channels.form.submit') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <ConfirmDialog
        v-model:open="confirmingDelete"
        :title="$t('channels.actions.delete')"
        :description="$t('channels.actions.confirm_delete')"
        :confirm-label="$t('base.delete')"
        destructive
        @confirm="destroy"
    />
</template>

<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    BellIcon,
    MailIcon,
    MessageCircleIcon,
    MoreHorizontalIcon,
    PencilIcon,
    PlusIcon,
    SendIcon,
    SlackIcon,
    Trash2Icon,
    WebhookIcon,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import { trans } from '@/lib/i18n';
import * as channelsRoute from '@/routes/channels';
import type { ChannelType, NotificationChannel } from '@/types/monitors';

defineProps<{
    channels: NotificationChannel[];
    types: ChannelType[];
}>();

const typeIcons: Record<ChannelType, unknown> = {
    email: MailIcon,
    webhook: WebhookIcon,
    slack: SlackIcon,
    discord: MessageCircleIcon,
};

const formOpen = ref(false);
const editing = ref<NotificationChannel | null>(null);
const confirmingDelete = ref(false);
const pendingDelete = ref<NotificationChannel | null>(null);

const form = useForm({
    name: '',
    type: 'email' as ChannelType,
    email: '',
    url: '',
    is_active: true,
});

// The server validates the nested config payload, so those error keys are
// dotted and sit outside the flat shape useForm infers.
const configErrors = computed(
    () => form.errors as unknown as Record<string, string | undefined>,
);

function openCreate() {
    editing.value = null;
    form.defaults({
        name: '',
        type: 'email',
        email: '',
        url: '',
        is_active: true,
    });
    form.reset();
    form.clearErrors();
    formOpen.value = true;
}

function openEdit(channel: NotificationChannel) {
    editing.value = channel;
    form.clearErrors();
    form.name = channel.name;
    form.type = channel.type;
    form.email = channel.type === 'email' ? channel.destination : '';
    form.url = channel.type === 'email' ? '' : channel.destination;
    form.is_active = channel.is_active;
    formOpen.value = true;
}

function payload() {
    return {
        name: form.name,
        type: form.type,
        is_active: form.is_active,
        config:
            form.type === 'email' ? { email: form.email } : { url: form.url },
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
            channelsRoute.update(editing.value.uuid).url,
            options,
        );

        return;
    }

    form.transform(payload).post(channelsRoute.store().url, options);
}

function sendTest(channel: NotificationChannel) {
    router.post(
        channelsRoute.test(channel.uuid).url,
        {},
        { preserveScroll: true },
    );
}

function askDelete(channel: NotificationChannel) {
    pendingDelete.value = channel;
    confirmingDelete.value = true;
}

function destroy() {
    if (!pendingDelete.value) {
        return;
    }

    router.delete(channelsRoute.destroy(pendingDelete.value.uuid).url, {
        preserveScroll: true,
    });
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: trans('channels.heading'),
                href: channelsRoute.index(),
            },
        ],
    },
});
</script>
