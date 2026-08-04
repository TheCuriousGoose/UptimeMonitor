<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Bell,
    Globe,
    LayoutGrid,
    Monitor,
    Settings2,
    ShieldCheck,
    Users,
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
import adminRoles from '@/routes/admin/roles';
import adminSettings from '@/routes/admin/settings';
import adminUsers from '@/routes/admin/users';
import channels from '@/routes/channels';
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
        title: trans('channels.title'),
        href: channels.index(),
        icon: Bell,
    },
    {
        title: trans('status_pages.title'),
        href: statusPages.index(),
        icon: Globe,
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
        title: 'Settings',
        href: adminSettings.index(),
        icon: Settings2,
    },
];

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
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
