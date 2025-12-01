<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Toaster } from '@/components/ui/sonner';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage, useRemember } from '@inertiajs/vue3';
import axios from 'axios';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import relativeTime from 'dayjs/plugin/relativeTime';
import { Calendar, Clock, EllipsisVertical, Eye, MapPin, MessageCircle, Share2, Sparkles, Star, Tag } from 'lucide-vue-next';
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
    hash_id: string;
    title: string;
    content: string;
    date_event: string;
    image: string;
    location: string;
    price: number;
    created_at: string;
    promoted_till: string | null;
};

const props = defineProps<{
    posts_count: number;
    data: {
        id: number;
        name: string;
        created_at: string;
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
        events: {
            data: Event[];
            current_page: number;
            last_page: number;
        };
    };
}>();

const activatedTabs = useRemember(ref<'post' | 'comment' | 'inkrip' | 'event'>('post'), 'activeTabs');

const formatTime = (date: string) => {
    return dayjs(date).fromNow();
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
</script>

<template>
    <Head title="Profile" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster />

        <!-- Profile Header -->
        <div class="relative mb-8 overflow-hidden rounded-b-2xl p-8 shadow-2xl">
            <div class="relative flex flex-col items-center gap-6 text-white">
                <!-- Avatar -->
                <div class="relative">
                    <div
                        class="flex h-24 w-24 items-center justify-center rounded-full border border-white/20 bg-gradient-to-br from-slate-700 to-slate-600 text-2xl font-bold shadow-lg"
                    >
                        {{ data.name.charAt(0).toUpperCase() }}
                    </div>
                    <div
                        class="absolute -right-1 -bottom-1 flex h-6 w-6 items-center justify-center rounded-full border-2 border-slate-900 bg-emerald-400 shadow-sm"
                    >
                        <div class="h-2 w-2 rounded-full bg-emerald-600"></div>
                    </div>
                </div>

                <!-- Name + Join -->
                <div class="text-center">
                    <h1 class="mb-2 text-3xl font-bold text-black dark:text-white">{{ data.name }}</h1>
                    <div class="flex items-center justify-center gap-2 text-slate-300">
                        <Clock class="h-4 w-4" />
                        <p class="text-sm font-medium text-gray-500">Bergabung {{ formatTime(data.created_at) }}</p>
                    </div>
                </div>

                <!-- Stats -->
                <div class="flex flex-wrap justify-center gap-4">
                    <div
                        class="min-w-[100px] rounded-xl border border-white/10 bg-white/5 p-4 text-center shadow-lg backdrop-blur-sm transition hover:bg-white/10"
                    >
                        <p class="mb-1 text-2xl font-bold text-black dark:text-white">{{ posts_count }}</p>
                        <p class="text-sm font-medium text-slate-500">Postingan</p>
                    </div>

                    <div
                        class="min-w-[100px] rounded-xl border border-white/10 bg-white/5 p-4 text-center shadow-lg backdrop-blur-sm transition hover:bg-white/10"
                    >
                        <p class="mb-1 text-2xl font-bold text-black dark:text-white">{{ data.events.data.length }}</p>
                        <p class="text-sm font-medium text-slate-500">Event</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Tab Navigation -->
        <div class="sticky top-0 z-10 mb-8 border-b border-gray-200 bg-white/80 shadow-sm backdrop-blur-md dark:border-gray-700 dark:bg-gray-900/80">
            <div class="flex justify-center">
                <div class="m-4 flex rounded-xl bg-gray-100 p-1 dark:bg-gray-800">
                    <button
                        class="relative overflow-hidden rounded-lg px-6 py-3 text-sm font-semibold transition-all duration-300"
                        @click="activatedTabs = 'post'"
                        :class="
                            activatedTabs == 'post'
                                ? 'scale-105 transform bg-white text-blue-600 shadow-lg dark:bg-gray-700 dark:text-blue-400'
                                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200'
                        "
                    >
                        <span class="relative z-10">Postingan</span>
                    </button>
                    <button
                        class="relative overflow-hidden rounded-lg px-6 py-3 text-sm font-semibold transition-all duration-300"
                        :class="
                            activatedTabs == 'comment'
                                ? 'scale-105 transform bg-white text-blue-600 shadow-lg dark:bg-gray-700 dark:text-blue-400'
                                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200'
                        "
                        @click="activatedTabs = 'comment'"
                    >
                        <span class="relative z-10">Komentar</span>
                    </button>
                    <button
                        class="relative overflow-hidden rounded-lg px-6 py-3 text-sm font-semibold transition-all duration-300"
                        :class="
                            activatedTabs == 'event'
                                ? 'scale-105 transform bg-white text-blue-600 shadow-lg dark:bg-gray-700 dark:text-blue-400'
                                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200'
                        "
                        @click="activatedTabs = 'event'"
                    >
                        <span class="relative z-10">Event</span>
                    </button>
                    <button
                        class="relative overflow-hidden rounded-lg px-6 py-3 text-sm font-semibold transition-all duration-300"
                        :class="
                            activatedTabs == 'inkrip'
                                ? 'scale-105 transform bg-white text-blue-600 shadow-lg dark:bg-gray-700 dark:text-blue-400'
                                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200'
                        "
                        @click="activatedTabs = 'inkrip'"
                    >
                        <span class="relative z-10">Inkrip</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Enhanced Posts Tab -->
        <div class="space-y-6 p-6" v-if="activatedTabs == 'post'">
            <div
                v-for="post in sortList(posts)"
                :key="post.id"
                class="group transform transition-all duration-300 hover:-translate-y-1 hover:scale-[1.02]"
            >
                <div
                    class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-lg transition-all duration-300 hover:shadow-2xl dark:border-gray-700 dark:bg-gray-800"
                >
                    <!-- Post Header -->
                    <div class="p-6 pb-4">
                        <div class="mb-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white text-black shadow-lg">
                                    {{ data.name.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <h3
                                        class="cursor-pointer font-bold text-gray-900 transition-colors duration-300 hover:text-blue-600 dark:text-white"
                                        @click="$inertia.visit(`/user/post/${post.hash_id}`)"
                                    >
                                        {{ data.name }}
                                    </h3>
                                    <div class="flex items-center gap-1 text-sm text-gray-500">
                                        <Clock class="h-3 w-3" />
                                        <span>{{ formatTime(post.created_at) }}</span>
                                    </div>
                                </div>
                            </div>
                            <DropdownMenu>
                                <DropdownMenuTrigger class="rounded-full p-2 transition-colors duration-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <EllipsisVertical class="h-5 w-5 text-gray-500" />
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
                        <p class="mb-4 leading-relaxed text-gray-800 dark:text-gray-200">{{ post.content }}</p>

                        <!-- Post Image -->
                        <div v-if="post.image" class="mb-4">
                            <img
                                :src="`/storage/${post.image}`"
                                alt="post image"
                                class="max-h-96 w-96 cursor-pointer rounded-xl object-cover shadow-md transition-shadow duration-300 hover:shadow-lg"
                            />
                        </div>
                    </div>

                    <!-- Post Actions -->
                    <div class="flex items-center justify-between border-t border-gray-100 px-6 pt-4 pb-6 dark:border-gray-700">
                        <div class="flex items-center gap-6">
                            <!-- <button class="group flex items-center gap-2 text-gray-500 transition-colors duration-300 hover:text-red-500">
                                <Heart class="h-5 w-5 transition-transform duration-300 group-hover:scale-110" />
                                <span class="text-sm font-medium">Suka</span>
                            </button> -->
                            <button
                                class="group flex items-center gap-2 text-gray-500 transition-colors duration-300 hover:text-blue-500"
                                @click="$inertia.visit(route('user.post.details', post.hash_id))"
                            >
                                <MessageCircle class="h-5 w-5 transition-transform duration-300 group-hover:scale-110" />
                                <span class="text-sm font-medium">Komentar</span>
                            </button>
                            <button
                                class="group flex items-center gap-2 text-gray-500 transition-colors duration-300 hover:text-green-500"
                                @click="copyPostUrl(post.hash_id)"
                            >
                                <Share2 class="h-5 w-5 transition-transform duration-300 group-hover:scale-110" />
                                <span class="text-sm font-medium">Bagikan</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="postPage < postLastPage" ref="postSentinel" class="h-1"></div>

            <!-- Enhanced Loading States -->
            <div v-if="postLoading" class="flex items-center justify-center p-8">
                <div class="flex items-center gap-3">
                    <div class="h-2 w-2 animate-bounce rounded-full bg-blue-500"></div>
                    <div class="h-2 w-2 animate-bounce rounded-full bg-purple-500 delay-75"></div>
                    <div class="h-2 w-2 animate-bounce rounded-full bg-pink-500 delay-150"></div>
                    <span class="ml-3 text-gray-600 dark:text-gray-400">Memuat postingan...</span>
                </div>
            </div>
            <div v-else-if="postPage >= postLastPage && posts.length > 0" class="flex justify-center p-8 text-gray-500">
                <div class="text-center">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700">
                        <Eye class="h-6 w-6" />
                    </div>
                    <p>Tidak ada postingan lagi</p>
                </div>
            </div>
            <div v-else-if="posts.length == 0" class="flex items-center justify-center py-16">
                <div class="text-center">
                    <div class="mx-auto mb-4 flex h-24 w-24 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                        <MessageCircle class="h-12 w-12 text-gray-400" />
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-gray-600 dark:text-gray-400">Belum ada postingan</h3>
                    <p class="text-gray-500">Mulai berbagi cerita Anda dengan dunia!</p>
                </div>
            </div>
        </div>

        <!-- Enhanced Comments Tab -->
        <div class="space-y-4 p-6" v-else-if="activatedTabs == 'comment'">
            <div
                v-for="comment in sortList(comments)"
                :key="comment.id"
                class="group transform cursor-pointer transition-all duration-300 hover:-translate-y-1 hover:scale-[1.01]"
                @click="$inertia.visit(route('user.post.details', comment.hash_id))"
            >
                <div
                    class="rounded-xl border border-gray-100 bg-white p-6 shadow-md transition-all duration-300 hover:shadow-lg dark:border-gray-700 dark:bg-gray-800"
                >
                    <div class="mb-3 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-black shadow-md">
                            {{ data.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white">{{ data.name }}</h3>
                            <div class="flex items-center gap-1 text-sm text-gray-500">
                                <Clock class="h-3 w-3" />
                                <span>{{ formatTime(comment.created_at) }}</span>
                            </div>
                        </div>
                    </div>
                    <p class="leading-relaxed text-gray-700 dark:text-gray-300">{{ comment.content }}</p>
                </div>
            </div>

            <div v-if="commentPage < commentLastPage" ref="commentSentinel" class="h-1"></div>

            <div v-if="commentLoading" class="flex items-center justify-center p-8">
                <div class="flex items-center gap-3">
                    <div class="h-2 w-2 animate-bounce rounded-full bg-green-500"></div>
                    <div class="h-2 w-2 animate-bounce rounded-full bg-blue-500 delay-75"></div>
                    <div class="h-2 w-2 animate-bounce rounded-full bg-purple-500 delay-150"></div>
                    <span class="ml-3 text-gray-600 dark:text-gray-400">Memuat komentar...</span>
                </div>
            </div>
            <div v-else-if="commentPage >= commentLastPage && comments.length > 0" class="flex justify-center p-8 text-gray-500">
                <div class="text-center">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700">
                        <MessageCircle class="h-6 w-6" />
                    </div>
                    <p>Tidak ada komentar lagi</p>
                </div>
            </div>
            <div v-else-if="comments.length == 0" class="flex items-center justify-center py-16">
                <div class="text-center">
                    <div class="mx-auto mb-4 flex h-24 w-24 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                        <MessageCircle class="h-12 w-12 text-gray-400" />
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-gray-600 dark:text-gray-400">Belum ada komentar</h3>
                    <p class="text-gray-500">Mulai berinteraksi dengan postingan lain!</p>
                </div>
            </div>
        </div>

        <!-- Enhanced Event Tab -->
        <div v-if="activatedTabs == 'event'" class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="event in data.events.data"
                :key="event.id"
                class="group transform transition-all duration-300 hover:-translate-y-2 hover:scale-105"
            >
                <div
                    class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-lg transition-all duration-300 hover:shadow-2xl dark:border-gray-700 dark:bg-gray-800"
                >
                    <!-- Event Image -->
                    <div class="relative h-48 bg-gradient-to-br from-slate-400 via-slate-500 to-slate-600">
                        <img v-if="event.image" :src="`/storage/${event.image}`" alt="Event" class="h-full w-full object-cover" />
                        <div v-else class="flex h-full items-center justify-center">
                            <Calendar class="h-16 w-16 text-white/80" />
                        </div>
                        <div class="absolute top-4 right-4 rounded-lg bg-white/90 px-3 py-1 backdrop-blur-sm">
                            <span class="text-sm font-bold text-gray-800">
                                {{ event.price == 0 ? 'GRATIS' : `Rp ${event.price.toLocaleString()}` }}
                            </span>
                        </div>
                        <div
                            v-if="event.promoted_till"
                            class="absolute top-4 left-4 flex items-center gap-1 rounded-full bg-yellow-400 px-3 py-1 text-xs font-bold text-yellow-900"
                        >
                            <Star class="h-3 w-3" />
                            PROMOSI
                        </div>
                    </div>

                    <!-- Event Content -->
                    <div class="p-6">
                        <h3 class="mb-3 line-clamp-2 text-xl font-bold text-gray-900 dark:text-white">{{ event.title }}</h3>
                        <p class="mb-4 line-clamp-3 leading-relaxed text-gray-600 dark:text-gray-400">{{ event.content }}</p>

                        <!-- Event Meta -->
                        <div class="mb-6 space-y-2">
                            <div class="flex items-center gap-2 text-gray-500">
                                <Calendar class="h-4 w-4" />
                                <span class="text-sm">{{ formatTime(event.date_event) }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-500">
                                <MapPin class="h-4 w-4" />
                                <span class="text-sm">{{ event.location }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-500">
                                <Tag class="h-4 w-4" />
                                <span class="text-sm">Dibuat {{ formatTime(event.created_at) }}</span>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <Button
                            class="w-full transform rounded-xl bg-gradient-to-br from-slate-400 via-slate-500 to-slate-600 py-3 font-semibold text-white shadow-lg transition-all duration-300 hover:scale-105 hover:from-slate-700 hover:to-slate-700 hover:shadow-xl"
                            @click="$inertia.visit(route('user.details.event', { hashId: event.hash_id, title: event.title }))"
                        >
                            Lihat Detail Event
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Empty State for Events -->
            <div v-if="data.events.data.length === 0" class="col-span-full flex items-center justify-center py-16">
                <div class="text-center">
                    <div class="mx-auto mb-4 flex h-24 w-24 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                        <Calendar class="h-12 w-12 text-gray-400" />
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-gray-600 dark:text-gray-400">Belum ada event</h3>
                    <p class="text-gray-500">Event yang Anda buat akan muncul di sini</p>
                </div>
            </div>
        </div>

        <!-- Enhanced Inkrip Tab -->
        <div v-else-if="activatedTabs == 'inkrip'" class="flex items-center justify-center py-20">
            <div class="max-w-md text-center">
                <!-- Animated Coming Soon Illustration -->
                <div class="relative mb-8">
                    <div
                        class="mx-auto flex h-32 w-32 items-center justify-center rounded-full bg-gradient-to-br from-blue-100 to-purple-100 shadow-lg dark:from-blue-900 dark:to-purple-900"
                    >
                        <div
                            class="flex h-20 w-20 animate-pulse items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-purple-500 shadow-md"
                        >
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white">
                                <Sparkles class="h-6 w-6 text-blue-500" />
                            </div>
                        </div>
                    </div>
                    <!-- Floating Elements -->
                    <div class="absolute -top-2 -right-2 h-6 w-6 animate-bounce rounded-full bg-yellow-400"></div>
                    <div class="absolute -bottom-2 -left-2 h-4 w-4 animate-bounce rounded-full bg-pink-400 delay-500"></div>
                    <div class="absolute top-1/2 -left-4 h-3 w-3 animate-bounce rounded-full bg-blue-400 delay-1000"></div>
                </div>

                <!-- Coming Soon Content -->
                <div class="space-y-4">
                    <h2 class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-3xl font-bold text-transparent">Segera Hadir!</h2>
                    <p class="leading-relaxed text-gray-600 dark:text-gray-400">
                        Fitur Inkrip sedang dalam tahap pengembangan. Nantikan update menarik yang akan membuat pengalaman Anda lebih istimewa.
                    </p>

                    <!-- Progress Bar -->
                    <div class="mt-6 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                        <div class="h-2 w-3/4 animate-pulse rounded-full bg-gradient-to-r from-blue-500 to-purple-500"></div>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">75% Complete</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
