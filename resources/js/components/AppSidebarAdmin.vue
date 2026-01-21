<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Bell, CalendarClock, Home, Telescope, User, Users } from 'lucide-vue-next';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/admin/dashboard',
        icon: Home,
    },
    {
        title: 'User Dashboard',
        href: '/admin/user-dashboard',
        icon: User,
    },
    {
        title: 'Event',
        href: '/admin/event',
        icon: CalendarClock,
    },
    {
        title: 'Report Post',
        href: '/admin/report-post',
        icon: Telescope,
    },
    {
        title: 'Report Event',
        href: '/admin/report-event',
        icon: Bell,
    },
    {
        title: 'Report Comment',
        href: '/admin/report-comment',
        icon: User,
    },
    {
        title: 'Report Collaboration',
        href: '/admin/report-collaboration',
        icon: Users,
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
