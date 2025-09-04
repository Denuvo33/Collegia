<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Bell, CalendarClock, Home, Telescope, User } from 'lucide-vue-next';

const mainNavItems: NavItem[] = [
    {
        title: 'Beranda',
        href: '/user/home',
        icon: Home,
    },
    {
        title: 'Jelajah',
        href: '/user/explore',
        icon: Telescope,
    },
    {
        title: 'Notifikasi',
        href: '/user/notifications',
        icon: Bell,
    },
    {
        title: 'Profile',
        href: '/user/profile',
        icon: User,
    },
    {
        title: 'Event',
        href: '/user/event',
        icon: CalendarClock,
    },
];

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem> </SidebarMenuItem>
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
