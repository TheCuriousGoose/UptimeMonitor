<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Bell,
    Coffee,
    FileText,
    Globe,
    LayoutGrid,
    Monitor,
    Settings2,
    ShieldCheck,
    Siren,
    Users,
    Wrench,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { trans } from '@/lib/i18n';
import { dashboard } from '@/routes';
import adminContent from '@/routes/admin/content';
import adminRoles from '@/routes/admin/roles';
import adminSettings from '@/routes/admin/settings';
import adminUsers from '@/routes/admin/users';
import incidents from '@/routes/incidents';
import integrations from '@/routes/integrations';
import maintenanceWindows from '@/routes/maintenance-windows';
import monitors from '@/routes/monitors';
import statusPages from '@/routes/status-pages';
import type { Auth } from '@/types';
import type { NavItem } from '@/types';

const page = usePage();
const auth = computed(() => page.props.auth as Auth);
const isSuperAdmin = computed(
    () => auth.value.roles?.includes('Super Admin') ?? false,
);

const mainNavItems: NavItem[] = [
    {
        title: trans('dashboards.title'),
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: trans('monitors.title'),
        href: monitors.index(),
        icon: Monitor,
    },
    {
        title: trans('incidents.title'),
        href: incidents.index(),
        icon: Siren,
    },
    {
        title: trans('integrations.title'),
        href: integrations.index(),
        icon: Bell,
    },
    {
        title: trans('status_pages.title'),
        href: statusPages.index(),
        icon: Globe,
    },
    {
        title: trans('maintenance.title'),
        href: maintenanceWindows.index(),
        icon: Wrench,
    },
];

const adminNavItems: NavItem[] = [
    {
        title: 'Users',
        href: adminUsers.index(),
        icon: Users,
    },
    {
        title: 'Roles',
        href: adminRoles.index(),
        icon: ShieldCheck,
    },
    {
        title: trans('content.heading'),
        href: adminContent.index(),
        icon: FileText,
    },
    {
        title: 'Settings',
        href: adminSettings.index(),
        icon: Settings2,
    },
];

// Empty when the instance is self-hosted, which collapses the group rather
// than asking those users to fund someone else's hosting.
const footerNavItems = computed<NavItem[]>(() => {
    const supportUrl = page.props.supportUrl as string | null;

    return supportUrl
        ? [
              {
                  title: trans('marketing.support.nav'),
                  href: supportUrl,
                  icon: Coffee,
              },
          ]
        : [];
});
</script>

<template>
    <Sidebar collapsible="icon" variant="sidebar">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
            <NavMain v-if="isSuperAdmin" :items="adminNavItems" label="Admin" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
