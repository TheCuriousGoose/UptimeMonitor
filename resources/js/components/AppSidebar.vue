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
import { usePermissions } from '@/composables/usePermissions';
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
import type { NavItem } from '@/types';

const page = usePage();
const { canAny } = usePermissions();

const visible = (items: NavItem[]) =>
    items.filter((item) => !item.permission || canAny(item.permission));

const allMainNavItems: NavItem[] = [
    {
        title: trans('dashboards.title'),
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: trans('monitors.title'),
        href: monitors.index(),
        icon: Monitor,
        permission: 'monitors.view',
    },
    {
        title: trans('incidents.title'),
        href: incidents.index(),
        icon: Siren,
        permission: 'incidents.view',
    },
    {
        title: trans('integrations.title'),
        href: integrations.index(),
        icon: Bell,
        permission: 'channels.view',
    },
    {
        title: trans('status_pages.title'),
        href: statusPages.index(),
        icon: Globe,
        permission: 'status_pages.view',
    },
    {
        title: trans('maintenance.title'),
        href: maintenanceWindows.index(),
        icon: Wrench,
        permission: 'maintenance.view',
    },
];

const mainNavItems = computed(() => visible(allMainNavItems));

const allAdminNavItems: NavItem[] = [
    {
        title: trans('admin.users.title'),
        href: adminUsers.index(),
        icon: Users,
        permission: 'users.view',
    },
    {
        title: trans('admin.roles.title'),
        href: adminRoles.index(),
        icon: ShieldCheck,
        permission: 'roles.view',
    },
    {
        title: trans('content.heading'),
        href: adminContent.index(),
        icon: FileText,
        permission: 'content.view',
    },
    {
        title: trans('settings.title'),
        href: adminSettings.index(),
        icon: Settings2,
        permission: 'settings.view',
    },
];

const adminNavItems = computed(() => visible(allAdminNavItems));

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
            <NavMain
                v-if="adminNavItems.length"
                :items="adminNavItems"
                label="Admin"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
