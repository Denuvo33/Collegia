<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Toaster } from '@/components/ui/sonner';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm, usePage, useRemember } from '@inertiajs/vue3';
import axios from 'axios';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import relativeTime from 'dayjs/plugin/relativeTime';
import { Calendar, Clock, EllipsisVertical, Eye, Gem, MapPin, MessageCircle, Share2, Tag } from 'lucide-vue-next';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

import 'vue-sonner/style.css';

const page = usePage();

dayjs.locale('id');
dayjs.extend(relativeTime);

type Post = {
    content: string;
    image: string | null;
    id: number;
    hash_id: string;
    created_at: string;
};

type Comment = {
    id: number;
    content: string;
    created_at: string;
    hash_id: string;
};

type Event = {
    id: number;
    is_promoted: boolean;
    hash_id: string;
    title: string;
    content: string;
    date_event: string;
    image: string;
    location: string;
    price: number;
    created_at: string;
    promoted_till: string | null;
    is_visible: boolean;
    order_id: string | null;
    transaction_status: string | null;
};

const props = defineProps<{
    posts_count: number;
    data: {
        id: number;
        name: string;
        clean_name: string;
        created_at: string;
        role: string;
        posts: {
            data: Post[];
            current_page: number;
            last_page: number;
        };
        comments: {
            data: Comment[];
            current_page: number;
            last_page: number;
        };
        events: Event[];
    };
}>();

const form = useForm({
    promote_event: null,
});

const activatedTabs = useRemember(ref<'post' | 'comment' | 'inkrip' | 'event'>('post'), 'activeTabs');

const formatTime = (date: string) => {
    return dayjs(date).fromNow();
};

// Check if event promotion has expired
const isPromotionExpired = (promotedTill: string | null) => {
    if (!promotedTill) return true; // null means not promoted
    const today = dayjs();
    const expireDate = dayjs(promotedTill);
    return expireDate.isBefore(today, 'day');
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile',
        href: '/user/profile',
    },
];

// Posts state
const posts = ref<Post[]>([...props.data.posts.data]);
const postPage = ref(props.data.posts.current_page);
const postLastPage = ref(props.data.posts.last_page);
const postLoading = ref(false);

// Comments state
const comments = ref<Comment[]>([...props.data.comments.data]);
const commentPage = ref(props.data.comments.current_page);
const commentLastPage = ref(props.data.comments.last_page);
const commentLoading = ref(false);

// Load more posts
const loadMorePosts = async () => {
    if (postLoading.value || postPage.value >= postLastPage.value) return;
    postLoading.value = true;

    try {
        const res = await axios.get('/user/profile/posts', {
            params: { page: postPage.value + 1 },
            headers: { Accept: 'application/json' },
        });
        const newData = res.data;
        posts.value.push(...newData.data);
        postPage.value = newData.current_page;
        postLastPage.value = newData.last_page;
    } catch (error) {
        console.error('Error loading posts:', error);
        toast.error('Failed to load more posts');
    } finally {
        postLoading.value = false;
    }
};

// Load more comments
const loadMoreComments = async () => {
    if (commentLoading.value || commentPage.value >= commentLastPage.value) return;
    commentLoading.value = true;

    try {
        const res = await axios.get('/user/profile/comments', {
            params: { page: commentPage.value + 1 },
            headers: { Accept: 'application/json' },
        });
        const newData = res.data;
        comments.value.push(...newData.data);
        commentPage.value = newData.current_page;
        commentLastPage.value = newData.last_page;
    } catch (error) {
        console.error('Error loading comments:', error);
        toast.error('Failed to load more comments');
    } finally {
        commentLoading.value = false;
    }
};

const postSentinel = ref<HTMLElement | null>(null);
const commentSentinel = ref<HTMLElement | null>(null);

let postObserver: IntersectionObserver | null = null;
let commentObserver: IntersectionObserver | null = null;

