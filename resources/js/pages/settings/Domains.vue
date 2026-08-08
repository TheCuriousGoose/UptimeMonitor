<template>
    <Head :title="$t('settings.domains.heading')" />

    <h1 class="sr-only">{{ $t('settings.domains.heading') }}</h1>

    <div class="flex flex-col gap-6">
        <Section
            :title="$t('settings.domains.heading')"
            :description="$t('settings.domains.description')"
        >
            <template #actions>
                <Button size="sm" @click="openAdd">
                    <PlusIcon />
                    {{ $t('settings.domains.add') }}
                </Button>
            </template>

            <p
                class="mb-4 rounded-sm border px-3 py-2 text-xs"
                :class="
                    required
                        ? 'border-amber-500/30 bg-amber-500/5 text-amber-700 dark:text-amber-400'
                        : 'text-muted-foreground'
                "
            >
                {{
                    required
                        ? $t('settings.domains.required_notice')
                        : $t('settings.domains.optional_notice')
                }}
            </p>

            <EmptyState
                v-if="domains.length === 0"
                :icon="GlobeIcon"
                :title="$t('settings.domains.empty')"
                :description="$t('settings.domains.description')"
            />

            <ul v-else class="divide-y rounded-sm border">
                <li
                    v-for="domain in domains"
                    :key="domain.uuid"
                    class="flex flex-col gap-3 px-4 py-3"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-medium">
                                {{ domain.domain }}
                            </p>
                            <p class="mt-1 flex items-center gap-2 text-xs">
                                <Badge
                                    :variant="
                                        domain.verified_at
                                            ? 'default'
                                            : 'outline'
                                    "
                                >
                                    {{
                                        domain.verified_at
                                            ? $t('settings.domains.verified')
                                            : $t('settings.domains.pending')
                                    }}
                                </Badge>
                                <span
                                    v-if="domain.last_attempted_at"
                                    class="text-muted-foreground"
                                >
                                    {{
                                        $t('settings.domains.last_checked', {
                                            time: formatRelative(
                                                domain.last_attempted_at,
                                            ),
                                        })
                                    }}
                                </span>
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-1">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="verify(domain)"
                            >
                                <RefreshCwIcon />
                                {{
                                    domain.verified_at
                                        ? $t('settings.domains.recheck')
                                        : $t('settings.domains.verify')
                                }}
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="askRemove(domain)"
                            >
                                <Trash2Icon />
                            </Button>
                        </div>
                    </div>

                    <div
                        v-if="!domain.verified_at"
                        class="rounded-sm border bg-muted/30 p-3 text-xs"
                    >
                        <p class="font-medium">
                            {{ $t('settings.domains.how_to') }}
                        </p>
                        <ul
                            class="mt-1.5 list-disc space-y-1 pl-4 text-muted-foreground"
                        >
                            <li>
                                {{
                                    $t('settings.domains.dns_option', {
                                        host: instructions.dns_host,
                                        domain: domain.domain,
                                    })
                                }}
                            </li>
                            <li>
                                {{
                                    $t('settings.domains.file_option', {
                                        domain: domain.domain,
                                        path: instructions.well_known_path,
                                    })
                                }}
                            </li>
                        </ul>

                        <p class="mt-2 font-medium">
                            {{ $t('settings.domains.token_label') }}
                        </p>
                        <div class="mt-1 flex items-center gap-2">
                            <code
                                class="flex-1 truncate rounded-sm border bg-background px-2.5 py-1.5 font-mono"
                                >{{ domain.token }}</code
                            >
                            <Button
                                type="button"
                                variant="outline"
                                size="icon-sm"
                                @click="copy(domain.token)"
                            >
                                <CheckIcon
                                    v-if="copied"
                                    class="text-emerald-600"
                                />
                                <CopyIcon v-else />
                            </Button>
                        </div>

                        <p
                            v-if="domain.last_error"
                            class="mt-2 text-destructive"
                        >
                            {{ domain.last_error }}
                        </p>
                    </div>
                </li>
            </ul>
        </Section>
    </div>

    <Dialog v-model:open="formOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ $t('settings.domains.add') }}</DialogTitle>
            </DialogHeader>

            <div class="grid gap-1.5 py-1">
                <Label for="domain">{{ $t('settings.domains.add') }}</Label>
                <Input
                    id="domain"
                    v-model="form.domain"
                    :placeholder="$t('settings.domains.add_placeholder')"
                />
                <InputError :message="form.errors.domain" />
            </div>

            <DialogFooter>
                <Button variant="outline" @click="formOpen = false">
                    {{ $t('base.cancel') }}
                </Button>
                <Button :disabled="form.processing" @click="submit">
                    <Spinner v-if="form.processing" />
                    {{ $t('settings.domains.add') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <ConfirmDialog
        v-model:open="confirmingRemove"
        :title="$t('settings.domains.remove')"
        :description="$t('settings.domains.description')"
        :confirm-label="$t('settings.domains.remove')"
        destructive
        @confirm="remove"
    />
</template>

<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { useClipboard } from '@vueuse/core';
import {
    CheckIcon,
    CopyIcon,
    GlobeIcon,
    PlusIcon,
    RefreshCwIcon,
    Trash2Icon,
} from 'lucide-vue-next';
import { ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import Section from '@/components/Section.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { formatRelative } from '@/lib/format';
import { trans } from '@/lib/i18n';
import * as domainsRoute from '@/routes/domains';

type VerifiedDomain = {
    uuid: string;
    domain: string;
    token: string;
    verified_at: string | null;
    last_error: string | null;
    last_attempted_at: string | null;
};

defineProps<{
    domains: VerifiedDomain[];
    instructions: { dns_host: string; well_known_path: string };
    required: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: trans('settings.domains.breadcrumb'),
                href: domainsRoute.index(),
            },
        ],
    },
});

const { copy, copied } = useClipboard();

const formOpen = ref(false);
const form = useForm<{ domain: string }>({ domain: '' });

function openAdd() {
    form.reset();
    form.clearErrors();
    formOpen.value = true;
}

function submit() {
    form.post(domainsRoute.store().url, {
        preserveScroll: true,
        onSuccess: () => {
            formOpen.value = false;
        },
    });
}

function verify(domain: VerifiedDomain) {
    router.post(
        domainsRoute.verify(domain.uuid).url,
        {},
        { preserveScroll: true },
    );
}

const confirmingRemove = ref(false);
const pendingRemove = ref<VerifiedDomain | null>(null);

function askRemove(domain: VerifiedDomain) {
    pendingRemove.value = domain;
    confirmingRemove.value = true;
}

function remove() {
    if (!pendingRemove.value) {
        return;
    }

    router.delete(domainsRoute.destroy(pendingRemove.value.uuid).url, {
        preserveScroll: true,
    });
}
</script>
