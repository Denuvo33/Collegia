<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { Bell, ChevronRight, Clock, MessageCircle } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Notification',
        href: '/user/notification',
    },
];

const props = defineProps<{
    notification: {
        data: Array<{
            id: number;
            message: string;
            post_id: number;
            created_at: string;
            hash_id: string;
            is_read: boolean;
            type?: string;
            event_title?: string;
            collab_hash_id?: string;
        }>;
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}>();

// Pagination state
const notifications = ref<Array<any>>([]);
const currentPage = ref(1);
const hasMoreNotifications = ref(true);
const loadingNotifications = ref(false);
const observer = ref<IntersectionObserver | null>(null);
const loadMoreTrigger = ref<HTMLElement | null>(null);

// Cache keys
const CACHE_KEY = 'user_notifications_cache';
const SCROLL_CACHE_KEY = 'notification_scroll_cache';

// Cache configuration
const CACHE_CONFIG = {
    MAX_NOTIFICATIONS: 50, // Increased for better caching
    CACHE_EXPIRY_MS: 5 * 60 * 1000, // 5 minutes
};

// Get cached data
const getCachedNotifications = () => {
    try {
        const cached = sessionStorage.getItem(CACHE_KEY);
        if (!cached) return null;

        const data = JSON.parse(cached);

        // Check expiry
        if (Date.now() - data.timestamp > CACHE_CONFIG.CACHE_EXPIRY_MS) {
            sessionStorage.removeItem(CACHE_KEY);
            sessionStorage.removeItem(SCROLL_CACHE_KEY);
            return null;
        }

        return data;
    } catch (e) {
        sessionStorage.removeItem(CACHE_KEY);
        return null;
    }
};

// Save to cache
const setCachedNotifications = (notifs: any[], page: number, lastPage: number, total: number) => {
    try {
        const limitedNotifications = notifs.slice(0, CACHE_CONFIG.MAX_NOTIFICATIONS);

        const data = {
            notifications: limitedNotifications,
            currentPage: page,
            lastPage: lastPage,
            total,
            timestamp: Date.now(),
        };

        sessionStorage.setItem(CACHE_KEY, JSON.stringify(data));
    } catch (e) {
        console.warn('Cache quota exceeded, clearing notification cache');
        sessionStorage.removeItem(CACHE_KEY);
    }
};

// Try to restore from cache
const cachedData = getCachedNotifications();

if (cachedData && cachedData.notifications && cachedData.notifications.length > 0) {
    // Use cached data
    notifications.value = cachedData.notifications;
    currentPage.value = cachedData.currentPage;
    hasMoreNotifications.value = cachedData.currentPage < cachedData.lastPage;
} else {
    // Use fresh props
    notifications.value = props.notification.data || [];
    currentPage.value = props.notification.current_page || 1;
    hasMoreNotifications.value = currentPage.value < (props.notification.last_page || 1);
}

// Load more notifications
const loadMoreNotifications = async () => {
    if (loadingNotifications.value || !hasMoreNotifications.value) return;

    loadingNotifications.value = true;
    try {
        const response = await fetch(`/user/notifications/paginate?page=${currentPage.value + 1}`);
        const data = await response.json();

        if (data.data && data.data.length > 0) {
            notifications.value = [...notifications.value, ...data.data];
            currentPage.value = data.current_page;
            hasMoreNotifications.value = currentPage.value < data.last_page;

            // Update cache
            setCachedNotifications(notifications.value, currentPage.value, data.last_page, data.total);
        } else {
            hasMoreNotifications.value = false;
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
    } finally {
        loadingNotifications.value = false;
    }
};

// Save scroll position
const saveScrollPosition = () => {
    sessionStorage.setItem(SCROLL_CACHE_KEY, String(window.scrollY));
};

// Restore scroll position
const restoreScrollPosition = () => {
    const savedScroll = sessionStorage.getItem(SCROLL_CACHE_KEY);
    if (savedScroll && cachedData) {
        requestAnimationFrame(() => {
            window.scrollTo(0, parseInt(savedScroll, 10));
        });
    }
};

// Setup intersection observer
onMounted(() => {
    observer.value = new IntersectionObserver(
        (entries) => {
            if (entries[0].isIntersecting && hasMoreNotifications.value && !loadingNotifications.value) {
                loadMoreNotifications();
            }
        },
        { threshold: 0.1 },
    );

    setTimeout(() => {
        if (loadMoreTrigger.value) {
            observer.value?.observe(loadMoreTrigger.value);
        }
    }, 100);

    // Cache initial data if not already cached
    if (!getCachedNotifications()) {
        setCachedNotifications(notifications.value, currentPage.value, props.notification.last_page || 1, props.notification.total || 0);
    }

    // Restore scroll position
    setTimeout(restoreScrollPosition, 100);
});

// Cleanup
onUnmounted(() => {
    if (observer.value) {
        observer.value.disconnect();
    }

    // Save scroll position and current state
    saveScrollPosition();
    setCachedNotifications(notifications.value, currentPage.value, props.notification.last_page || 1, props.notification.total || 0);
});

function splitName(text: string) {
    const parts = text.split(' commented');
    const name = parts[0].trim();
    const rest = 'commented' + (parts[1] ?? '');
    return { name, rest };
}

function formatTimeAgo(dateString: string) {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now.getTime() - date.getTime()) / 1000);

    if (diffInSeconds < 60) return 'Baru saja';
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} menit yang lalu`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} jam yang lalu`;
    if (diffInSeconds < 2592000) return `${Math.floor(diffInSeconds / 86400)} hari yang lalu`;

    return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

