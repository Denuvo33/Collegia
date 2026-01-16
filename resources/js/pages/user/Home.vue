<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Toaster } from '@/components/ui/sonner';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import relativeTime from 'dayjs/plugin/relativeTime';
import { Calendar, Clock, EllipsisVertical, HandHeart, MapPin, MessageSquare, Pin, Plus, Rat, Share2, Users, Workflow } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import 'vue-sonner/style.css';

dayjs.extend(relativeTime);
dayjs.locale('id');

const page = usePage();
const reportValue = ref('spam');

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Home', href: '/user/home' }];

type User = {
    id: number;
    name: string;
    email: string;
    role: string;
    hash_id: string;
    clean_name: string;
};

type FeedItem = {
    id: number;
    type: 'post' | 'event' | 'collaboration' | 'volunteer';
    title?: string;
    content?: string;
    description?: string;
    image?: string;
    hash_id: string;
    created_at: string;
    user: User;
    location?: string;
    date_event?: string;
    price?: number;
    organizer?: string;
    participants_needed?: number;
    interests_count?: number;
};

type PaginatedFeed = {
    data: FeedItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    next_page_url: string | null;
};

const props = defineProps<{
    pinnedEvents?: FeedItem[];
    feed?: PaginatedFeed;
}>();

// Filter configuration
const FILTER_CACHE_KEY = 'home_filter_cache';
const filters = ['all', 'event', 'collaboration', 'volunteer', 'post'] as const;
type FilterType = (typeof filters)[number];

// Restore filter from cache
const savedFilter = sessionStorage.getItem(FILTER_CACHE_KEY) as FilterType | null;
const activeFilter = ref<FilterType>(savedFilter || 'all');

// Save filter to cache when changed
watch(activeFilter, (newFilter) => {
    sessionStorage.setItem(FILTER_CACHE_KEY, newFilter);
});

const feed = ref<FeedItem[]>(props.feed?.data || []);
const pinnedEvents = ref<FeedItem[]>(props.pinnedEvents || []);
const currentPage = ref(props.feed?.current_page || 1);
const lastPage = ref(props.feed?.last_page || 1);
const isLoadingMore = ref(false);

const filteredFeed = computed(() => {
    if (activeFilter.value === 'all') {
        return feed.value;
    }
    return feed.value.filter((item) => item.type === activeFilter.value);
});

async function loadMore() {
    if (isLoadingMore.value || currentPage.value >= lastPage.value) return;

    isLoadingMore.value = true;
    try {
        const response = await fetch(`/user/home?page=${currentPage.value + 1}`, {
            headers: { Accept: 'application/json' },
        });
        const data = await response.json();
        feed.value = [...feed.value, ...data.data];
        currentPage.value = data.current_page;
        lastPage.value = data.last_page;
    } catch (error) {
        console.error('Failed to load more:', error);
    } finally {
        isLoadingMore.value = false;
    }
}

// Infinite scroll handler
function handleScroll() {
    const scrollTop = window.scrollY;
    const windowHeight = window.innerHeight;
    const documentHeight = document.documentElement.scrollHeight;

    if (scrollTop + windowHeight >= documentHeight - 200) {
        loadMore();
    }
}

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', handleScroll);
});

function formatDate(date: string) {
    return dayjs(date).fromNow();
}

function formatEventDate(date: string) {
    return dayjs(date).format('DD MMM YYYY, HH:mm');
}

function navigateToItem(item: FeedItem) {
    if (item.type === 'post') {
        router.visit(`/user/post/${item.hash_id}`);
    } else if (item.type === 'event') {
        const slug = item.title?.toLowerCase().split(' ').join('-') || 'event';
        router.visit(`/user/event/${item.hash_id}/${slug}`);
    } else {
        router.visit(`/user/collaboration/${item.hash_id}`);
    }
}

function copyUrl(item: FeedItem) {
    let url = '';
    if (item.type === 'post') {
        url = `${window.location.origin}/user/post/${item.hash_id}`;
    } else if (item.type === 'event') {
        const slug = item.title?.toLowerCase().split(' ').join('-') || 'event';
        url = `${window.location.origin}/user/event/${item.hash_id}/${slug}`;
    } else {
        url = `${window.location.origin}/user/collaboration/${item.hash_id}`;
    }
    navigator.clipboard.writeText(url).then(() => {
        toast.success('Link disalin!', { duration: 3000 });
    });
}

