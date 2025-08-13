<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Home,Telescope,Bell,User,Megaphone, LayoutGrid } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';


const mainNavItems: NavItem[] = [
    {
        title: 'Home',
        href: '/user/home',
        icon: Home,
    },
    {
        title: 'Explore',
        href: '/user/explore',
        icon: Telescope,
    },
    {
        title: 'Notifications',
        href: '/user/notifications',
        icon: Bell,
    },
    {
        title: 'Profile',
        href: '/user/profile',
        icon: User,
    },
    {
        title: 'Create Post',
        href: '/user/create-post',
        icon: Megaphone,
    },

];

const footerNavItems: NavItem[] = [
    
];
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
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
