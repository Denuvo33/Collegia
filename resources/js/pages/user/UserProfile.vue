<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Toaster } from '@/components/ui/sonner';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import relativeTime from 'dayjs/plugin/relativeTime';
import { Building2, Clock, Lock } from 'lucide-vue-next';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import 'vue-sonner/style.css';

dayjs.locale('id');
dayjs.extend(relativeTime);

type post = {
    id: number;
    content: string;
    hash_id: string;
    image: string | null;
    created_at: string;
    type: 'post' | 'collaboration' | 'volunteer';
    title?: string;
    description?: string;
    location?: string;
    start_date?: string;
    end_date?: string;
    participants_needed?: number;
};

type event = {
    id: number;
    hash_id: string;
    title: string;
    content: string;
    image: string | null;
    date_event: string;
    location: string;
    organizer: string;
    price: number;
    created_at: string;
};

type comment = {
    id: number;
    post_id: number;
    hash_id: string;
    content: string;
    created_at: string;
};

const props = defineProps<{
    user: {
        id: number;
        hash_id: string;
        name: string;
        clean_name: string;
        role: string;
        created_at: string;
        posts_count: number;
        comments_count: number;
        events_count: number;
        collaboration_interests_count: number;
        private_post: boolean;
        private_comment: boolean;
        bio?: string;
    };
    posts: {
        data: post[];
        current_page: number;
        last_page: number;
    };
    comments: {
        data: comment[];
        current_page: number;
        last_page: number;
    };
    events: {
        data: event[];
        current_page: number;
        last_page: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [];

// Active tab state with session storage persistence
const ACTIVE_TAB_CACHE_KEY = 'userprofile_active_tab';
const savedActiveTab = sessionStorage.getItem(ACTIVE_TAB_CACHE_KEY) as 'posts' | 'comments' | 'events' | null;
const activeTabs = ref<'posts' | 'comments' | 'events'>(savedActiveTab || 'posts');

// Post filter
const POST_FILTER_CACHE_KEY = 'userprofile_post_filter_cache';
const postFilters = ['all', 'post', 'collaboration', 'volunteer'] as const;
type PostFilterType = (typeof postFilters)[number];

const savedPostFilter = sessionStorage.getItem(POST_FILTER_CACHE_KEY) as PostFilterType | null;
const activePostFilter = ref<PostFilterType>(savedPostFilter || 'all');

watch(activePostFilter, (newFilter) => {
    sessionStorage.setItem(POST_FILTER_CACHE_KEY, newFilter);

    // Check if data for this filter is already cached
    const cachedData = postsCacheMap.get(newFilter);
    if (cachedData) {
        posts.value = cachedData.posts;
        postPage.value = cachedData.page;
        postLastPage.value = cachedData.lastPage;
        return;
    }

    // Reset posts when filter changes and no cache
    posts.value = [];
    postPage.value = 0;
    postLastPage.value = 1;
    loadMorePosts();
});

function getPostFilterLabel(filter: PostFilterType) {
    const labels: Record<PostFilterType, string> = {
        all: 'Semua',
        post: 'Cerita',
        collaboration: 'Kolaborasi',
        volunteer: 'Relawan',
    };
    return labels[filter];
}

function getTypeStyles(type: string) {
    const styles: Record<string, { border: string; gradient: string; icon: string }> = {
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
        collaboration: 'Kolaborasi',
        volunteer: 'Relawan',
        post: 'Cerita',
    };
    return labels[type] || type;
}

function navigateToItem(item: post) {
    if (item.type === 'post') {
        router.visit(`/user/post/${item.hash_id}`);
    } else {
        router.visit(`/user/collaboration/${item.hash_id}`);
    }
}

// --- POSTS ---
const posts = ref<post[]>([]);
const postPage = ref(0);
const postLastPage = ref(1);
const postLoading = ref(false);

// Posts cache
const postsCacheMap = new Map<PostFilterType, { posts: post[]; page: number; lastPage: number }>();

// Comments loading state
const comments = ref<comment[]>([...props.comments.data]);
const commentPage = ref(props.comments.current_page);
const commentLastPage = ref(props.comments.last_page);
const commentLoading = ref(false);

// Events loading state
const events = ref<event[]>([...props.events.data]);
const eventPage = ref(props.events.current_page);
const eventLastPage = ref(props.events.last_page);
const eventLoading = ref(false);

// Events cache
const eventsCacheMap = new Map<string, { events: event[]; page: number; lastPage: number }>();

// Load more posts
const loadMorePosts = async () => {
    if (postLoading.value || postPage.value >= postLastPage.value) return;
    postLoading.value = true;

    try {
        const res = await axios.get(`/user/${props.user.hash_id}/${props.user.name.toLowerCase().replace(/\s+/g, '-')}/posts`, {
            params: {
                page: postPage.value + 1,
                filter: activePostFilter.value,
            },
            headers: { Accept: 'application/json' },
        });
        const newData = res.data;
        posts.value.push(...newData.data);
        postPage.value = newData.current_page;
        postLastPage.value = newData.last_page;

        // Update cache
        postsCacheMap.set(activePostFilter.value, {
            posts: [...posts.value],
            page: postPage.value,
            lastPage: postLastPage.value,
        });
    } finally {
        postLoading.value = false;
    }
};

// Load more comments
const loadMoreComments = async () => {
    if (commentLoading.value || commentPage.value >= commentLastPage.value) return;
    commentLoading.value = true;
    console.log('Loading more comments...');

    try {
        const res = await axios.get(`/user/${props.user.hash_id}/${props.user.name.toLowerCase().replace(/\s+/g, '-')}/comments`, {
            params: { page: commentPage.value + 1 },
            headers: { Accept: 'application/json' },
        });
        const newData = res.data;
        comments.value.push(...newData.data);
        commentPage.value = newData.current_page;
        commentLastPage.value = newData.last_page;
    } finally {
        commentLoading.value = false;
    }
};

// Load more events
const loadMoreEvents = async () => {
    if (eventLoading.value || eventPage.value >= eventLastPage.value) return;
    eventLoading.value = true;

    try {
        const res = await axios.get(`/user/${props.user.hash_id}/${props.user.name.toLowerCase().replace(/\s+/g, '-')}/events`, {
            params: { page: eventPage.value + 1 },
            headers: { Accept: 'application/json' },
        });
        const newData = res.data;
        events.value.push(...newData.data);
        eventPage.value = newData.current_page;
        eventLastPage.value = newData.last_page;

        // Update cache
        eventsCacheMap.set('events', {
            events: [...events.value],
            page: eventPage.value,
            lastPage: eventLastPage.value,
        });
    } finally {
        eventLoading.value = false;
    }
};

const postSentinel = ref<HTMLElement | null>(null);
const commentSentinel = ref<HTMLElement | null>(null);
const eventSentinel = ref<HTMLElement | null>(null);

let postObserver: IntersectionObserver | null = null;
let commentObserver: IntersectionObserver | null = null;
let eventObserver: IntersectionObserver | null = null;

// Function to setup observers
const setupObservers = () => {
    // Clean up existing observers
    if (postObserver) {
        postObserver.disconnect();
        postObserver = null;
    }
    if (commentObserver) {
        commentObserver.disconnect();
        commentObserver = null;
    }
    if (eventObserver) {
        eventObserver.disconnect();
        eventObserver = null;
    }

    // Create post observer
    postObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting && activeTabs.value === 'posts') {
                    loadMorePosts();
                }
            });
        },
        { rootMargin: '200px' },
    );

    // Create comment observer
    commentObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting && activeTabs.value === 'comments') {
                    loadMoreComments();
                }
            });
        },
        { rootMargin: '200px' },
    );

    // Create event observer
    eventObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting && activeTabs.value === 'events') {
                    loadMoreEvents();
                }
            });
        },
        { rootMargin: '200px' },
    );
};

