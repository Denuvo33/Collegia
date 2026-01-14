<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
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

// Cache configuration - smaller for notifications
const CACHE_CONFIG = {
    MAX_NOTIFICATIONS: 20,
    CACHE_EXPIRY_MS: 3 * 60 * 1000, // 3 minutes
};

// Cache functions
const CACHE_KEY = 'user_notifications';

const getCachedNotifications = () => {
    try {
        const cached = sessionStorage.getItem(CACHE_KEY);
        if (!cached) return null;

        const data = JSON.parse(cached);

        // Check expiry
        if (Date.now() - data.timestamp > CACHE_CONFIG.CACHE_EXPIRY_MS) {
            sessionStorage.removeItem(CACHE_KEY);
            return null;
        }

        return data;
    } catch (e) {
        sessionStorage.removeItem(CACHE_KEY);
        return null;
    }
};

const setCachedNotifications = (notifs: any[], currentPage: number, total: number) => {
    try {
        // Limit to prevent memory bloat
        const limitedNotifications = notifs.slice(0, CACHE_CONFIG.MAX_NOTIFICATIONS);

        const data = {
            notifications: limitedNotifications,
            currentPage: Math.min(currentPage, Math.ceil(CACHE_CONFIG.MAX_NOTIFICATIONS / 5)),
            total,
            timestamp: Date.now(),
        };

        sessionStorage.setItem(CACHE_KEY, JSON.stringify(data));
    } catch (e) {
        console.warn('Cache quota exceeded, clearing notification cache');
        sessionStorage.removeItem(CACHE_KEY);
    }
};

const invalidateCache = () => {
    sessionStorage.removeItem(CACHE_KEY);
};

// Initialize from cache or props
const cached = getCachedNotifications();
if (cached && cached.notifications.length > 0) {
    notifications.value = cached.notifications;
    currentPage.value = cached.currentPage;
    hasMoreNotifications.value = currentPage.value < Math.ceil(cached.total / 5);
} else {
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
            setCachedNotifications(notifications.value, currentPage.value, data.total);
        } else {
            hasMoreNotifications.value = false;
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
    } finally {
        loadingNotifications.value = false;
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
        setCachedNotifications(notifications.value, currentPage.value, props.notification.total || 0);
    }
});

// Cleanup
onUnmounted(() => {
    if (observer.value) {
        observer.value.disconnect();
    }

    // Save current state
    setCachedNotifications(notifications.value, currentPage.value, props.notification.total || 0);
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
                        <div class="rounded-xl bg-indigo-100 p-3">
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
                    <div class="mb-6 rounded-full bg-white p-6 shadow-lg">
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
                            class="cursor-pointer overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
                            @click="$inertia.visit(route('user.post.details', notif.hash_id))"
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
                                                <h3
                                                    class="mb-1 text-lg font-semibold text-gray-900 transition-colors group-hover:text-indigo-700 dark:text-white"
                                                >
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

                            <!-- Subtle border accent -->
                            <div
                                class="h-1 bg-gradient-to-r from-black to-blue-600 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                            ></div>
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
