<template>
    <Head :title="$t('integrations.title')" />

    <div class="flex flex-col gap-8 p-4">
        <PageHeader
            :title="$t('integrations.heading')"
            :description="$t('integrations.subtitle')"
        />

        <Section
            v-can="'channels.create'"
            :title="$t('integrations.available')"
        >
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
                        <div
                            v-if="
                                integration.quiet_hours_start ||
                                integration.renotify_minutes
                            "
                            class="mt-1.5 flex flex-wrap gap-1"
                        >
                            <Badge
                                v-if="integration.quiet_hours_start"
                                variant="outline"
                                class="gap-1 font-normal"
                            >
                                <MoonIcon class="size-3" />
                                {{ integration.quiet_hours_start }}–{{
                                    integration.quiet_hours_end
                                }}
                            </Badge>
                            <Badge
                                v-if="integration.renotify_minutes"
                                variant="outline"
                                class="gap-1 font-normal"
                            >
                                <BellRingIcon class="size-3" />
                                {{ integration.renotify_minutes }}m ×{{
                                    integration.renotify_limit
                                }}
                            </Badge>
                        </div>
                    </div>

                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="ghost" size="sm">
                                <MoreHorizontalIcon />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem
                                v-can="'channels.edit'"
                                @select="openEdit(integration)"
                            >
                                <PencilIcon />
                                {{ $t('integrations.actions.edit') }}
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                v-can="'channels.edit'"
                                @select="sendTest(integration)"
                            >
                                <SendIcon />
                                {{ $t('integrations.actions.test') }}
                            </DropdownMenuItem>
                            <DropdownMenuSeparator v-can="'channels.delete'" />
                            <DropdownMenuItem
                                v-can="'channels.delete'"
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
        <DialogContent class="sm:max-w-3xl">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <component
                        :is="providerIcons[form.type]"
                        class="size-4 shrink-0 text-muted-foreground"
                    />
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

            <Tabs v-model="tab">
                <TabsList>
                    <TabsTrigger v-for="name in tabs" :key="name" :value="name">
                        {{ $t(`integrations.form.tabs.${name}`) }}
                        <span
                            v-if="tabHasError[name]"
                            class="size-1.5 rounded-full bg-destructive"
                        />
                    </TabsTrigger>
                </TabsList>

                <div class="max-h-[55vh] overflow-y-auto pt-4 pr-0.5">
                    <TabsContent value="setup" class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-1.5">
                                <Label for="integration-name">{{
                                    $t('integrations.form.name.title')
                                }}</Label>
                                <Input
                                    id="integration-name"
                                    v-model="form.name"
                                    :placeholder="
                                        $t('integrations.form.name.placeholder')
                                    "
                                />
                                <InputError :message="form.errors.name" />
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="integration-secret">{{
                                    $t(
                                        `integrations.providers.${form.type}.field`,
                                    )
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
                                    {{
                                        $t(
                                            `integrations.providers.${form.type}.hint`,
                                        )
                                    }}
                                </p>
                                <InputError :message="secretError" />
                            </div>
                        </div>

                        <div
                            class="flex items-center justify-between gap-3 rounded-sm border p-3"
                        >
                            <div>
                                <p class="text-sm font-medium">
                                    {{
                                        $t('integrations.form.is_active.title')
                                    }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{
                                        $t(
                                            'integrations.form.is_active.description',
                                        )
                                    }}
                                </p>
                            </div>
                            <Switch v-model:checked="form.is_active" />
                        </div>
                    </TabsContent>

                    <TabsContent value="scope" class="space-y-3">
                        <p class="text-xs text-muted-foreground">
                            {{ $t('integrations.form.scope.description') }}
                        </p>

                        <div class="grid gap-2 sm:grid-cols-2">
                            <label
                                v-for="scope in scopes"
                                :key="scope"
                                class="flex cursor-pointer items-start gap-2.5 rounded-sm border p-3 transition-colors hover:bg-muted/40"
                                :class="
                                    form.alert_scope === scope
                                        ? 'border-primary/50 bg-accent/40'
                                        : ''
                                "
                            >
                                <input
                                    v-model="form.alert_scope"
                                    type="radio"
                                    :value="scope"
                                    class="mt-0.5 accent-primary"
                                />
                                <span>
                                    <span class="block text-sm">{{
                                        $t(`integrations.form.scope.${scope}`)
                                    }}</span>
                                    <span
                                        class="block text-xs text-muted-foreground"
                                        >{{
                                            $t(
                                                `integrations.form.scope.${scope}_hint`,
                                            )
                                        }}</span
                                    >
                                </span>
                            </label>
                        </div>

                        <div v-if="form.alert_scope === 'selected'">
                            <p
                                v-if="monitors.length === 0"
                                class="text-xs text-muted-foreground"
                            >
                                {{ $t('integrations.form.scope.empty') }}
                            </p>
                            <template v-else>
                                <div
                                    class="mb-2 flex items-center justify-between gap-2"
                                >
                                    <p class="text-xs text-muted-foreground">
                                        {{
                                            $t(
                                                'integrations.attached',
                                                {
                                                    count: form.monitors.length,
                                                },
                                                form.monitors.length,
                                            )
                                        }}
                                    </p>
                                    <div class="flex gap-1">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="selectAllMonitors"
                                            >{{
                                                $t(
                                                    'integrations.form.scope.select_all',
                                                )
                                            }}</Button
                                        >
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="form.monitors = []"
                                            >{{
                                                $t(
                                                    'integrations.form.scope.clear',
                                                )
                                            }}</Button
                                        >
                                    </div>
                                </div>
                                <div
                                    class="grid max-h-64 overflow-y-auto rounded-sm border sm:grid-cols-2"
                                >
                                    <label
                                        v-for="monitor in monitors"
                                        :key="monitor.uuid"
                                        class="flex cursor-pointer items-center gap-3 border-b px-3 py-2 transition-colors last:border-b-0 hover:bg-muted/40 sm:[&:nth-last-child(2):nth-child(odd)]:border-b-0"
                                    >
                                        <Checkbox
                                            :model-value="
                                                form.monitors.includes(
                                                    monitor.uuid,
                                                )
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
                            </template>
                            <InputError :message="form.errors.monitors" />
                        </div>
                    </TabsContent>

                    <TabsContent value="message" class="space-y-4">
                        <p class="text-xs text-muted-foreground">
                            {{ $t('integrations.form.templates.description') }}
                        </p>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div
                                v-for="event in events"
                                :key="event"
                                class="grid content-start gap-2 rounded-sm border p-3"
                            >
                                <div
                                    class="flex flex-wrap items-center justify-between gap-2"
                                >
                                    <p class="text-sm font-medium">
                                        {{
                                            $t(
                                                `integrations.form.templates.${event}`,
                                            )
                                        }}
                                    </p>
                                    <div class="flex flex-wrap gap-1">
                                        <Button
                                            v-for="preset in presets[event]"
                                            :key="preset.key"
                                            variant="outline"
                                            size="sm"
                                            class="h-6 px-2 text-xs"
                                            @click="applyPreset(event, preset)"
                                        >
                                            {{
                                                $t(
                                                    `integrations.form.templates.presets.${preset.key}`,
                                                )
                                            }}
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-6 px-2 text-xs"
                                            @click="clearTemplate(event)"
                                        >
                                            {{
                                                $t(
                                                    'integrations.form.templates.presets.clear',
                                                )
                                            }}
                                        </Button>
                                    </div>
                                </div>

                                <Input
                                    v-model="form.templates[event].title"
                                    :placeholder="defaults[event].title"
                                    class="font-mono text-xs"
                                    @focus="
                                        rememberField(event, 'title', $event)
                                    "
                                />
                                <InputError
                                    :message="templateError(event, 'title')"
                                />

                                <template v-if="!ignoresBody">
                                    <textarea
                                        v-model="form.templates[event].body"
                                        rows="3"
                                        :placeholder="defaults[event].body"
                                        class="w-full rounded-sm border border-input bg-transparent px-2.5 py-2 font-mono text-xs transition-colors outline-none focus-visible:border-ring focus-visible:ring-1 focus-visible:ring-ring dark:bg-input/20"
                                        @focus="
                                            rememberField(event, 'body', $event)
                                        "
                                    />
                                    <InputError
                                        :message="templateError(event, 'body')"
                                    />
                                </template>

                                <div class="rounded-sm bg-muted/50 p-2.5">
                                    <p
                                        class="font-mono text-[10px] tracking-wide text-muted-foreground uppercase"
                                    >
                                        {{
                                            $t(
                                                'integrations.form.templates.preview',
                                            )
                                        }}
                                    </p>
                                    <p class="mt-1 text-sm font-medium">
                                        {{ preview(event).title }}
                                    </p>
                                    <p
                                        v-if="!ignoresBody"
                                        class="mt-0.5 text-xs whitespace-pre-wrap text-muted-foreground"
                                    >
                                        {{ preview(event).body }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <p class="text-xs text-muted-foreground">
                                {{
                                    $t(
                                        'integrations.form.templates.placeholders',
                                    )
                                }}
                                —
                                {{
                                    $t(
                                        'integrations.form.templates.insert_hint',
                                    )
                                }}
                            </p>
                            <div class="mt-1.5 flex flex-wrap gap-1">
                                <button
                                    v-for="placeholder in placeholders"
                                    :key="placeholder"
                                    type="button"
                                    class="rounded-sm bg-muted px-1.5 py-0.5 font-mono text-[10px] transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                                    @mousedown.prevent
                                    @click="insertPlaceholder(placeholder)"
                                >
                                    {{ braced(placeholder) }}
                                </button>
                            </div>
                        </div>

                        <p
                            v-if="ignoresBody"
                            class="text-xs text-muted-foreground"
                        >
                            {{ $t('integrations.form.templates.unsupported') }}
                        </p>
                    </TabsContent>

                    <TabsContent value="delivery" class="space-y-4">
                        <p class="text-xs text-muted-foreground">
                            {{ $t('integrations.form.delivery.description') }}
                        </p>

                        <div class="rounded-sm border">
                            <div
                                class="flex items-center justify-between gap-3 p-3"
                            >
                                <div>
                                    <p class="text-sm font-medium">
                                        {{
                                            $t(
                                                'integrations.form.delivery.renotify.title',
                                            )
                                        }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{
                                            $t(
                                                'integrations.form.delivery.renotify.description',
                                            )
                                        }}
                                    </p>
                                </div>
                                <Switch
                                    :checked="renotifyOn"
                                    @update:checked="toggleRenotify"
                                />
                            </div>

                            <div v-if="renotifyOn" class="border-t p-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <Label
                                        for="renotify-minutes"
                                        class="text-xs font-normal text-muted-foreground"
                                        >{{
                                            $t(
                                                'integrations.form.delivery.renotify.every',
                                            )
                                        }}</Label
                                    >
                                    <Input
                                        id="renotify-minutes"
                                        v-model="form.renotify_minutes"
                                        type="number"
                                        min="5"
                                        max="1440"
                                        class="h-8 w-20"
                                    />
                                    <span
                                        class="text-xs text-muted-foreground"
                                        >{{
                                            $t(
                                                'integrations.form.delivery.renotify.minutes',
                                            )
                                        }}</span
                                    >
                                    <Label
                                        for="renotify-limit"
                                        class="ml-2 text-xs font-normal text-muted-foreground"
                                        >{{
                                            $t(
                                                'integrations.form.delivery.renotify.limit',
                                            )
                                        }}</Label
                                    >
                                    <Input
                                        id="renotify-limit"
                                        v-model="form.renotify_limit"
                                        type="number"
                                        min="1"
                                        max="20"
                                        class="h-8 w-20"
                                    />
                                    <span
                                        class="text-xs text-muted-foreground"
                                        >{{
                                            $t(
                                                'integrations.form.delivery.renotify.reminders',
                                            )
                                        }}</span
                                    >
                                </div>
                                <p
                                    v-if="renotifySummary"
                                    class="mt-2 text-xs text-muted-foreground"
                                >
                                    {{ renotifySummary }}
                                </p>
                                <InputError
                                    :message="form.errors.renotify_minutes"
                                />
                                <InputError
                                    :message="form.errors.renotify_limit"
                                />
                            </div>
                            <p
                                v-else
                                class="border-t px-3 py-2 text-xs text-muted-foreground"
                            >
                                {{
                                    $t(
                                        'integrations.form.delivery.renotify.off_hint',
                                    )
                                }}
                            </p>
                        </div>

                        <div class="rounded-sm border">
                            <div
                                class="flex items-center justify-between gap-3 p-3"
                            >
                                <div>
                                    <p class="text-sm font-medium">
                                        {{
                                            $t(
                                                'integrations.form.delivery.quiet_hours.title',
                                            )
                                        }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{
                                            $t(
                                                'integrations.form.delivery.quiet_hours.description',
                                            )
                                        }}
                                    </p>
                                </div>
                                <Switch
                                    :checked="quietOn"
                                    @update:checked="toggleQuiet"
                                />
                            </div>

                            <div v-if="quietOn" class="space-y-3 border-t p-3">
                                <div class="flex flex-wrap items-end gap-3">
                                    <div class="grid gap-1.5">
                                        <Label
                                            for="quiet-start"
                                            class="text-xs font-normal text-muted-foreground"
                                            >{{
                                                $t(
                                                    'integrations.form.delivery.quiet_hours.from',
                                                )
                                            }}</Label
                                        >
                                        <Input
                                            id="quiet-start"
                                            v-model="form.quiet_hours_start"
                                            type="time"
                                            class="h-8 w-32"
                                        />
                                    </div>
                                    <div class="grid gap-1.5">
                                        <Label
                                            for="quiet-end"
                                            class="text-xs font-normal text-muted-foreground"
                                            >{{
                                                $t(
                                                    'integrations.form.delivery.quiet_hours.to',
                                                )
                                            }}</Label
                                        >
                                        <Input
                                            id="quiet-end"
                                            v-model="form.quiet_hours_end"
                                            type="time"
                                            class="h-8 w-32"
                                        />
                                    </div>
                                    <div class="min-w-48 flex-1">
                                        <div
                                            class="mb-1.5 flex items-center justify-between gap-2"
                                        >
                                            <Label
                                                for="quiet-timezone"
                                                class="text-xs font-normal text-muted-foreground"
                                                >{{
                                                    $t(
                                                        'integrations.form.delivery.quiet_hours.timezone',
                                                    )
                                                }}</Label
                                            >
                                            <button
                                                type="button"
                                                class="text-xs text-primary underline-offset-2 hover:underline focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                                                @click="useBrowserZone"
                                            >
                                                {{
                                                    $t(
                                                        'integrations.form.delivery.quiet_hours.use_browser',
                                                    )
                                                }}
                                            </button>
                                        </div>
                                        <TimezoneField
                                            id="quiet-timezone"
                                            v-model="form.quiet_hours_timezone"
                                        />
                                    </div>
                                </div>

                                <p
                                    v-if="quietSummary"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ quietSummary }}
                                    <template v-if="quietIsOvernight">
                                        {{
                                            $t(
                                                'integrations.form.delivery.quiet_hours.overnight',
                                            )
                                        }}
                                    </template>
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{
                                        $t(
                                            'integrations.form.delivery.quiet_hours.behaviour',
                                        )
                                    }}
                                </p>

                                <InputError
                                    :message="form.errors.quiet_hours_start"
                                />
                                <InputError
                                    :message="form.errors.quiet_hours_end"
                                />
                                <InputError
                                    :message="form.errors.quiet_hours_timezone"
                                />
                            </div>
                            <p
                                v-else
                                class="border-t px-3 py-2 text-xs text-muted-foreground"
                            >
                                {{
                                    $t(
                                        'integrations.form.delivery.quiet_hours.off_hint',
                                    )
                                }}
                            </p>
                        </div>
                    </TabsContent>
                </div>
            </Tabs>

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
    BellRingIcon,
    GlobeIcon,
    HashIcon,
    MailIcon,
    MessageSquareIcon,
    MoonIcon,
    MoreHorizontalIcon,
    PencilIcon,
    PlugIcon,
    SendIcon,
    SirenIcon,
    Trash2Icon,
} from 'lucide-vue-next';
import { computed, nextTick, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import Section from '@/components/Section.vue';
import TimezoneField from '@/components/TimezoneField.vue';
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { trans } from '@/lib/i18n';
import * as integrationsRoute from '@/routes/integrations';
import type { Monitor, NotificationChannel } from '@/types/monitors';

const props = defineProps<{
    integrations: NotificationChannel[];
    providers: string[];
    scopes: string[];
    placeholders: string[];
    monitors: Monitor[];
}>();

const events = ['down', 'recovered'] as const;

type AlertEvent = (typeof events)[number];
type TemplateField = 'title' | 'body';
type Template = { title: string; body: string };
type Preset = Template & { key: string };

const tabs = ['setup', 'scope', 'message', 'delivery'] as const;

type Tab = (typeof tabs)[number];

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

/**
 * Template text lives here rather than in the locale files because a literal
 * `{{ }}` is interpolation syntax to vue-i18n and would have to be escaped
 * character by character.
 *
 * `defaults` mirrors the built-in wording in App\Monitoring\AlertMessage, so a
 * blank field previews exactly what the server will send.
 */
const defaults: Record<AlertEvent, Template> = {
    down: {
        title: '{{monitor.name}} is DOWN',
        body: '{{monitor.name}} ({{monitor.url}}) stopped responding: {{error}}',
    },
    recovered: {
        title: '{{monitor.name}} is back UP',
        body: '{{monitor.name}} ({{monitor.url}}) is responding again after {{incident.duration}}.',
    },
};

const presets: Record<AlertEvent, Preset[]> = {
    down: [
        { key: 'default', ...defaults.down },
        {
            key: 'detailed',
            title: '🔴 {{monitor.name}} is down',
            body:
                '{{monitor.name}} stopped responding at {{occurred_at}}.\n\n' +
                'URL: {{monitor.url}}\n' +
                'Error: {{error}}\n' +
                'Details: {{monitor.link}}',
        },
        {
            key: 'short',
            title: 'DOWN: {{monitor.name}}',
            body: '{{monitor.url}} — {{error}}',
        },
    ],
    recovered: [
        { key: 'default', ...defaults.recovered },
        {
            key: 'detailed',
            title: '🟢 {{monitor.name}} has recovered',
            body:
                '{{monitor.name}} is responding again as of {{occurred_at}}.\n\n' +
                'URL: {{monitor.url}}\n' +
                'Downtime: {{incident.duration}}\n' +
                'Details: {{monitor.link}}',
        },
        {
            key: 'short',
            title: 'UP: {{monitor.name}}',
            body: '{{monitor.url}} — back after {{incident.duration}}',
        },
    ],
};

const formOpen = ref(false);
const tab = ref<Tab>('setup');
const editing = ref<NotificationChannel | null>(null);
const confirmingDisconnect = ref(false);
const pendingDisconnect = ref<NotificationChannel | null>(null);
const lastField = ref<{
    event: AlertEvent;
    field: TemplateField;
    el: HTMLInputElement | HTMLTextAreaElement;
} | null>(null);

const form = useForm({
    name: '',
    type: 'email',
    secret: '',
    is_active: true,
    alert_scope: 'all',
    monitors: [] as string[],
    templates: emptyTemplates(),
    // Always populated, even while switched off: the inputs bind to them, and
    // a null would have to be handled at every binding. The toggles below
    // decide what the payload actually sends.
    renotify_minutes: 30 as number | string,
    renotify_limit: 3 as number | string,
    quiet_hours_start: '22:00',
    quiet_hours_end: '07:00',
    quiet_hours_timezone: '',
});

const browserZone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';

const renotifyOn = ref(false);
const quietOn = ref(false);

const quietIsOvernight = computed(
    () => quietOn.value && form.quiet_hours_start > form.quiet_hours_end,
);

const renotifySummary = computed(() =>
    renotifyOn.value
        ? trans('integrations.form.delivery.renotify.summary', {
              minutes: form.renotify_minutes,
              count: form.renotify_limit,
          })
        : '',
);

const quietSummary = computed(() =>
    quietOn.value
        ? trans('integrations.form.delivery.quiet_hours.summary', {
              start: form.quiet_hours_start,
              end: form.quiet_hours_end,
          })
        : '',
);

function toggleRenotify(on: boolean) {
    renotifyOn.value = on;
}

function toggleQuiet(on: boolean) {
    quietOn.value = on;

    // The server requires a zone alongside the window, and the channel's own
    // zone is the one that matters — not the server's.
    if (on && !form.quiet_hours_timezone) {
        form.quiet_hours_timezone = browserZone;
    }
}

function useBrowserZone() {
    form.quiet_hours_timezone = browserZone;
}

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

/** Which tab owns each validation error, so a failure is never hidden. */
const tabHasError = computed<Record<Tab, boolean>>(() => {
    const keys = Object.keys(form.errors);

    return {
        setup: keys.some(
            (key) =>
                ['name', 'type', 'is_active'].includes(key) ||
                key.startsWith('config'),
        ),
        scope: keys.some(
            (key) => key === 'alert_scope' || key.startsWith('monitors'),
        ),
        message: keys.some((key) => key.startsWith('templates')),
        delivery: keys.some(
            (key) =>
                key.startsWith('renotify') || key.startsWith('quiet_hours'),
        ),
    };
});

function templateError(event: AlertEvent, field: TemplateField) {
    return dottedError(`templates.${event}.${field}`);
}

// Built here rather than inline: a literal `{{` inside a template expression
// is a Vue parsing error.
function braced(placeholder: string) {
    return `{{${placeholder}}}`;
}

function emptyTemplates(): Record<AlertEvent, Template> {
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

function applyPreset(event: AlertEvent, preset: Preset) {
    form.templates[event].title = preset.title;
    form.templates[event].body = preset.body;
}

function clearTemplate(event: AlertEvent) {
    form.templates[event].title = '';
    form.templates[event].body = '';
}

function rememberField(
    event: AlertEvent,
    field: TemplateField,
    focus: FocusEvent,
) {
    lastField.value = {
        event,
        field,
        el: focus.target as HTMLInputElement | HTMLTextAreaElement,
    };
}

/**
 * Drops a placeholder at the caret of the field the user last touched. The
 * chip suppresses mousedown so the field keeps focus and its selection.
 */
function insertPlaceholder(placeholder: string) {
    const target = lastField.value;
    const event = target?.event ?? 'down';
    const field = target?.field ?? 'title';
    // Switching tabs unmounts the field, which leaves a detached node behind
    // with a caret offset that no longer means anything.
    const el = target?.el.isConnected ? target.el : null;
    const text = braced(placeholder);
    const current = form.templates[event][field];
    const start = el?.selectionStart ?? current.length;
    const end = el?.selectionEnd ?? current.length;

    form.templates[event][field] =
        current.slice(0, start) + text + current.slice(end);

    if (!el) {
        return;
    }

    const caret = start + text.length;

    nextTick(() => {
        el.focus();
        el.setSelectionRange(caret, caret);
    });
}

/** Mirrors AlertTemplate::apply — a whitelist lookup, nothing evaluated. */
function render(template: string, event: AlertEvent) {
    const values: Record<string, string> = {
        'monitor.name': trans('integrations.test.sample_monitor'),
        'monitor.url': 'https://example.com',
        'monitor.type': 'http',
        'monitor.uuid': '9f1c0e7a-5b2d-4c8e-9a3f-1d2b3c4d5e6f',
        'monitor.link': `${typeof window === 'undefined' ? '' : window.location.origin}/monitors/9f1c0e7a`,
        event,
        error: 'Connection timed out after 10s',
        occurred_at: new Date().toLocaleString(),
        'incident.duration': '12m',
    };

    return template.replace(
        /\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/g,
        (_match, key: string) => values[key] ?? '',
    );
}

function preview(event: AlertEvent) {
    const template = form.templates[event];

    return {
        title: render(template.title.trim() || defaults[event].title, event),
        body: render(template.body.trim() || defaults[event].body, event),
    };
}

function toggleMonitor(uuid: string) {
    form.monitors = form.monitors.includes(uuid)
        ? form.monitors.filter((value) => value !== uuid)
        : [...form.monitors, uuid];
}

function selectAllMonitors() {
    form.monitors = props.monitors.map((monitor) => monitor.uuid);
}

function openForm() {
    tab.value = 'setup';
    lastField.value = null;
    formOpen.value = true;
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
    renotifyOn.value = false;
    quietOn.value = false;
    form.renotify_minutes = 30;
    form.renotify_limit = 3;
    form.quiet_hours_start = '22:00';
    form.quiet_hours_end = '07:00';
    form.quiet_hours_timezone = browserZone;
    openForm();
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
    renotifyOn.value = integration.renotify_minutes !== null;
    quietOn.value =
        integration.quiet_hours_start !== null &&
        integration.quiet_hours_end !== null;
    form.renotify_minutes = integration.renotify_minutes ?? 30;
    form.renotify_limit = integration.renotify_limit ?? 3;
    form.quiet_hours_start = integration.quiet_hours_start ?? '22:00';
    form.quiet_hours_end = integration.quiet_hours_end ?? '07:00';
    form.quiet_hours_timezone = integration.quiet_hours_timezone ?? browserZone;
    openForm();
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
        // Off is sent as null rather than omitted, so clearing a window on an
        // existing integration actually unsets it. Number inputs hand back
        // strings, which the integer rules reject.
        renotify_minutes: renotifyOn.value
            ? numeric(form.renotify_minutes)
            : null,
        renotify_limit: numeric(form.renotify_limit) ?? 3,
        quiet_hours_start: quietOn.value ? form.quiet_hours_start : null,
        quiet_hours_end: quietOn.value ? form.quiet_hours_end : null,
        quiet_hours_timezone: quietOn.value ? form.quiet_hours_timezone : null,
    };
}

function numeric(value: number | string) {
    if (value === '') {
        return null;
    }

    const parsed = Number(value);

    return Number.isFinite(parsed) ? parsed : null;
}

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            formOpen.value = false;
        },
        // Errors can land on a tab the user is not looking at.
        onError: () => {
            tab.value =
                tabs.find((name) => tabHasError.value[name]) ?? tab.value;
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