function reportPost(postId: number) {
    router.post(
        route('user.post.report'),
        {
            post_id: postId,
            reason: reportValue.value,
        },
        {
            preserveScroll: true,
            onError: () => toast.error('Gagal mengirim laporan.'),
        },
    );
}

function deletePost(id: number) {
    router.delete(route('user.post.delete', id), {
        preserveScroll: true,
        onError: () => toast.error('Gagal menghapus.'),
    });
}

watch(
    () => page.props.flash,
    (flash: any) => {
        if (flash?.success) {
            toast.success(flash.success, { duration: 5000 });
        }
        if (flash?.error) {
            toast.error(flash.error, { duration: 5000 });
        }
    },
    { deep: true, immediate: true },
);

function getFilterLabel(filter: FilterType) {
    const labels: Record<FilterType, string> = {
        all: 'Semua',
        event: 'Event',
        collaboration: 'Kolaborasi',
        volunteer: 'Relawan',
        post: 'Cerita',
    };
    return labels[filter];
}

function getTypeStyles(type: string) {
    const styles: Record<string, { border: string; gradient: string; icon: string }> = {
        event: {
            border: 'border-l-blue-500',
            gradient: 'bg-gradient-to-r from-blue-50/30 to-transparent dark:from-blue-950/20',
            icon: 'Calendar',
        },
        collaboration: {
            border: 'border-l-green-500',
            gradient: 'bg-gradient-to-r from-green-50/30 to-transparent dark:from-green-950/20',
            icon: 'Workflow',
        },
        volunteer: {
            border: 'border-l-purple-500',
            gradient: 'bg-gradient-to-r from-purple-50/30 to-transparent dark:from-purple-950/20',
            icon: 'HandHeart',
        },
        post: {
            border: 'border-l-orange-500',
            gradient: 'bg-gradient-to-r from-orange-50/30 to-transparent dark:from-orange-950/20',
            icon: 'MessageSquare',
        },
    };
    return styles[type] || styles.post;
}

function getTypeLabel(type: string) {
    const labels: Record<string, string> = {
        event: 'Event',
        collaboration: 'Kolaborasi',
        volunteer: 'Relawan',
        post: 'Cerita',
    };
    return labels[type] || type;
}
</script>

