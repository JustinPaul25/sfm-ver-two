<script setup lang="ts">
// import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
// import { BookOpen, Folder, LayoutGrid } from 'lucide-vue-next';
import { LayoutGrid, Users, File, List, BarChart3, Clock, UserCog, Settings, LineChart } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { computed } from 'vue';

const page = usePage<SharedData>();
const userRole = computed(() => page.props.auth?.user?.role || 'farmer');

// Menu items for farmers
const farmerNavItems: NavItem[] = [
    {
        title: 'Samplings',
        href: '/samplings',
        icon: File,
    },
    {
        title: 'Feed Types',
        href: '/feed-types',
        icon: List,
    },
    {
        title: 'Cages',
        href: '/cages',
        icon: List,
    },
    {
        title: 'Feeding Schedules',
        href: '/cages/feeding-schedules',
        icon: Clock,
    },
    {
        title: 'Feeding Reports',
        href: '/reports/feeding',
        icon: BarChart3,
    },
];

// Menu items for investors (view-only)
const investorNavItems: NavItem[] = [
    {
        title: 'My Dashboard',
        href: '/investor/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'My Cages',
        href: '/cages',
        icon: List,
    },
    {
        title: 'Samplings',
        href: '/samplings',
        icon: File,
    },
    {
        title: 'Cage Verification',
        href: '/cages/verification',
        icon: BarChart3,
    },
    {
        title: 'Reports',
        href: '/reports/overall',
        icon: BarChart3,
    },
];

// Menu items for admin (all items)
const adminNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'User Management',
        href: '/users',
        icon: UserCog,
    },
    {
        title: 'System Settings',
        href: '/settings/system',
        icon: Settings,
    },
    {
        title: 'Forecasting Simulation',
        href: '/forecasting/simulation',
        icon: LineChart,
    },
    {
        title: 'Investors',
        href: '/investors',
        icon: Users,
    },
    {
        title: 'Samplings',
        href: '/samplings',
        icon: File,
    },
    {
        title: 'Feed Types',
        href: '/feed-types',
        icon: List,
    },
    {
        title: 'Cages',
        href: '/cages',
        icon: List,
    },
    {
        title: 'Feeding Schedules',
        href: '/cages/feeding-schedules',
        icon: Clock,
    },
    {
        title: 'Sampling Reports',
        href: '/reports/overall',
        icon: BarChart3,
    },
    {
        title: 'Feeding Reports',
        href: '/reports/feeding',
        icon: BarChart3,
    },
];

const mainNavItems = computed(() => {
    if (userRole.value === 'investor') {
        return investorNavItems;
    } else if (userRole.value === 'admin') {
        return adminNavItems;
    } else {
        return farmerNavItems;
    }
});

// const footerNavItems: NavItem[] = [
//     {
//         title: 'Github Repo',
//         href: 'https://github.com/laravel/vue-starter-kit',
//         icon: Folder,
//     },
//     {
//         title: 'Documentation',
//         href: 'https://laravel.com/docs/starter-kits#vue',
//         icon: BookOpen,
//     },
// ];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <!-- <NavFooter :items="footerNavItems" /> -->
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
