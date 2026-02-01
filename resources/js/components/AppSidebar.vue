<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Bell, CalendarClock, Home, Telescope } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

const unreadCount = ref(0);

// Fetch unread count
const fetchUnreadCount = async () => {
    try {
        const response = await fetch('/user/notifications/unread-count');
        const data = await response.json();
        unreadCount.value = data.count;
        console.log(unreadCount.value);
    } catch (error) {
        console.error('Failed to fetch unread count:', error);
    }
};

onMounted(() => {
    fetchUnreadCount();
});

const mainNavItems: NavItem[] = [
    {
        title: 'Feed',
        href: '/user/home',
        icon: Home,
    },
    {
        title: 'Event',
        href: '/user/event',
        icon: CalendarClock,
    },
    {
        title: 'People / Community',
        href: '/user/explore',
        icon: Telescope,
    },
    {
        title: 'Notifikasi',
        href: '/user/notifications',
        icon: Bell,
        badge: unreadCount.value,
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