<template>
    <Head title="Home" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster />
        <div class="space-y-4 p-8">
            <!-- Posting Button -->
            <div class="flex items-center justify-between">
                <AlertDialog>
                    <AlertDialogTrigger as-child>
                        <Button class="text-lg">Posting</Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent class="max-w-md">
                        <AlertDialogHeader>
                            <AlertDialogTitle class="text-xl">Pilih Jenis Postingan</AlertDialogTitle>
                        </AlertDialogHeader>

                        <div class="grid gap-3 py-4">
                            <Card
                                class="cursor-pointer rounded border-2 p-2 transition-all hover:border-blue-500 hover:shadow-lg"
                                @click="$inertia.visit(route('user.create-event'))"
                            >
                                <CardContent class="p-4">
                                    <div class="flex items-center gap-4">
                                        <Calendar class="h-8 w-8 text-blue-600 dark:text-blue-400" />
                                        <div>
                                            <h3 class="text-lg font-semibold">Event</h3>
                                            <p class="text-sm text-gray-500">Buat acara kampus/komunitas</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card
                                class="cursor-pointer rounded border-2 p-2 transition-all hover:border-green-500 hover:shadow-lg"
                                @click="$inertia.visit(route('user.create-collaboration'))"
                            >
                                <CardContent class="p-4">
                                    <div class="flex items-center gap-4">
                                        <Workflow class="h-8 w-8 text-green-600 dark:text-green-400" />
                                        <div>
                                            <h3 class="text-lg font-semibold">Kolaborasi / Relawan</h3>
                                            <p class="text-sm text-gray-500">Cari rekan atau volunteer</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card
                                class="cursor-pointer rounded border-2 p-2 transition-all hover:border-purple-500 hover:shadow-lg"
                                @click="$inertia.visit(route('user.create-post'))"
                            >
                                <CardContent class="p-4">
                                    <div class="flex items-center gap-4">
                                        <Rat class="h-8 w-8 text-purple-600 dark:text-purple-400" />
                                        <div>
                                            <h3 class="text-lg font-semibold">Cerita</h3>
                                            <p class="text-sm text-gray-500">Info terkait kampus/komunitas</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <AlertDialogFooter>
                            <AlertDialogCancel>Batal</AlertDialogCancel>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            </div>

            <!-- Filter Tabs -->
            <div
                class="sticky top-0 z-40 -mx-8 bg-slate-50/95 px-8 pt-4 pb-2 backdrop-blur supports-[backdrop-filter]:bg-slate-50/60 dark:bg-slate-950/95 dark:supports-[backdrop-filter]:bg-slate-950/60"
            >
                <div class="flex gap-2 overflow-x-auto pb-2">
                    <Button
                        v-for="filter in filters"
                        :key="filter"
                        :variant="activeFilter === filter ? 'default' : 'outline'"
                        size="sm"
                        @click="activeFilter = filter"
                        class="shrink-0"
                    >
                        {{ getFilterLabel(filter) }}
                    </Button>
                </div>
            </div>

            <!-- Pinned Events -->
            <div v-if="pinnedEvents.length > 0 && (activeFilter === 'all' || activeFilter === 'event')" class="space-y-4">
                <h2 class="flex items-center gap-2 text-lg font-bold">
                    <Pin class="h-5 w-5 text-red-500" />
                    Event Terbaru
                </h2>
                <div class="grid gap-4 md:grid-cols-2">
                    <div
                        v-for="event in pinnedEvents"
                        :key="'pinned-' + event.id"
                        class="group cursor-pointer overflow-hidden rounded-xl border-2 border-blue-200 bg-gradient-to-r from-blue-50 to-white shadow transition-all hover:shadow-lg dark:border-blue-800 dark:from-blue-950 dark:to-gray-900"
                        @click="navigateToItem(event)"
                    >
                        <div class="flex">
                            <div v-if="event.image" class="h-32 w-32 flex-shrink-0">
                                <img :src="`/storage/${event.image}`" :alt="event.title" class="h-full w-full object-cover" />
                            </div>
                            <div class="flex-1 p-4">
                                <div class="mb-1 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="rounded-full bg-blue-500 px-2 py-0.5 text-xs font-semibold text-white">Event</span>
                                        <Pin class="h-3 w-3 text-red-500" />
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="h-6 w-6 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100"
                                        @click.stop="copyUrl(event)"
                                    >
                                        <Share2 class="h-3 w-3" />
                                    </Button>
                                </div>
                                <h3 class="font-bold text-gray-900 dark:text-white">{{ event.title }}</h3>
                                <div class="mt-2 flex items-center gap-3 text-xs text-gray-500">
                                    <div class="flex items-center gap-1">
                                        <Calendar class="h-3 w-3" />
                                        <span>{{ event.date_event ? formatEventDate(event.date_event) : '-' }}</span>
                                    </div>
                                    <div v-if="event.location" class="flex items-center gap-1">
                                        <MapPin class="h-3 w-3" />
                                        <span class="max-w-20 truncate">{{ event.location }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feed Items -->
            <div class="space-y-4">
                <div
                    v-for="item in filteredFeed"
                    :key="item.type + '-' + item.id"
                    :class="[
                        'group transform cursor-pointer space-y-4 rounded-2xl border-y border-r border-l-4 bg-white p-4 shadow transition-all hover:-translate-y-1 hover:scale-[1.02] hover:shadow-lg dark:border-y-gray-700 dark:border-r-gray-700 dark:bg-gray-800 dark:hover:shadow-gray-700',
                        getTypeStyles(item.type).border,
                        getTypeStyles(item.type).gradient,
                    ]"
                    @click="navigateToItem(item)"
                >
                    <!-- Header -->
                    <div class="mb-3 flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-lg font-bold text-black shadow">
                                {{ item.user.name.charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-bold">
                                        {{ item.user.role === 'user' ? item.user.clean_name : item.user.name }}
                                    </h3>
                                    <div v-if="item.user.role === 'admin'" class="rounded bg-red-600 px-3 py-1 text-sm font-bold text-white shadow">
                                        Admin
                                    </div>
                                    <div
                                        v-if="item.user.role === 'moderator'"
                                        class="rounded bg-blue-600 px-3 py-1 text-sm font-bold text-white shadow"
                                    >
                                        Moderator
                                    </div>
                                    <div
                                        v-if="item.user.role === 'community'"
                                        class="rounded bg-yellow-500 px-3 py-1 text-sm font-bold text-white shadow"
                                    >
                                        Komunitas
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                    <!-- Type Icon -->
                                    <div
                                        class="flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="{
                                            'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300': item.type === 'event',
                                            'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300': item.type === 'collaboration',
                                            'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300': item.type === 'volunteer',
                                            'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300': item.type === 'post',
                                        }"
                                    >
                                        <Calendar v-if="item.type === 'event'" class="h-3 w-3" />
                                        <Workflow v-if="item.type === 'collaboration'" class="h-3 w-3" />
                                        <HandHeart v-if="item.type === 'volunteer'" class="h-3 w-3" />
                                        <MessageSquare v-if="item.type === 'post'" class="h-3 w-3" />
                                        <span>{{ getTypeLabel(item.type) }}</span>
                                    </div>
                                    <span class="text-gray-400">•</span>
                                    <Clock class="h-3 w-3" />
                                    <span>{{ formatDate(item.created_at) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-1">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100"
                                @click.stop="copyUrl(item)"
                            >
                                <Share2 class="h-4 w-4" />
                            </Button>
                            <DropdownMenu>
                                <DropdownMenuTrigger @click.stop>
                                    <EllipsisVertical class="h-5 w-5 text-gray-500" />
                                </DropdownMenuTrigger>
                                <DropdownMenuContent>
                                    <DropdownMenuItem @click="copyUrl(item)">Salin Link</DropdownMenuItem>
                                    <DropdownMenuItem
                                        @click="
                                            $inertia.visit(
                                                route('user.details', {
                                                    hashId: item.user.hash_id,
                                                    name: item.user.clean_name.toLowerCase().split(' ').join('-'),
                                                }),
                                            )
                                        "
                                    >
                                        Lihat Profil
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>

                    <!-- Content based on type -->
                    <div v-if="item.title" class="mb-2 text-xl font-bold">
                        {{ item.title }}
                    </div>

                    <div v-if="item.image">
                        <img
                            :src="`/storage/${item.image}`"
                            class="max-h-[500px] w-auto max-w-full rounded-xl object-cover shadow-sm"
                            alt="Post content"
                        />
                    </div>

                    <p v-if="item.content || item.description" class="whitespace-pre-wrap text-gray-700 dark:text-gray-300">
                        {{ item.content || item.description }}
                    </p>

                    <!-- Event specific details -->
                    <div v-if="item.type === 'event'" class="mt-4 grid grid-cols-2 gap-4 text-sm">
                        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                            <Calendar class="h-4 w-4" />
                            <span>{{ item.date_event ? formatEventDate(item.date_event) : '-' }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                            <MapPin class="h-4 w-4" />
                            <span>{{ item.location }}</span>
                        </div>
                    </div>

                    <!-- Collaboration/Volunteer specific details -->
                    <div v-if="item.type === 'collaboration' || item.type === 'volunteer'" class="mt-4 flex flex-wrap gap-4 text-sm">
                        <div v-if="item.participants_needed" class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                            <Users class="h-4 w-4" />
                            <span>Dibutuhkan: {{ item.participants_needed }} orang</span>
                        </div>
                        <div v-if="item.interests_count" class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                            <Users class="h-4 w-4" />
                            <span>{{ item.interests_count }} tertarik</span>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredFeed.length === 0" class="flex flex-col items-center justify-center py-12 text-center text-gray-500">
                    <AlertDialog>
                        <AlertDialogTrigger as-child>
                            <Button class="text-lg">Posting</Button>
                        </AlertDialogTrigger>
                        <AlertDialogContent class="max-w-md">
                            <AlertDialogHeader>
                                <AlertDialogTitle class="text-xl">Pilih Jenis Postingan</AlertDialogTitle>
                            </AlertDialogHeader>

                            <div class="grid gap-3 py-4">
                                <Card
                                    class="cursor-pointer rounded border-2 p-2 transition-all hover:border-blue-500 hover:shadow-lg"
                                    @click="$inertia.visit(route('user.create-event'))"
                                >
                                    <CardContent class="p-4">
                                        <div class="flex items-center gap-4">
                                            <Calendar class="h-8 w-8 text-blue-600 dark:text-blue-400" />
                                            <div>
                                                <h3 class="text-lg font-semibold">Event</h3>
                                                <p class="text-sm text-gray-500">Buat acara kampus/komunitas</p>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card
                                    class="cursor-pointer rounded border-2 p-2 transition-all hover:border-green-500 hover:shadow-lg"
                                    @click="$inertia.visit(route('user.create-collaboration'))"
                                >
                                    <CardContent class="p-4">
                                        <div class="flex items-center gap-4">
                                            <Workflow class="h-8 w-8 text-green-600 dark:text-green-400" />
                                            <div>
                                                <h3 class="text-lg font-semibold">Kolaborasi / Relawan</h3>
                                                <p class="text-sm text-gray-500">Cari rekan atau volunteer</p>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card
                                    class="cursor-pointer rounded border-2 p-2 transition-all hover:border-purple-500 hover:shadow-lg"
                                    @click="$inertia.visit(route('user.create-post'))"
                                >
                                    <CardContent class="p-4">
                                        <div class="flex items-center gap-4">
                                            <Rat class="h-8 w-8 text-purple-600 dark:text-purple-400" />
                                            <div>
                                                <h3 class="text-lg font-semibold">Cerita</h3>
                                                <p class="text-sm text-gray-500">Info terkait kampus/komunitas</p>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>

                            <AlertDialogFooter>
                                <AlertDialogCancel>Batal</AlertDialogCancel>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>
                    <div class="mt-4">Tidak ada konten untuk ditampilkan</div>
                </div>
            </div>

            <!-- Floating Action Button -->
            <div class="fixed right-6 bottom-6 z-50">
                <AlertDialog>
                    <AlertDialogTrigger as-child>
                        <Button size="icon" class="h-14 w-14 rounded-full shadow-xl transition-transform hover:scale-105">
                            <Plus class="h-8 w-8" />
                        </Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent class="max-w-md">
                        <AlertDialogHeader>
                            <AlertDialogTitle class="text-xl">Pilih Jenis Postingan</AlertDialogTitle>
                        </AlertDialogHeader>

                        <div class="grid gap-3 py-4">
                            <Card
                                class="cursor-pointer rounded border-2 p-2 transition-all hover:border-blue-500 hover:shadow-lg"
                                @click="$inertia.visit(route('user.create-event'))"
                            >
                                <CardContent class="p-4">
                                    <div class="flex items-center gap-4">
                                        <Calendar class="h-8 w-8 text-blue-600 dark:text-blue-400" />
                                        <div>
                                            <h3 class="text-lg font-semibold">Event</h3>
                                            <p class="text-sm text-gray-500">Buat acara kampus/komunitas</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card
                                class="cursor-pointer rounded border-2 p-2 transition-all hover:border-green-500 hover:shadow-lg"
                                @click="$inertia.visit(route('user.create-collaboration'))"
                            >
                                <CardContent class="p-4">
                                    <div class="flex items-center gap-4">
                                        <Workflow class="h-8 w-8 text-green-600 dark:text-green-400" />
                                        <div>
                                            <h3 class="text-lg font-semibold">Kolaborasi / Relawan</h3>
                                            <p class="text-sm text-gray-500">Cari rekan atau volunteer</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card
                                class="cursor-pointer rounded border-2 p-2 transition-all hover:border-purple-500 hover:shadow-lg"
                                @click="$inertia.visit(route('user.create-post'))"
                            >
                                <CardContent class="p-4">
                                    <div class="flex items-center gap-4">
                                        <Rat class="h-8 w-8 text-purple-600 dark:text-purple-400" />
                                        <div>
                                            <h3 class="text-lg font-semibold">Cerita</h3>
                                            <p class="text-sm text-gray-500">Info terkait kampus/komunitas</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <AlertDialogFooter>
                            <AlertDialogCancel>Batal</AlertDialogCancel>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            </div>
        </div>
    </AppLayout>
</template>