async function handleNotificationClick(notif: any) {
    console.log('Notification clicked:', { id: notif.id, type: notif.type, hash_id: notif.hash_id });

    // Mark as read menggunakan axios
    try {
        await axios.post(`/user/notifications/${notif.id}/mark-read`);

        notif.is_read = true;
    } catch (err) {
        console.error('Failed to mark as read:', err);
    }

    // Route based on type
    let routeName = 'user.post.details';
    let params: any = notif.hash_id;

    if (notif.type === 'event') {
        routeName = 'user.details.event';
        params = { hashId: notif.hash_id, title: notif.event_title };
    } else if (notif.type === 'collaboration') {
        routeName = 'user.collaboration.details';
    }

    router.visit(route(routeName, params), { preserveScroll: true });
}

const sortNotif = computed(() => {
    return notifications.value.sort((a, b) => {
        const dateA = new Date(a.created_at);
        const dateB = new Date(b.created_at);
        return dateB.getTime() - dateA.getTime();
    });
});
</script>

<template>
    <Head title="Notification" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen">
            <div class="mx-auto max-w-4xl px-4 py-8">
                <!-- Header Section -->
                <div class="mb-8">
                    <div class="mb-4 flex items-center gap-3">
                        <div class="rounded-xl p-3">
                            <Bell class="h-6 w-6 text-black" />
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold">Notifikasi</h1>
                            <p class="mt-1 text-gray-600">Kelola semua pemberitahuan Anda</p>
                        </div>
                    </div>
                    <div class="h-px bg-gradient-to-r from-gray-200 via-gray-300 to-gray-200"></div>
                </div>

                <!-- Empty State -->
                <div v-if="notifications.length === 0" class="flex flex-col items-center justify-center px-4 py-16">
                    <div class="mb-6 rounded-full bg-white p-6 shadow-lg dark:bg-gray-800">
                        <Bell class="h-12 w-12 text-black" />
                    </div>
                    <h3 class="mb-2 text-xl font-semibold text-gray-700">Belum ada notifikasi</h3>
                    <p class="max-w-md text-center text-gray-500">Anda akan menerima pemberitahuan ketika ada aktivitas baru pada postingan Anda</p>
                </div>

                <!-- Notifications List -->
                <div v-else class="space-y-3">
                    <div v-for="(notif, index) in sortNotif" :key="notif.id" class="group relative">
                        <!-- Notification Card -->
                        <div
                            :class="[
                                'cursor-pointer overflow-hidden rounded-xl border shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md',
                                notif.is_read
                                    ? 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800'
                                    : 'border-blue-300 bg-blue-50 hover:border-blue-400 dark:bg-blue-950/30',
                            ]"
                            @click="handleNotificationClick(notif)"
                        >
                            <!-- Card Content -->
                            <div class="p-6">
                                <div class="flex items-start gap-4">
                                    <!-- Icon -->
                                    <div class="mt-1 flex-shrink-0">
                                        <div class="rounded-lg border border-blue-100 bg-gradient-to-br from-blue-50 to-indigo-50 p-2.5">
                                            <MessageCircle class="h-5 w-5 text-black" />
                                        </div>
                                    </div>

                                    <!-- Content -->
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="flex-1">
                                                <h3 class="mb-1 text-lg font-semibold text-gray-900 transition-colors dark:text-white">
                                                    {{ splitName(notif.message).name }}
                                                </h3>
                                                <p class="mb-3 leading-relaxed text-gray-600">
                                                    {{ splitName(notif.message).rest }}
                                                </p>

                                                <!-- Timestamp -->
                                                <div class="flex items-center gap-2 text-sm text-gray-500">
                                                    <Clock class="h-4 w-4" />
                                                    <span>{{ formatTimeAgo(notif.created_at) }}</span>
                                                </div>
                                            </div>

                                            <!-- Arrow Icon -->
                                            <div class="mt-2 flex-shrink-0">
                                                <ChevronRight
                                                    class="h-5 w-5 text-gray-400 transition-all duration-200 group-hover:translate-x-1 group-hover:text-black"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Connecting line for multiple notifications -->
                        <div
                            v-if="index < sortNotif.length - 1"
                            class="absolute top-20 left-8 h-4 w-px bg-gradient-to-b from-gray-200 to-transparent"
                        ></div>
                    </div>

                    <!-- Load more trigger -->
                    <div v-if="hasMoreNotifications" ref="loadMoreTrigger" class="mt-6 text-center">
                        <div v-if="loadingNotifications" class="flex items-center justify-center gap-2 text-gray-500">
                            <div class="h-5 w-5 animate-spin rounded-full border-2 border-gray-300 border-t-indigo-600"></div>
                            <span>Memuat notifikasi...</span>
                        </div>
                    </div>
                </div>

                <!-- Footer spacing -->
                <div class="h-8"></div>
            </div>
        </div>
    </AppLayout>
</template>