const setupObservers = () => {
    if (postObserver) {
        postObserver.disconnect();
        postObserver = null;
    }
    if (commentObserver) {
        commentObserver.disconnect();
        commentObserver = null;
    }

    postObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting && activatedTabs.value === 'post') {
                    loadMorePosts();
                }
            });
        },
        { rootMargin: '200px' },
    );

    commentObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting && activatedTabs.value === 'comment') {
                    loadMoreComments();
                }
            });
        },
        { rootMargin: '200px' },
    );
};

const observeCurrentTab = () => {
    nextTick(() => {
        if (activatedTabs.value === 'post' && postSentinel.value && postObserver) {
            postObserver.observe(postSentinel.value);
        } else if (activatedTabs.value === 'comment' && commentSentinel.value && commentObserver) {
            commentObserver.observe(commentSentinel.value);
        }
    });
};

watch(activatedTabs, () => {
    if (postObserver && postSentinel.value) {
        postObserver.unobserve(postSentinel.value);
    }
    if (commentObserver && commentSentinel.value) {
        commentObserver.unobserve(commentSentinel.value);
    }
    observeCurrentTab();
});

function deletePost(id: number) {
    return router.delete(route('user.post.delete', id), {
        onError: (errors) => {
            console.error('error: ', errors);
        },
        onSuccess: () => {
            posts.value = posts.value.filter((post) => post.id !== id);
        },
    });
}

watch(
    () => page.props.flash,
    (flash: any) => {
        if (flash?.success) {
            toast.success(flash.success);
        }
        if (flash?.error) {
            toast.error(flash.error);
        }
    },
    { deep: true },
);

const sortList = <T extends { created_at: string }>(list: Array<T>) => {
    return list.sort((a, b) => {
        const dateA = new Date(a.created_at);
        const dateB = new Date(b.created_at);
        return dateB.getTime() - dateA.getTime();
    });
};

onMounted(() => {
    setupObservers();
    observeCurrentTab();
});

onBeforeUnmount(() => {
    if (postObserver) postObserver.disconnect();
    if (commentObserver) commentObserver.disconnect();
});

function copyPostUrl(hashId: string) {
    const postUrl = `${window.location.origin}/user/post/${hashId}`;
    navigator.clipboard.writeText(postUrl).then(
        () => {
            toast.success('Link postingan disalin ke clipboard!', {
                duration: 3000,
                action: {
                    label: 'Tutup',
                    onClick: () => {
                        toast.dismiss();
                    },
                },
            });
        },
        (err) => {
            console.error('Could not copy text: ', err);
        },
    );
}

// Delete event
const deleteEvent = (eventId: number) => {
    router.delete(route('user.delete.event', eventId), {
        onSuccess: () => {
            // Remove event from the list
            props.data.events = props.data.events.filter((e) => e.id !== eventId);
        },
        onError: (errors) => {
            console.error('Failed to delete event:', errors);
        },
    });
};
</script>

