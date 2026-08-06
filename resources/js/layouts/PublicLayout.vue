<template>
    <SkipLink />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header
            class="sticky top-0 z-30 border-b bg-background/85 backdrop-blur-sm"
        >
            <div
                class="mx-auto flex h-14 w-full max-w-6xl items-center gap-6 px-4"
            >
                <Link :href="home()" class="flex shrink-0 items-center gap-2.5">
                    <span
                        class="flex size-7 items-center justify-center rounded-sm border border-primary/40 bg-primary/10 text-primary"
                    >
                        <AppLogoIcon class="size-4" />
                    </span>
                    <span class="text-sm font-semibold tracking-tight">{{
                        appName
                    }}</span>
                </Link>

                <nav class="hidden items-center gap-5 md:flex">
                    <Link
                        v-for="item in nav"
                        :key="item.href"
                        :href="item.href"
                        class="text-sm transition-colors"
                        :class="
                            isCurrent(item.href)
                                ? 'font-medium text-foreground'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                    >
                        {{ item.label }}
                    </Link>
                </nav>

                <div class="ml-auto flex items-center gap-2">
                    <Button
                        :as="Link"
                        :href="login()"
                        variant="ghost"
                        size="sm"
                        class="hidden sm:inline-flex"
                    >
                        {{ $t('marketing.nav.login') }}
                    </Button>
                    <Button :as="Link" :href="register()" size="sm">
                        {{ $t('marketing.nav.register') }}
                    </Button>

                    <!-- Without this the nav simply vanishes below md and the
                         public site is unnavigable on a phone. -->
                    <Sheet v-model:open="mobileNavOpen">
                        <SheetTrigger as-child>
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                class="md:hidden"
                            >
                                <MenuIcon />
                                <span class="sr-only">{{
                                    $t('marketing.nav.menu')
                                }}</span>
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="right" class="w-72">
                            <SheetHeader>
                                <SheetTitle>{{
                                    $t('marketing.nav.menu')
                                }}</SheetTitle>
                            </SheetHeader>
                            <nav class="flex flex-col gap-1 px-4 pb-4">
                                <Link
                                    v-for="item in nav"
                                    :key="item.href"
                                    :href="item.href"
                                    class="rounded-sm px-2 py-2 text-sm transition-colors"
                                    :class="
                                        isCurrent(item.href)
                                            ? 'bg-accent font-medium text-accent-foreground'
                                            : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
                                    "
                                    @click="mobileNavOpen = false"
                                >
                                    {{ item.label }}
                                </Link>
                                <Link
                                    :href="login()"
                                    class="rounded-sm px-2 py-2 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground sm:hidden"
                                    @click="mobileNavOpen = false"
                                >
                                    {{ $t('marketing.nav.login') }}
                                </Link>
                            </nav>
                        </SheetContent>
                    </Sheet>
                </div>
            </div>
        </header>

        <main id="main-content" tabindex="-1" class="flex-1">
            <slot />
        </main>

        <footer class="border-t">
            <div
                class="mx-auto flex w-full max-w-6xl flex-col gap-6 px-4 py-10 md:flex-row md:justify-between"
            >
                <div class="max-w-xs">
                    <p
                        class="font-mono text-[10px] tracking-[0.12em] text-muted-foreground uppercase"
                    >
                        {{ appName }}
                    </p>
                    <p class="mt-2 text-sm text-muted-foreground">
                        {{ $t('marketing.footer.tagline') }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-8 sm:grid-cols-3">
                    <div v-for="group in footerGroups" :key="group.heading">
                        <p
                            class="font-mono text-[10px] font-semibold tracking-[0.12em] text-muted-foreground uppercase"
                        >
                            {{ group.heading }}
                        </p>
                        <ul class="mt-3 space-y-2">
                            <li v-for="item in group.items" :key="item.href">
                                <Link
                                    :href="item.href"
                                    class="text-sm text-muted-foreground transition-colors hover:text-foreground"
                                >
                                    {{ item.label }}
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="border-t">
                <div
                    class="mx-auto w-full max-w-6xl px-4 py-4 text-xs text-muted-foreground"
                >
                    &copy; {{ year }} {{ appName }}
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { MenuIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import SkipLink from '@/components/SkipLink.vue';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { trans } from '@/lib/i18n';
import {
    about,
    contact,
    features,
    home,
    login,
    privacy,
    register,
    roadmap,
    terms,
} from '@/routes';
import blog from '@/routes/blog';
import changelog from '@/routes/changelog';
import docs from '@/routes/docs';

const page = usePage();
const appName = computed(() => (page.props.name as string) ?? 'Vigil Watch');
const year = new Date().getFullYear();
const mobileNavOpen = ref(false);

const nav = [
    { label: trans('marketing.nav.features'), href: features().url },
    { label: trans('marketing.nav.docs'), href: docs.index().url },
    { label: trans('marketing.nav.changelog'), href: changelog.index().url },
    { label: trans('marketing.nav.blog'), href: blog.index().url },
];

const footerGroups = [
    {
        heading: trans('marketing.footer.product'),
        items: [
            { label: trans('marketing.nav.features'), href: features().url },
            { label: trans('marketing.nav.roadmap'), href: roadmap().url },
            {
                label: trans('marketing.nav.changelog'),
                href: changelog.index().url,
            },
        ],
    },
    {
        heading: trans('marketing.footer.resources'),
        items: [
            { label: trans('marketing.nav.docs'), href: docs.index().url },
            { label: trans('marketing.nav.blog'), href: blog.index().url },
        ],
    },
    {
        heading: trans('marketing.footer.company'),
        items: [
            { label: trans('marketing.nav.about'), href: about().url },
            { label: trans('marketing.nav.contact'), href: contact().url },
            { label: trans('marketing.nav.privacy'), href: privacy().url },
            { label: trans('marketing.nav.terms'), href: terms().url },
        ],
    },
];

function isCurrent(href: string): boolean {
    return page.url.split('?')[0] === href;
}
</script>