// Function to observe current tab's sentinel
const observeCurrentTab = () => {
    nextTick(() => {
        if (activeTabs.value === 'posts' && postSentinel.value && postObserver) {
            postObserver.observe(postSentinel.value);
        } else if (activeTabs.value === 'comments' && commentSentinel.value && commentObserver) {
            commentObserver.observe(commentSentinel.value);
        } else if (activeTabs.value === 'events' && eventSentinel.value && eventObserver) {
            eventObserver.observe(eventSentinel.value);
        }
    });
};

// Watch for tab changes
watch(activeTabs, (newTab) => {
    // Save to session storage
    sessionStorage.setItem(ACTIVE_TAB_CACHE_KEY, newTab);

    // Unobserve all sentinels first
    if (postObserver && postSentinel.value) {
        postObserver.unobserve(postSentinel.value);
    }
    if (commentObserver && commentSentinel.value) {
        commentObserver.unobserve(commentSentinel.value);
    }
    if (eventObserver && eventSentinel.value) {
        eventObserver.unobserve(eventSentinel.value);
    }

    // Observe the current tab's sentinel
    observeCurrentTab();
});

function formatTime(date: string) {
    return dayjs(date).fromNow();
}

onMounted(() => {
    setupObservers();
    observeCurrentTab();
    // Load initial data for current filter
    if (posts.value.length === 0) {
        loadMorePosts();
    }
});