<template>
    <Head title="Profile" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster />

        <!-- Profile Header -->
        <div class="mb-8 rounded-lg border border-gray-200 bg-white p-8 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-col items-center gap-6">
                <!-- Avatar -->
                <div
                    class="flex h-20 w-20 items-center justify-center rounded-full bg-gray-200 text-2xl font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-200"
                >
                    {{ data.name.charAt(0).toUpperCase() }}
                </div>

                <!-- Name + Join -->
                <div class="text-center">
                    <div class="flex flex-wrap items-center justify-center gap-2">
                        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ data.role == 'user' ? data.clean_name : data.name }}</h1>
                        <span
                            v-if="data.role === 'moderator'"
                            class="rounded-md bg-blue-100 px-2 py-1 text-sm font-medium text-blue-700 dark:bg-blue-900 dark:text-blue-300"
                        >
                            Moderator
                        </span>
                        <span
                            v-if="data.role === 'community'"
                            class="rounded-md bg-yellow-100 px-2 py-1 text-sm font-medium text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300"
                        >
                            Komunitas
                        </span>
                    </div>
                    <div class="mt-2 flex items-center justify-center gap-1.5 text-sm text-gray-500">
                        <Clock class="h-4 w-4" />
                        <p>Bergabung {{ formatTime(data.created_at) }}</p>
                    </div>
                </div>

                <!-- Stats -->
                <div class="flex gap-8">
                    <div class="text-center">
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ posts_count }}</p>
                        <p class="text-sm text-gray-500">Postingan</p>
                    </div>
                    <div class="h-12 w-px bg-gray-200 dark:bg-gray-700"></div>
                    <div class="text-center">
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ data.events.length }}</p>
                        <p class="text-sm text-gray-500">Event</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="sticky top-0 z-10 mb-8 border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <div class="flex justify-center">
                <div class="flex gap-1">
                    <button
                        class="border-b-2 px-6 py-3 text-sm font-medium transition-colors"
                        @click="activatedTabs = 'post'"
                        :class="
                            activatedTabs == 'post'
                                ? 'border-gray-900 text-gray-900 dark:border-white dark:text-white'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
                        "
                    >
                        Postingan
                    </button>
                    <button
                        class="border-b-2 px-6 py-3 text-sm font-medium transition-colors"
                        :class="
                            activatedTabs == 'comment'
                                ? 'border-gray-900 text-gray-900 dark:border-white dark:text-white'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
                        "
                        @click="activatedTabs = 'comment'"
                    >
                        Komentar
                    </button>
                    <button
                        class="border-b-2 px-6 py-3 text-sm font-medium transition-colors"
                        :class="
                            activatedTabs == 'event'
                                ? 'border-gray-900 text-gray-900 dark:border-white dark:text-white'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
                        "
                        @click="activatedTabs = 'event'"
                    >
                        Event
                    </button>
                    <button
                        class="border-b-2 px-6 py-3 text-sm font-medium transition-colors"
                        :class="
                            activatedTabs == 'inkrip'
                                ? 'border-gray-900 text-gray-900 dark:border-white dark:text-white'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
                        "
                        @click="activatedTabs = 'inkrip'"
                    >
                        Inkrip
                    </button>
                </div>
            </div>
        </div>

        <!-- Posts Tab -->
        <div class="space-y-4 px-6" v-if="activatedTabs == 'post'">
            <div
                v-for="post in sortList(posts)"
                :key="post.id"
                class="rounded-lg border border-gray-200 bg-white transition-shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
            >
                <!-- Post Header -->
                <div class="p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 text-sm font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-200"
                            >
                                {{ data.name.charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <h3
                                    class="cursor-pointer font-medium text-gray-900 hover:text-gray-700 dark:text-white dark:hover:text-gray-300"
                                    @click="$inertia.visit(`/user/post/${post.hash_id}`)"
                                >
                                    {{ data.clean_name }}
                                </h3>
                                <div class="flex items-center gap-1 text-xs text-gray-500">
                                    <Clock class="h-3 w-3" />
                                    <span>{{ formatTime(post.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                        <DropdownMenu>
                            <DropdownMenuTrigger class="rounded p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <EllipsisVertical class="h-5 w-5 text-gray-400" />
                            </DropdownMenuTrigger>
                            <DropdownMenuContent>
                                <DropdownMenuItem
                                    @click="deletePost(post.id)"
                                    class="text-red-600 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-900/20"
                                >
                                    Hapus
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    <!-- Post Content -->
                    <p class="mb-3 text-gray-700 dark:text-gray-300">{{ post.content }}</p>

                    <!-- Post Image -->
                    <div v-if="post.image" class="mb-3">
                        <img :src="`/storage/${post.image}`" alt="post image" class="max-h-96 w-96 cursor-pointer rounded-lg object-cover" />
                    </div>
                </div>

                <!-- Post Actions -->
                <div class="border-t border-gray-100 px-5 py-3 dark:border-gray-700">
                    <div class="flex items-center gap-4">
                        <button
                            class="flex items-center gap-1.5 text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200"
                            @click="$inertia.visit(route('user.post.details', post.hash_id))"
                        >
                            <MessageCircle class="h-4 w-4" />
                            <span>Komentar</span>
                        </button>
                        <button
                            class="flex items-center gap-1.5 text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200"
                            @click="copyPostUrl(post.hash_id)"
                        >
                            <Share2 class="h-4 w-4" />
                            <span>Bagikan</span>
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="postPage < postLastPage" ref="postSentinel" class="h-1"></div>

            <!-- Loading States -->
            <div v-if="postLoading" class="flex justify-center py-8">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <div class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400"></div>
                    <div class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400" style="animation-delay: 0.1s"></div>
                    <div class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400" style="animation-delay: 0.2s"></div>
                    <span class="ml-2">Memuat postingan...</span>
                </div>
            </div>
            <div v-else-if="postPage >= postLastPage && posts.length > 0" class="py-8 text-center text-sm text-gray-500">
                Tidak ada postingan lagi
            </div>
            <div v-else-if="posts.length == 0" class="py-16 text-center">
                <div class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                    <MessageCircle class="h-8 w-8 text-gray-400" />
                </div>
                <h3 class="mb-1 font-medium text-gray-900 dark:text-gray-100">Belum ada postingan</h3>
                <p class="text-sm text-gray-500">Mulai berbagi cerita Anda dengan dunia!</p>
            </div>
        </div>

        <!-- Comments Tab -->
        <div class="space-y-4 px-6" v-else-if="activatedTabs == 'comment'">
            <div
                v-for="comment in sortList(comments)"
                :key="comment.id"
                class="cursor-pointer rounded-lg border border-gray-200 bg-white p-5 transition-shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
                @click="$inertia.visit(route('user.post.details', comment.hash_id))"
            >
                <div class="mb-3 flex items-center gap-3">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-200 text-sm font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-200"
                    >
                        {{ data.name.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                        <h3 class="font-medium text-gray-900 dark:text-white">{{ data.clean_name }}</h3>
                        <div class="flex items-center gap-1 text-xs text-gray-500">
                            <Clock class="h-3 w-3" />
                            <span>{{ formatTime(comment.created_at) }}</span>
                        </div>
                    </div>
                </div>
                <p class="text-gray-700 dark:text-gray-300">{{ comment.content }}</p>
            </div>

            <div v-if="commentPage < commentLastPage" ref="commentSentinel" class="h-1"></div>

            <div v-if="commentLoading" class="flex justify-center py-8">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <div class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400"></div>
                    <div class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400" style="animation-delay: 0.1s"></div>
                    <div class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400" style="animation-delay: 0.2s"></div>
                    <span class="ml-2">Memuat komentar...</span>
                </div>
            </div>
            <div v-else-if="commentPage >= commentLastPage && comments.length > 0" class="py-8 text-center text-sm text-gray-500">
                Tidak ada komentar lagi
            </div>
            <div v-else-if="comments.length == 0" class="py-16 text-center">
                <div class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                    <MessageCircle class="h-8 w-8 text-gray-400" />
                </div>
                <h3 class="mb-1 font-medium text-gray-900 dark:text-gray-100">Belum ada komentar</h3>
                <p class="text-sm text-gray-500">Mulai berinteraksi dengan postingan lain!</p>
            </div>
        </div>

        <!-- Event Tab -->
        <div v-if="activatedTabs == 'event'" class="grid grid-cols-1 gap-4 px-6 md:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="event in data.events"
                :key="event.id"
                class="overflow-hidden rounded-lg border border-gray-200 bg-white transition-shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
            >
                <!-- Event Image -->
                <div class="relative h-48 bg-gray-200 dark:bg-gray-700">
                    <img v-if="event.image" :src="`/storage/${event.image}`" alt="Event" class="h-full w-full object-cover" />
                    <div v-else class="flex h-full items-center justify-center">
                        <Calendar class="h-12 w-12 text-gray-400" />
                    </div>
                    <div class="absolute top-3 right-3 flex gap-2">
                        <div class="rounded-md bg-white px-2.5 py-1 shadow-sm">
                            <span class="text-xs font-semibold text-gray-900">
                                {{ event.price == 0 ? 'GRATIS' : `Rp ${event.price.toLocaleString()}` }}
                            </span>
                        </div>

                        <DropdownMenu>
                            <DropdownMenuTrigger class="rounded-md bg-white p-1 shadow-sm">
                                <EllipsisVertical class="h-4 w-4 text-gray-600" />
                            </DropdownMenuTrigger>
                            <DropdownMenuContent>
                                <DropdownMenuItem
                                    @click="deleteEvent(event.id)"
                                    class="cursor-pointer text-red-600 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-900/20"
                                >
                                    Hapus
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    <div
                        v-if="event.is_promoted"
                        class="absolute top-3 left-3 flex items-center gap-1 rounded-md bg-yellow-100 px-2.5 py-1 shadow-sm"
                    >
                        <Gem class="h-3.5 w-3.5 text-yellow-600" />
                        <span class="text-xs font-semibold text-yellow-900">PROMOSI</span>
                    </div>
                    <!-- Visibility Badge for invisible events -->
                    <div
                        v-if="!event.is_visible"
                        class="absolute bottom-3 left-3 flex items-center gap-1 rounded-md bg-red-100 px-2.5 py-1 shadow-sm"
                    >
                        <Eye class="h-3.5 w-3.5 text-red-600" />
                        <span class="text-xs font-semibold text-red-900">BELUM TERLIHAT</span>
                    </div>
                </div>

                <!-- Event Content -->
                <div class="p-5">
                    <h3 class="mb-2 line-clamp-2 text-lg font-semibold text-gray-900 dark:text-white">{{ event.title }}</h3>
                    <p class="mb-4 line-clamp-2 text-sm text-gray-600 dark:text-gray-400">{{ event.content }}</p>

                    <!-- Event Meta -->
                    <div class="mb-4 space-y-1.5">
                        <div class="flex items-center gap-2 text-gray-500">
                            <Calendar class="h-3.5 w-3.5" />
                            <span class="text-xs">{{ formatTime(event.date_event) }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-500">
                            <MapPin class="h-3.5 w-3.5" />
                            <span class="text-xs">{{ event.location }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-500">
                            <Tag class="h-3.5 w-3.5" />
                            <span class="text-xs">Dibuat {{ formatTime(event.created_at) }}</span>
                        </div>
                        <!-- Order ID Badge -->
                        <div v-if="event.order_id" class="pt-1">
                            <div class="inline-block rounded bg-gray-100 px-2 py-0.5 dark:bg-gray-700">
                                <span class="font-mono text-xs text-gray-700 dark:text-gray-300">ID: {{ event.order_id }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <Button
                        v-if="event.is_visible"
                        class="w-full rounded-lg bg-gray-900 py-2 text-sm font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200"
                        @click="$inertia.visit(route('user.details.event', { hashId: event.hash_id, title: event.title }))"
                    >
                        Lihat Detail Event
                    </Button>
                    <Button
                        v-if="isPromotionExpired(event.promoted_till) && event.is_visible"
                        class="mt-2 w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                        @click="$inertia.visit(route('user.event.promote.form', event.id))"
                    >
                        Promosikan
                    </Button>
                </div>
            </div>

            <!-- Empty State for Events -->
            <div v-if="data.events.length === 0" class="col-span-full py-16 text-center">
                <div class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                    <Calendar class="h-8 w-8 text-gray-400" />
                </div>
                <h3 class="mb-1 font-medium text-gray-900 dark:text-gray-100">Belum ada event</h3>
                <p class="text-sm text-gray-500">Event yang Anda buat akan muncul di sini</p>
            </div>
        </div>

        <!-- Inkrip Tab -->
        <div v-else-if="activatedTabs == 'inkrip'" class="flex items-center justify-center py-20">
            <div class="text-center">
                <h2 class="mb-2 text-xl font-semibold text-gray-900 dark:text-white">Segera Hadir!</h2>
                <p class="text-sm text-gray-500">Fitur Inkrip sedang dalam tahap pengembangan.</p>
            </div>
        </div>
    </AppLayout>
</template>
