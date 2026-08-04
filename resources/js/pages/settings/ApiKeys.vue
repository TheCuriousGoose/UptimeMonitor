<template>
    <Head :title="$t('api_tokens.heading')" />

    <h1 class="sr-only">{{ $t('api_tokens.heading') }}</h1>

    <div class="flex flex-col gap-6">
        <Section
            :title="$t('api_tokens.heading')"
            :description="$t('api_tokens.subtitle')"
        >
            <template #actions>
                <Button size="sm" @click="openCreate">
                    <PlusIcon />
                    {{ $t('api_tokens.form.create') }}
                </Button>
            </template>

            <!-- The plaintext token only ever exists in this one flash — shown
                 exactly once, right after creation, then gone for good. -->
            <div
                v-if="revealed"
                class="mb-4 flex flex-col gap-2 rounded-sm border border-primary/30 bg-primary/5 p-4"
            >
                <p class="text-sm font-medium">
                    {{ $t('api_tokens.reveal.created', { name: revealed.name }) }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ $t('api_tokens.reveal.copy_now') }}
                </p>
                <div class="flex items-center gap-2">
                    <code
                        class="flex-1 truncate rounded-sm border bg-background px-2.5 py-1.5 font-mono text-xs"
                        >{{ revealed.token }}</code
                    >
                    <Button
                        type="button"
                        variant="outline"
                        size="icon-sm"
                        @click="copy(revealed.token)"
                    >
                        <CheckIcon v-if="copied" class="text-emerald-600" />
                        <CopyIcon v-else />
                    </Button>
                </div>
            </div>

            <EmptyState
                v-if="tokens.length === 0"
                :icon="KeyRoundIcon"
                :title="$t('api_tokens.empty.title')"
                :description="$t('api_tokens.empty.description')"
            />

            <ul v-else class="divide-y rounded-sm border">
                <li
                    v-for="token in tokens"
                    :key="token.id"
                    class="flex items-start justify-between gap-3 px-4 py-3"
                >
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ token.name }}</p>
                        <div class="mt-1.5 flex flex-wrap gap-1">
                            <Badge
                                v-for="ability in token.abilities"
                                :key="ability"
                                variant="outline"
                            >
                                {{ ability }}
                            </Badge>
                        </div>
                        <p class="mt-1.5 text-xs text-muted-foreground">
                            {{
                                token.last_used_at
                                    ? $t('api_tokens.status.last_used', {
                                          time: formatRelative(
                                              token.last_used_at,
                                          ),
                                      })
                                    : $t('api_tokens.status.never_used')
                            }}
                            ·
                            {{
                                token.expires_at
                                    ? $t('api_tokens.status.expires', {
                                          date: formatDateTime(
                                              token.expires_at,
                                          ),
                                      })
                                    : $t('api_tokens.status.never_expires')
                            }}
                        </p>
                    </div>

                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="askRevoke(token)"
                    >
                        <Trash2Icon />
                        {{ $t('api_tokens.actions.revoke') }}
                    </Button>
                </li>
            </ul>
        </Section>
    </div>

    <Dialog v-model:open="formOpen">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{ $t('api_tokens.form.create') }}</DialogTitle>
            </DialogHeader>

            <div class="space-y-4 py-1">
                <div class="grid gap-1.5">
                    <Label for="token-name">{{
                        $t('api_tokens.form.name.title')
                    }}</Label>
                    <Input
                        id="token-name"
                        v-model="form.name"
                        :placeholder="$t('api_tokens.form.name.placeholder')"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label>{{ $t('api_tokens.form.abilities.title') }}</Label>
                    <div class="space-y-2">
                        <label
                            v-for="ability in abilities"
                            :key="ability"
                            class="flex cursor-pointer items-center gap-3 rounded-sm border p-2.5 transition-colors hover:bg-muted/40"
                        >
                            <Checkbox
                                :model-value="form.abilities.includes(ability)"
                                @update:model-value="toggleAbility(ability)"
                            />
                            <span class="min-w-0">
                                <span
                                    class="block font-mono text-xs font-medium"
                                    >{{ ability }}</span
                                >
                                <span
                                    class="block text-xs text-muted-foreground"
                                    >{{
                                        $t(`api_tokens.abilities.${ability}`)
                                    }}</span
                                >
                            </span>
                        </label>
                    </div>
                    <InputError :message="form.errors.abilities" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="token-expires">{{
                        $t('api_tokens.form.expires.title')
                    }}</Label>
                    <Select v-model="expiresOption">
                        <SelectTrigger id="token-expires"
                            ><SelectValue
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="never">{{
                                $t('api_tokens.form.expires.never')
                            }}</SelectItem>
                            <SelectItem value="30">{{
                                $t('api_tokens.form.expires.in_30_days')
                            }}</SelectItem>
                            <SelectItem value="90">{{
                                $t('api_tokens.form.expires.in_90_days')
                            }}</SelectItem>
                            <SelectItem value="365">{{
                                $t('api_tokens.form.expires.in_1_year')
                            }}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.expires_in_days" />
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="formOpen = false">{{
                    $t('base.cancel')
                }}</Button>
                <Button :disabled="form.processing" @click="submit">
                    <Spinner v-if="form.processing" />
                    {{ $t('api_tokens.form.submit') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <ConfirmDialog
        v-model:open="confirmingRevoke"
        :title="$t('api_tokens.actions.confirm_revoke.title')"
        :description="$t('api_tokens.actions.confirm_revoke.description')"
        :confirm-label="$t('api_tokens.actions.revoke')"
        destructive
        @confirm="revoke"
    />
</template>

<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { useClipboard } from '@vueuse/core';
import {
    CheckIcon,
    CopyIcon,
    KeyRoundIcon,
    PlusIcon,
    Trash2Icon,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import Section from '@/components/Section.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import { formatDateTime, formatRelative } from '@/lib/format';
import { trans } from '@/lib/i18n';
import * as apiTokensRoute from '@/routes/api-tokens';

type ApiToken = {
    id: number;
    name: string;
    abilities: string[];
    last_used_at: string | null;
    expires_at: string | null;
    created_at: string | null;
};

defineProps<{
    tokens: ApiToken[];
    /** Raw App\Enums\ApiAbility values — labels come from api_tokens.abilities.*. */
    abilities: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: trans('api_tokens.heading'),
                href: apiTokensRoute.index(),
            },
        ],
    },
});