onBeforeUnmount(() => {
    if (postObserver) postObserver.disconnect();
    if (commentObserver) commentObserver.disconnect();
    if (eventObserver) eventObserver.disconnect();
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
    <Head :title="`${user.clean_name} - Profile`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster />
        <!-- Profile Header -->
        <div class="mb-8 rounded-lg border border-gray-200 bg-white p-8 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-col items-center gap-6">
                <!-- Avatar -->
                <div
                    class="flex h-20 w-20 items-center justify-center rounded-full bg-gray-200 text-2xl font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-200"
                >
                    {{ user.name.charAt(0).toUpperCase() }}
                </div>

                <!-- Name + Role + Bio -->
                <div class="w-full text-center">
                    <div class="flex flex-wrap items-center justify-center gap-2">
                        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ user.role == 'user' ? user.clean_name : user.name }}</h1>
                        <span
                            v-if="user.role === 'user'"
                            class="rounded-md bg-green-100 px-2 py-1 text-sm font-medium text-green-700 dark:bg-green-900 dark:text-green-300"
                        >
                            Mahasiswa
                        </span>
                        <span
                            v-if="user.role === 'moderator'"
                            class="rounded-md bg-blue-100 px-2 py-1 text-sm font-medium text-blue-700 dark:bg-blue-900 dark:text-blue-300"
                        >
                            Moderator
                        </span>
                        <span
                            v-if="user.role === 'community'"
                            class="rounded-md bg-yellow-100 px-2 py-1 text-sm font-medium text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300"
                        >
                            Komunitas
                        </span>
                    </div>
                    <div class="mt-2 flex items-center justify-center gap-1.5 text-sm text-gray-500">
                        <Clock class="h-4 w-4" />
                        <p>Bergabung {{ formatTime(user.created_at) }}</p>
                    </div>

                    <!-- Bio Section (Read-only) -->
                    <div v-if="user.bio" class="mx-auto mt-4 max-w-md">
                        <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-700/50">
                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ user.bio }}</p>
                        </div>
                    </div>
                </div>

                <!-- Stats -->
                <div class="flex flex-wrap gap-8">
                    <div class="text-center">
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ user.posts_count }}</p>
                        <p class="text-sm text-gray-500">Postingan</p>
                    </div>
                    <div class="h-12 w-px bg-gray-200 dark:bg-gray-700"></div>
                    <div class="text-center">
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ user.events_count }}</p>
                        <p class="text-sm text-gray-500">Event</p>
                    </div>
                    <div class="h-12 w-px bg-gray-200 dark:bg-gray-700"></div>
                    <div class="text-center">
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ user.collaboration_interests_count }}</p>
                        <p class="text-sm text-gray-500">Kolaborasi / Relawan Diminati</p>
                    </div>
                    <div class="h-12 w-px bg-gray-200 dark:bg-gray-700"></div>
                    <div class="text-center">
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ user.comments_count }}</p>
                        <p class="text-sm text-gray-500">Komentar</p>
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
                        @click="activeTabs = 'posts'"
                        :class="
                            activeTabs == 'posts'
                                ? 'border-gray-900 text-gray-900 dark:border-white dark:text-white'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
                        "
                    >
                        Postingan
                    </button>
                    <button
                        class="border-b-2 px-6 py-3 text-sm font-medium transition-colors"
                        :class="
                            activeTabs == 'comments'
                                ? 'border-gray-900 text-gray-900 dark:border-white dark:text-white'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
                        "
                        @click="activeTabs = 'comments'"
                    >
                        Komentar
                    </button>
                    <button
                        class="border-b-2 px-6 py-3 text-sm font-medium transition-colors"
                        :class="
                            activeTabs == 'events'
                                ? 'border-gray-900 text-gray-900 dark:border-white dark:text-white'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
                        "
                        @click="activeTabs = 'events'"
                    >
                        Event
                    </button>
                </div>
            </div>
        </div>

        <div class="px-8">
            <!-- POSTS -->
            <div v-if="activeTabs == 'posts'">
                <!-- Filter Tabs -->
                <div
                    class="sticky top-0 z-30 -mx-8 mb-4 bg-slate-50/95 px-8 py-3 backdrop-blur supports-[backdrop-filter]:bg-slate-50/60 dark:bg-slate-950/95 dark:supports-[backdrop-filter]:bg-slate-950/60"
                >
                    <div class="flex gap-2 overflow-x-auto pb-1">
                        <Button
                            v-for="filter in postFilters"
                            :key="filter"
                            :variant="activePostFilter === filter ? 'default' : 'outline'"
                            size="sm"
                            @click="activePostFilter = filter"
                            class="shrink-0"
                        >
                            {{ getPostFilterLabel(filter) }}
                        </Button>
                    </div>
                </div>

                <div
                    v-if="user.private_post == false"
                    v-for="post in posts"
                    :key="post.type + '-' + post.id"
                    :class="[
                        'mb-4 cursor-pointer overflow-hidden rounded-2xl border-y border-r border-l-4 bg-white shadow transition-shadow hover:shadow-lg dark:border-y-gray-700 dark:border-r-gray-700 dark:bg-gray-800',
                        getTypeStyles(post.type).border,
                    ]"
                    @click="navigateToItem(post)"
                >
                    <div class="p-5">
                        <div class="mb-3 flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 text-sm font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-200"
                            >
                                {{ user.name.charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ user.clean_name }}</p>
                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                    <!-- Type Badge -->
                                    <div
                                        class="flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="{
                                            'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300': post.type === 'collaboration',
                                            'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300': post.type === 'volunteer',
                                            'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300': post.type === 'post',
                                        }"
                                    >
                                        <Workflow v-if="post.type === 'collaboration'" class="h-3 w-3" />
                                        <HandHeart v-if="post.type === 'volunteer'" class="h-3 w-3" />
                                        <MessageSquare v-if="post.type === 'post'" class="h-3 w-3" />
                                        <span>{{ getTypeLabel(post.type) }}</span>
                                    </div>
                                    <span class="text-gray-400">•</span>
                                    <Clock class="h-3 w-3"></Clock>
                                    <p>dibuat {{ formatTime(post.created_at) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Title for collaboration/volunteer -->
                        <div v-if="post.title" class="mb-2 text-xl font-bold text-gray-900 dark:text-white">
                            {{ post.title }}
                        </div>

                        <!-- Image -->
                        <img
                            v-if="post.image"
                            :src="post.image_url"
                            class="mb-3 max-h-[500px] w-auto max-w-full rounded-lg object-cover"
                            alt="post image"
                        />

                        <!-- Content -->
                        <p class="whitespace-pre-wrap text-gray-700 dark:text-gray-300">{{ post.content }}</p>

                        <!-- Collaboration/Volunteer details -->
                        <div v-if="post.type === 'collaboration' || post.type === 'volunteer'" class="mt-4 flex flex-wrap gap-4 text-sm">
                            <div v-if="post.location" class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                <MapPin class="h-4 w-4" />
                                <span>{{ post.location }}</span>
                            </div>
                            <div v-if="post.participants_needed" class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                <Users class="h-4 w-4" />
                                <span>Dibutuhkan: {{ post.participants_needed }} orang</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-center py-8" v-else-if="user.private_post == true">
                    <div class="flex items-center gap-2 text-gray-500">
                        <Lock class="h-5 w-5"></Lock>
                        <p>Private</p>
                    </div>
                </div>
                <div v-if="postPage < postLastPage" ref="postSentinel" class="h-1"></div>
                <div v-if="postLoading" class="flex justify-center py-8 text-sm text-gray-500">Loading posts...</div>
                <div v-else-if="postPage >= postLastPage && posts.length > 0" class="py-8 text-center text-sm text-gray-500">
                    No more posts to load
                </div>
            </div>

            <!-- COMMENTS -->
            <div v-else-if="activeTabs == 'comments'">
                <div
                    v-if="user.private_comment == false"
                    v-for="comment in comments"
                    :key="comment.id"
                    @click="$inertia.visit(route('user.post.details', comment.hash_id))"
                    class="mb-4 cursor-pointer rounded-lg border border-gray-200 bg-white p-5 transition-shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
                >
                    <div class="mb-3 flex items-center gap-3">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-200 text-sm font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-200"
                        >
                            {{ user.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ user.clean_name }}</p>
                            <div class="flex items-center gap-1 text-xs text-gray-500">
                                <Clock class="h-3 w-3" />
                                <p>dibuat {{ formatTime(comment.created_at) }}</p>
                            </div>
                        </div>
                    </div>
                    <p class="text-gray-700 dark:text-gray-300">{{ comment.content }}</p>
                </div>
                <div class="flex justify-center py-8" v-else-if="user.private_comment == true">
                    <div class="flex items-center gap-2 text-gray-500">
                        <Lock class="h-5 w-5"></Lock>
                        <p>Private</p>
                    </div>
                </div>
                <div v-if="commentPage < commentLastPage" ref="commentSentinel" class="h-1"></div>
                <div v-if="commentLoading" class="flex justify-center py-8 text-sm text-gray-500">Loading comments...</div>
                <div v-else-if="commentPage >= commentLastPage && comments.length > 0" class="py-8 text-center text-sm text-gray-500">
                    No more comments to load
                </div>
            </div>

            <!-- EVENTS -->
            <div v-else-if="activeTabs == 'events'">
                <div
                    v-for="event in events"
                    :key="event.id"
                    @click="$inertia.visit(`/user/event/${event.hash_id}/${event.title.toLowerCase().replace(/\s+/g, '-')}`)"
                    class="mb-4 cursor-pointer rounded-lg border border-gray-200 bg-white p-5 transition-shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
                >
                    <div class="mb-3 flex items-center gap-3">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-200 text-sm font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-200"
                        >
                            {{ user.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ user.clean_name }}</p>
                            <div class="flex items-center gap-1 text-xs text-gray-500">
                                <Clock class="h-3 w-3" />
                                <p>dibuat {{ formatTime(event.created_at) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Event Title -->
                    <h3 class="mb-2 text-xl font-bold text-gray-900 dark:text-white">{{ event.title }}</h3>

                    <!-- Event Image -->
                    <img
                        v-if="event.image"
                        :src="event.image_url"
                        class="mb-3 max-h-[500px] w-auto max-w-full rounded-lg object-cover"
                        alt="event image"
                    />

                    <!-- Event Content -->
                    <p class="mb-3 line-clamp-3 whitespace-pre-wrap text-gray-700 dark:text-gray-300">{{ event.content }}</p>

                    <!-- Event Details -->
                    <div class="flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-400">
                        <div class="flex items-center gap-1">
                            <Clock class="h-4 w-4" />
                            <span>{{ dayjs(event.date_event).format('DD MMM YYYY') }}</span>
                        </div>
                        <div v-if="event.location" class="flex items-center gap-1">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>{{ event.location }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <Building2 class="h-4 w-4" />
                            <span>{{ event.organizer }}</span>
                        </div>
                        <div v-if="event.price > 0" class="flex items-center gap-1 font-medium text-green-600 dark:text-green-400">
                            <span>Rp {{ event.price.toLocaleString('id-ID') }}</span>
                        </div>
                        <div v-else class="flex items-center gap-1 font-medium text-green-600 dark:text-green-400">
                            <span>Gratis</span>
                        </div>
                    </div>
                </div>
                <div v-if="eventPage < eventLastPage" ref="eventSentinel" class="h-1"></div>
                <div v-if="eventLoading" class="flex justify-center py-8 text-sm text-gray-500">Loading events...</div>
                <div v-else-if="eventPage >= eventLastPage && events.length > 0" class="py-8 text-center text-sm text-gray-500">
                    No more events to load
                </div>
                <div v-else-if="events.length === 0 && !eventLoading" class="py-8 text-center text-sm text-gray-500">Belum ada event</div>
            </div>
        </div>
    </AppLayout>
</template>
