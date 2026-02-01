<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Bell, CalendarClock, Home, LogOut, Settings, Telescope, UserIcon, Wallet } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user as User);
const unreadCount = ref(0);
const { getInitials } = useInitials();

// Fetch unread count
const fetchUnreadCount = async () => {
    try {
        const response = await fetch('/user/notifications/unread-count');
        const data = await response.json();
        unreadCount.value = data.count;
    } catch (error) {
        console.error('Failed to fetch unread count:', error);
    }
};

onMounted(() => {
    fetchUnreadCount();
});

// Navigation items (without Profile - will be separate dropdown)
const navItems = [
    { title: 'Feed', href: '/user/home', icon: Home },
    { title: 'Event', href: '/user/event', icon: CalendarClock },
    { title: 'Explore', href: '/user/explore', icon: Telescope },
    { title: 'Notifikasi', href: '/user/notifications', icon: Bell, hasBadge: true },
];

const isActive = (href: string) => {
    const currentPath = page.url;
    if (href === '/user/home') {
        return currentPath === '/user/home' || currentPath === '/user';
    }
    return currentPath.startsWith(href);
};

const navigate = (href: string) => {
    router.visit(href);
};

const handleLogout = () => {
    router.flushAll();
};

// Check if user avatar exists
const showAvatar = computed(() => user.value?.avatar && user.value.avatar !== '');
</script>

<template>
    <nav class="safe-area-bottom fixed right-0 bottom-0 left-0 z-50 border-t border-border/50 bg-background/95 backdrop-blur-lg md:hidden">
        <div class="flex h-16 items-center justify-around px-2">
            <!-- Regular nav items -->
            <button
                v-for="item in navItems"
                :key="item.href"
                @click="navigate(item.href)"
                class="relative mx-0.5 flex h-full flex-1 flex-col items-center justify-center gap-0.5 rounded-lg transition-all duration-200"
                :class="[isActive(item.href) ? 'text-primary' : 'text-muted-foreground hover:text-foreground']"
            >
                <!-- Icon container with background on active -->
                <div
                    class="relative flex h-7 w-10 items-center justify-center rounded-full transition-all duration-200"
                    :class="isActive(item.href) ? 'bg-primary/10' : ''"
                >
                    <component :is="item.icon" class="h-5 w-5" />

                    <!-- Notification Badge -->
                    <Badge
                        v-if="item.hasBadge && unreadCount > 0"
                        variant="destructive"
                        class="absolute -top-1 -right-1 flex h-4 min-w-4 items-center justify-center px-1 text-[10px] font-semibold"
                    >
                        {{ unreadCount > 99 ? '99+' : unreadCount }}
                    </Badge>
                </div>

                <!-- Label -->
                <span class="text-[10px] font-medium transition-all duration-200" :class="isActive(item.href) ? 'font-semibold' : ''">
                    {{ item.title }}
                </span>

                <!-- Active indicator line -->
                <div v-if="isActive(item.href)" class="absolute top-0 left-1/2 h-0.5 w-8 -translate-x-1/2 rounded-full bg-primary" />
            </button>

            <!-- User Profile Dropdown -->
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <button
                        class="relative mx-0.5 flex h-full flex-1 flex-col items-center justify-center gap-0.5 rounded-lg text-muted-foreground transition-all duration-200 hover:text-foreground focus:outline-none"
                    >
                        <div class="relative flex h-7 w-10 items-center justify-center">
                            <Avatar class="h-6 w-6 overflow-hidden rounded-full ring-2 ring-border/50">
                                <AvatarImage v-if="showAvatar" :src="user.avatar!" :alt="user.name" />
                                <AvatarFallback class="rounded-full text-[10px] font-medium">
                                    {{ getInitials(user.name) }}
                                </AvatarFallback>
                            </Avatar>
                        </div>
                        <span class="text-[10px] font-medium">Akun</span>
                    </button>
                </DropdownMenuTrigger>

                <DropdownMenuContent class="mb-2 w-64 rounded-xl" side="top" align="end" :side-offset="8">
                    <!-- User Info Header -->
                    <DropdownMenuLabel class="p-0 font-normal">
                        <div class="flex items-center gap-3 px-3 py-3">
                            <Avatar class="h-10 w-10 overflow-hidden rounded-lg">
                                <AvatarImage v-if="showAvatar" :src="user.avatar!" :alt="user.name" />
                                <AvatarFallback class="rounded-lg text-sm font-medium">
                                    {{ getInitials(user.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="grid flex-1 text-left leading-tight">
                                <span class="truncate font-semibold">{{ user.name }}</span>
                                <span class="truncate text-xs text-muted-foreground">{{ user.email }}</span>
                            </div>
                        </div>
                    </DropdownMenuLabel>

                    <DropdownMenuSeparator />

                    <!-- Menu Items -->
                    <DropdownMenuGroup>
                        <DropdownMenuItem :as-child="true">
                            <Link class="flex w-full items-center gap-3 px-3 py-2.5" :href="route('user.profile')" as="button">
                                <UserIcon class="h-4 w-4" />
                                <span>Profile</span>
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem :as-child="true">
                            <Link class="flex w-full items-center gap-3 px-3 py-2.5" :href="route('user.transaction')" as="button">
                                <Wallet class="h-4 w-4" />
                                <span>Transaksi</span>
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem :as-child="true">
                            <Link class="flex w-full items-center gap-3 px-3 py-2.5" :href="route('appearance')" as="button">
                                <Settings class="h-4 w-4" />
                                <span>Settings</span>
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuGroup>

                    <DropdownMenuSeparator />

                    <!-- Logout -->
                    <DropdownMenuItem :as-child="true">
                        <Link
                            class="flex w-full items-center gap-3 px-3 py-2.5 text-destructive hover:text-destructive"
                            method="post"
                            :href="route('logout')"
                            @click="handleLogout"
                            as="button"
                        >
                            <LogOut class="h-4 w-4" />
                            <span>Log out</span>
                        </Link>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </nav>
</template>

<style scoped>
/* Safe area for devices with notch/home indicator */
.safe-area-bottom {
    padding-bottom: env(safe-area-inset-bottom, 0px);
}
</style>
