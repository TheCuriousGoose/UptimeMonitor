<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { trans } from '@/lib/i18n';
import { toUrl } from '@/lib/utils';
import { index as editApiTokens } from '@/routes/api-tokens';
import { edit as editAppearance } from '@/routes/appearance';
import { index as editDomains } from '@/routes/domains';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: editProfile(),
    },
    {
        title: 'Security',
        href: editSecurity(),
    },
    {
        title: 'Appearance',
        href: editAppearance(),
    },
    {
        title: trans('settings.domains.breadcrumb'),
        href: editDomains(),
    },
    {
        title: trans('api_tokens.heading'),
        href: editApiTokens(),
    },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="px-4 py-6">
        <Heading
            title="Settings"
            description="Manage your profile and account settings"
        />

        <div class="flex flex-col lg:flex-row lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-48">
                <!-- Same left-accent-bar language as the main rail, so
                     "where am I" reads the same way everywhere. -->
                <nav class="flex flex-col border-l" aria-label="Settings">
                    <Link
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        :href="item.href"
                        class="-ml-px flex items-center gap-2 border-l-2 py-1.5 pl-3 text-sm transition-colors hover:text-foreground"
                        :class="
                            isCurrentOrParentUrl(item.href)
                                ? 'border-primary font-medium text-foreground'
                                : 'border-transparent text-muted-foreground'
                        "
                    >
                        <component :is="item.icon" class="h-4 w-4" />
                        {{ item.title }}
                    </Link>
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="flex-1 md:max-w-2xl">
                <section class="max-w-xl space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