type RevealedToken = { name: string; token: string };

// The plaintext token is Inertia "flash" data, not a regular prop: it lives
// on page.flash for exactly one request and is gone on the next navigation,
// which is what makes the reveal-once behaviour work without any code here
// to explicitly clear it.
const revealed = computed(
    () =>
        (usePage().flash as Record<string, unknown>)
            ?.apiToken as RevealedToken | undefined,
);

const { copy, copied } = useClipboard();

const formOpen = ref(false);
const expiresOption = ref<string>('never');

const form = useForm<{
    name: string;
    abilities: string[];
    expires_in_days: number | null;
}>({
    name: '',
    abilities: [],
    expires_in_days: null,
});

function toggleAbility(value: string) {
    form.abilities = form.abilities.includes(value)
        ? form.abilities.filter((v) => v !== value)
        : [...form.abilities, value];
}

function openCreate() {
    form.reset();
    form.clearErrors();
    expiresOption.value = 'never';
    formOpen.value = true;
}

function submit() {
    form.expires_in_days =
        expiresOption.value === 'never' ? null : Number(expiresOption.value);

    form.post(apiTokensRoute.store().url, {
        preserveScroll: true,
        onSuccess: () => {
            formOpen.value = false;
        },
    });
}

const confirmingRevoke = ref(false);
const pendingRevoke = ref<ApiToken | null>(null);

function askRevoke(token: ApiToken) {
    pendingRevoke.value = token;
    confirmingRevoke.value = true;
}

function revoke() {
    if (!pendingRevoke.value) {
        return;
    }

    router.delete(apiTokensRoute.destroy(pendingRevoke.value.id).url, {
        preserveScroll: true,
    });
}
</script>
