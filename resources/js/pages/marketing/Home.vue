<template>
    <SeoHead
        :title="$t('marketing.home.title')"
        :description="$t('marketing.home.subtitle')"
    />

    <!--
        JSON-LD has to be injected as raw text: interpolation would escape the
        quotes and crawlers would parse nothing. The content is JSON.stringify
        output over translated strings, so it carries no markup of its own.
    -->
    <!-- eslint-disable vue/no-v-text-v-html-on-component -->
    <component
        :is="'script'"
        type="application/ld+json"
        v-html="organizationLd"
    />
    <!-- eslint-enable vue/no-v-text-v-html-on-component -->

    <!-- Hero -->
    <section class="border-b">
        <div
            class="mx-auto grid w-full max-w-6xl gap-12 px-4 py-20 md:py-24 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:items-center lg:gap-16"
        >
            <div>
                <p
                    class="font-mono text-[11px] tracking-[0.14em] text-primary uppercase"
                >
                    {{ $t('marketing.home.eyebrow') }}
                </p>
                <h1
                    class="mt-4 text-4xl font-semibold tracking-tight text-balance md:text-5xl"
                >
                    {{ $t('marketing.home.title') }}
                </h1>
                <p class="mt-5 max-w-xl text-lg text-muted-foreground">
                    {{ $t('marketing.home.subtitle') }}
                </p>
                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <Button :as="Link" :href="register()" size="lg">
                        {{ $t('marketing.home.cta_primary') }}
                        <ArrowRightIcon />
                    </Button>
                    <Button
                        :as="Link"
                        :href="docs.index()"
                        variant="outline"
                        size="lg"
                    >
                        {{ $t('marketing.home.cta_secondary') }}
                    </Button>
                </div>
                <p class="mt-4 text-sm text-muted-foreground">
                    {{ $t('marketing.home.cta_note') }}
                </p>
            </div>

            <!-- Illustrative panel: sample data, in the same chrome as the app -->
            <div
                class="overflow-hidden rounded-md border bg-card"
                aria-hidden="true"
            >
                <div
                    class="flex items-center justify-between border-b px-4 py-2.5"
                >
                    <span
                        class="font-mono text-[10px] tracking-[0.12em] text-muted-foreground uppercase"
                    >
                        {{ $t('marketing.home.preview.label') }}
                    </span>
                    <span class="flex gap-1.5">
                        <span class="size-1.5 rounded-full bg-border" />
                        <span class="size-1.5 rounded-full bg-border" />
                        <span class="size-1.5 rounded-full bg-border" />
                    </span>
                </div>

                <table class="w-full text-sm">
                    <thead>
                        <tr
                            class="border-b text-[10px] tracking-wide text-muted-foreground uppercase"
                        >
                            <th class="px-4 py-2 text-left font-normal">
                                {{
                                    $t('marketing.home.preview.columns.monitor')
                                }}
                            </th>
                            <th
                                class="hidden px-4 py-2 text-right font-normal sm:table-cell"
                            >
                                {{
                                    $t('marketing.home.preview.columns.latency')
                                }}
                            </th>
                            <th class="px-4 py-2 text-right font-normal">
                                {{
                                    $t('marketing.home.preview.columns.uptime')
                                }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="row in previewRows" :key="row.key">
                            <td class="px-4 py-3">
                                <span class="flex items-center gap-2">
                                    <span
                                        class="size-1.5 shrink-0 rounded-full"
                                        :class="
                                            row.down
                                                ? 'bg-destructive'
                                                : 'bg-success'
                                        "
                                    />
                                    <span class="truncate font-medium">
                                        {{
                                            $t(
                                                `marketing.home.preview.rows.${row.key}.name`,
                                            )
                                        }}
                                    </span>
                                </span>
                                <span
                                    class="mt-1 block pl-3.5 font-mono text-[10px] tracking-wide text-muted-foreground uppercase"
                                >
                                    {{
                                        $t(
                                            `marketing.home.preview.rows.${row.key}.type`,
                                        )
                                    }}
                                </span>
                            </td>
                            <td
                                class="hidden px-4 py-3 text-right font-mono text-xs tabular-nums sm:table-cell"
                                :class="
                                    row.down
                                        ? 'text-destructive'
                                        : 'text-muted-foreground'
                                "
                            >
                                {{
                                    $t(
                                        `marketing.home.preview.rows.${row.key}.latency`,
                                    )
                                }}
                            </td>
                            <td
                                class="px-4 py-3 text-right font-mono text-xs tabular-nums"
                            >
                                {{
                                    $t(
                                        `marketing.home.preview.rows.${row.key}.uptime`,
                                    )
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <p
                    class="border-t border-l-2 border-l-destructive bg-destructive/5 px-4 py-3 text-xs text-muted-foreground"
                >
                    {{ $t('marketing.home.preview.incident') }}
                </p>
            </div>
        </div>
    </section>

    <!-- Ledger strip, same language as the in-app dashboard -->
    <section class="border-b">
        <div class="mx-auto w-full max-w-6xl px-4">
            <dl
                class="grid grid-cols-2 divide-x divide-y lg:grid-cols-4 lg:divide-y-0"
            >
                <div
                    v-for="stat in stats"
                    :key="stat.key"
                    class="flex flex-col gap-1.5 px-5 py-6 first:pl-0 lg:last:pr-0"
                >
                    <dd
                        class="font-mono text-3xl leading-none font-semibold tabular-nums"
                    >
                        {{ $t(`marketing.home.stats.${stat.key}.value`) }}
                    </dd>
                    <dt
                        class="text-xs tracking-wide text-muted-foreground uppercase"
                    >
                        {{ $t(`marketing.home.stats.${stat.key}.label`) }}
                    </dt>
                </div>
            </dl>
        </div>
    </section>

    <!-- How it works -->
    <section class="border-b">
        <div class="mx-auto w-full max-w-6xl px-4 py-20">
            <SectionHeading
                :eyebrow="$t('marketing.home.how.eyebrow')"
                :heading="$t('marketing.home.how.heading')"
                :lead="$t('marketing.home.how.lead')"
            />

            <ol class="mt-10 grid gap-px bg-border md:grid-cols-3">
                <li
                    v-for="(step, index) in steps"
                    :key="step"
                    class="bg-background pt-6 md:px-6 md:first:pl-0 md:last:pr-0"
                >
                    <span
                        class="font-mono text-[11px] tracking-[0.14em] text-muted-foreground"
                    >
                        {{ String(index + 1).padStart(2, '0') }}
                    </span>
                    <h3 class="mt-3 font-medium">
                        {{ $t(`marketing.home.how.steps.${step}.title`) }}
                    </h3>
                    <p class="mt-2 pb-6 text-sm text-muted-foreground">
                        {{ $t(`marketing.home.how.steps.${step}.body`) }}
                    </p>
                </li>
            </ol>
        </div>
    </section>

    <!-- Features -->
    <section class="border-b">
        <div class="mx-auto w-full max-w-6xl px-4 py-20">
            <SectionHeading
                :heading="$t('marketing.home.features_heading')"
                :lead="$t('marketing.home.features_lead')"
            />

            <div
                class="mt-10 grid gap-px overflow-hidden rounded-md border bg-border md:grid-cols-2 lg:grid-cols-3"
            >
                <article
                    v-for="feature in features"
                    :key="feature.key"
                    class="flex flex-col bg-background p-6"
                >
                    <component
                        :is="feature.icon"
                        class="size-5 text-primary"
                        aria-hidden="true"
                    />
                    <h3 class="mt-4 font-medium">
                        {{ $t(`marketing.home.features.${feature.key}.title`) }}
                    </h3>
                    <p class="mt-2 text-sm text-muted-foreground">
                        {{ $t(`marketing.home.features.${feature.key}.body`) }}
                    </p>
                </article>
            </div>
        </div>
    </section>

    <!-- Check types -->
    <section class="border-b">
        <div class="mx-auto w-full max-w-6xl px-4 py-20">
            <SectionHeading
                :eyebrow="$t('marketing.home.types.eyebrow')"
                :heading="$t('marketing.home.types.heading')"
                :lead="$t('marketing.home.types.lead')"
            />

            <div class="mt-10 overflow-x-auto rounded-md border">
                <table class="w-full min-w-[40rem] text-left text-sm">
                    <thead>
                        <tr
                            class="border-b text-[10px] tracking-wide text-muted-foreground uppercase"
                        >
                            <th class="px-5 py-3 font-normal">
                                {{ $t('marketing.home.types.columns.type') }}
                            </th>
                            <th class="px-5 py-3 font-normal">
                                {{ $t('marketing.home.types.columns.target') }}
                            </th>
                            <th class="px-5 py-3 font-normal">
                                {{ $t('marketing.home.types.columns.catches') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="type in checkTypes"
                            :key="type"
                            class="align-top transition-colors hover:bg-muted/40"
                        >
                            <td class="px-5 py-4 whitespace-nowrap">
                                <Badge variant="secondary">
                                    {{
                                        $t(
                                            `marketing.home.types.items.${type}.name`,
                                        )
                                    }}
                                </Badge>
                            </td>
                            <td
                                class="px-5 py-4 whitespace-nowrap text-muted-foreground"
                            >
                                {{
                                    $t(
                                        `marketing.home.types.items.${type}.target`,
                                    )
                                }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{
                                    $t(
                                        `marketing.home.types.items.${type}.catches`,
                                    )
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- Alerting -->
    <section class="border-b">
        <div class="mx-auto w-full max-w-6xl px-4 py-20">
            <SectionHeading
                :eyebrow="$t('marketing.home.alerts.eyebrow')"
                :heading="$t('marketing.home.alerts.heading')"
                :lead="$t('marketing.home.alerts.lead')"
            />

            <ul
                class="mt-10 grid gap-px overflow-hidden rounded-md border bg-border sm:grid-cols-2 lg:grid-cols-4"
            >
                <li
                    v-for="channel in channels"
                    :key="channel.key"
                    class="bg-background px-4 py-4"
                >
                    <span class="flex items-center gap-2">
                        <component
                            :is="channel.icon"
                            class="size-4 shrink-0 text-primary"
                            aria-hidden="true"
                        />
                        <span class="text-sm font-medium">
                            {{
                                $t(
                                    `marketing.home.alerts.channels.${channel.key}.name`,
                                )
                            }}
                        </span>
                    </span>
                    <span class="mt-1.5 block text-xs text-muted-foreground">
                        {{
                            $t(
                                `marketing.home.alerts.channels.${channel.key}.detail`,
                            )
                        }}
                    </span>
                </li>
            </ul>

            <div class="mt-8 grid gap-8 md:grid-cols-2">
                <div
                    v-for="point in alertPoints"
                    :key="point"
                    class="border-l-2 border-l-primary pl-4"
                >
                    <h3 class="text-sm font-medium">
                        {{ $t(`marketing.home.alerts.points.${point}.title`) }}
                    </h3>
                    <p class="mt-1.5 text-sm text-muted-foreground">
                        {{ $t(`marketing.home.alerts.points.${point}.body`) }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Status pages and API -->
    <section class="border-b">
        <div class="mx-auto w-full max-w-6xl px-4 py-20">
            <SectionHeading
                :eyebrow="$t('marketing.home.transparency.eyebrow')"
                :heading="$t('marketing.home.transparency.heading')"
            />

            <div
                class="mt-10 grid gap-px overflow-hidden rounded-md border bg-border lg:grid-cols-2"
            >
                <!-- Status pages -->
                <div class="flex flex-col bg-background p-6">
                    <GlobeIcon class="size-5 text-primary" aria-hidden="true" />
                    <h3 class="mt-4 font-medium">
                        {{ $t('marketing.home.transparency.status.title') }}
                    </h3>
                    <p class="mt-2 text-sm text-muted-foreground">
                        {{ $t('marketing.home.transparency.status.body') }}
                    </p>

                    <!-- 90 daily segments, the same shape a status page renders -->
                    <div
                        class="mt-6 flex gap-px overflow-hidden rounded-[3px]"
                        aria-hidden="true"
                    >
                        <span
                            v-for="(day, index) in uptimeDays"
                            :key="index"
                            class="h-8 flex-1"
                            :class="day ? 'bg-success/70' : 'bg-destructive/70'"
                        />
                    </div>
                    <p
                        class="mt-2 flex justify-between font-mono text-[10px] tracking-wide text-muted-foreground uppercase"
                    >
                        <span>90d</span>
                        <span>99.94%</span>
                    </p>

                    <ul class="mt-6 space-y-2.5">
                        <li
                            v-for="(item, index) in statusPoints"
                            :key="index"
                            class="flex gap-2.5 text-sm text-muted-foreground"
                        >
                            <CheckIcon
                                class="mt-0.5 size-4 shrink-0 text-primary"
                            />
                            {{ item }}
                        </li>
                    </ul>
                </div>

                <!-- API -->
                <div class="flex flex-col bg-background p-6">
                    <TerminalIcon
                        class="size-5 text-primary"
                        aria-hidden="true"
                    />
                    <h3 class="mt-4 font-medium">
                        {{ $t('marketing.home.transparency.api.title') }}
                    </h3>
                    <p class="mt-2 text-sm text-muted-foreground">
                        {{ $t('marketing.home.transparency.api.body') }}
                    </p>

                    <p
                        class="mt-6 font-mono text-[10px] tracking-[0.12em] text-muted-foreground uppercase"
                    >
                        {{ $t('marketing.home.transparency.api.sample_label') }}
                    </p>
                    <pre
                        class="mt-2 overflow-x-auto rounded-sm border bg-muted/40 p-4 font-mono text-xs leading-relaxed"
                    ><code>{{ apiSample }}</code></pre>

                    <p
                        class="mt-6 font-mono text-[10px] tracking-[0.12em] text-muted-foreground uppercase"
                    >
                        {{
                            $t(
                                'marketing.home.transparency.api.abilities_label',
                            )
                        }}
                    </p>
                    <ul class="mt-2 flex flex-wrap gap-1.5">
                        <li v-for="ability in abilities" :key="ability">
                            <Badge variant="outline">{{ ability }}</Badge>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Open source -->
    <section class="border-b">
        <div class="mx-auto w-full max-w-6xl px-4 py-20">
            <div
                class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]"
            >
                <div>
                    <SectionHeading
                        :eyebrow="$t('marketing.home.open.eyebrow')"
                        :heading="$t('marketing.home.open.heading')"
                        :lead="$t('marketing.home.open.body')"
                    />
                    <Button
                        as="a"
                        href="https://github.com/TheCuriousGoose/UptimeMonitor"
                        target="_blank"
                        rel="noopener noreferrer"
                        variant="outline"
                        class="mt-6"
                    >
                        <ExternalLinkIcon />
                        {{ $t('marketing.home.open.cta') }}
                    </Button>
                </div>

                <dl class="divide-y self-start border-y">
                    <div v-for="point in openPoints" :key="point" class="py-4">
                        <dt class="text-sm font-medium">
                            {{
                                $t(`marketing.home.open.points.${point}.title`)
                            }}
                        </dt>
                        <dd class="mt-1.5 text-sm text-muted-foreground">
                            {{ $t(`marketing.home.open.points.${point}.body`) }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section class="border-b">
        <div class="mx-auto w-full max-w-6xl px-4 py-20">
            <SectionHeading
                :eyebrow="$t('marketing.home.faq.eyebrow')"
                :heading="$t('marketing.home.faq.heading')"
            />

            <dl class="mt-10 grid gap-x-12 gap-y-8 md:grid-cols-2">
                <div v-for="item in faq" :key="item">
                    <dt class="font-medium">
                        {{ $t(`marketing.home.faq.items.${item}.question`) }}
                    </dt>
                    <dd class="mt-2 text-sm text-muted-foreground">
                        {{ $t(`marketing.home.faq.items.${item}.answer`) }}
                    </dd>
                </div>
            </dl>
        </div>
    </section>

    <!-- Closing -->
    <section>
        <div
            class="mx-auto flex w-full max-w-6xl flex-col items-start gap-6 px-4 py-16 md:flex-row md:items-center md:justify-between"
        >
            <div>
                <h2 class="text-2xl font-semibold tracking-tight">
                    {{ $t('marketing.home.closing.title') }}
                </h2>
                <p class="mt-2 text-muted-foreground">
                    {{ $t('marketing.home.closing.body') }}
                </p>
            </div>
            <Button :as="Link" :href="register()" size="lg" class="shrink-0">
                {{ $t('marketing.home.cta_primary') }}
                <ArrowRightIcon />
            </Button>
        </div>
    </section>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRightIcon,
    BellIcon,
    CheckIcon,
    ExternalLinkIcon,
    GlobeIcon,
    HashIcon,
    MailIcon,
    MessageSquareIcon,
    PhoneCallIcon,
    RadioIcon,
    ShieldCheckIcon,
    SirenIcon,
    TerminalIcon,
    UsersIcon,
    WebhookIcon,
} from 'lucide-vue-next';
import { computed } from 'vue';
import SectionHeading from '@/components/marketing/SectionHeading.vue';
import SeoHead from '@/components/SeoHead.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { t, tList } from '@/lib/i18n';
import { register } from '@/routes';
import docs from '@/routes/docs';

defineProps<{
    canRegister?: boolean;
}>();

const page = usePage();

/**
 * Organization markup so a search result carries the product name and link
 * rather than a bare URL. Serialised here rather than in Blade because the
 * name and description are translated strings the server view cannot reach.
 */
const organizationLd = computed(() =>
    JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'Organization',
        name: (page.props.name as string) ?? '',
        url: typeof window === 'undefined' ? '' : window.location.origin,
        description: t('marketing.home.subtitle'),
    }),
);

const stats = [
    { key: 'interval' },
    { key: 'types' },
    { key: 'channels' },
    { key: 'retention' },
];

const features = [
    { key: 'checks', icon: RadioIcon },
    { key: 'confirmation', icon: ShieldCheckIcon },
    { key: 'incidents', icon: SirenIcon },
    { key: 'alerts', icon: BellIcon },
    { key: 'status', icon: GlobeIcon },
    { key: 'api', icon: TerminalIcon },
];

/**
 * Sample rows for the hero panel. The `down` flag lives here rather than in
 * the translations so the styling has one source of truth — a translator
 * changing a label can't accidentally turn a row green.
 */
const previewRows = [
    { key: 'api', down: false },
    { key: 'web', down: false },
    { key: 'db', down: true },
    { key: 'cert', down: false },
];

const steps = ['add', 'tune', 'route'];

const checkTypes = ['http', 'keyword', 'port', 'ping', 'dns', 'ssl'];

const channels = [
    { key: 'email', icon: MailIcon },
    { key: 'slack', icon: HashIcon },
    { key: 'discord', icon: MessageSquareIcon },
    { key: 'teams', icon: UsersIcon },
    { key: 'pagerduty', icon: PhoneCallIcon },
    { key: 'opsgenie', icon: SirenIcon },
    { key: 'webhook', icon: WebhookIcon },
];

const alertPoints = ['dedupe', 'resolve'];

const openPoints = ['unlimited', 'data', 'source'];

const faq = ['agent', 'noise', 'scale', 'history', 'existing', 'cost'];

const statusPoints = computed(() =>
    tList('marketing.home.transparency.status.points'),
);

/**
 * Literal API syntax, deliberately not translated. It also must not go
 * through vue-i18n at all: the compiler reads `{` as the start of a
 * placeholder, and the `:placeholder` normalisation would turn `monitors:read`
 * into `monitors{read}` — which renders as the bare word "monitors".
 */
const apiSample = `curl -X POST https://vigil.example.com/api/v1/monitors \\
  -H "Authorization: Bearer $VIGIL_KEY" \\
  -H "Content-Type: application/json" \\
  -d '{
    "name": "Checkout",
    "type": "keyword",
    "url": "https://example.com/checkout",
    "interval_seconds": 60,
    "timeout": 10,
    "confirmation_threshold": 3,
    "config": { "keyword": "Place order" }
  }'`;

const abilities = [
    'monitors:read',
    'monitors:write',
    'incidents:read',
    'checks:trigger',
];

/**
 * A fixed 90-day pattern for the illustrative uptime bar. Hard-coded rather
 * than randomised so it renders identically on the server and the client.
 */
const uptimeDays = Array.from(
    { length: 90 },
    (_, index) => index !== 61 && index !== 62 && index !== 78,
);
</script>
