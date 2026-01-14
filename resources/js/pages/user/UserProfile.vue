<script setup lang="ts">
import { Toaster } from '@/components/ui/sonner';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useRemember } from '@inertiajs/vue3';
import axios from 'axios';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import relativeTime from 'dayjs/plugin/relativeTime';
import { Clock, Lock, MessageCircle, Share2 } from 'lucide-vue-next';
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
        private_post: boolean;
        private_comment: boolean;
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
}>();

const breadcrumbs: BreadcrumbItem[] = [];

const activeTabs = useRemember(ref<'posts' | 'comments' | 'inkrip'>('posts'), 'activeTabs');

// --- POSTS ---
const posts = ref<post[]>([...props.posts.data]);
const postPage = ref(props.posts.current_page);
const postLastPage = ref(props.posts.last_page);
const postLoading = ref(false);

// Comments loading state
const comments = ref<comment[]>([...props.comments.data]);
const commentPage = ref(props.comments.current_page);
const commentLastPage = ref(props.comments.last_page);
const commentLoading = ref(false);

// Load more posts
const loadMorePosts = async () => {
    if (postLoading.value || postPage.value >= postLastPage.value) return;
    postLoading.value = true;
    console.log('Loading more posts...');

    try {
        const res = await axios.get(`/user/${props.user.hash_id}/${props.user.name.toLowerCase().replace(/\s+/g, '-')}/posts`, {
            params: { page: postPage.value + 1 },
            headers: { Accept: 'application/json' },
        });
        const newData = res.data;
        posts.value.push(...newData.data);
        postPage.value = newData.current_page;
        postLastPage.value = newData.last_page;
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

const postSentinel = ref<HTMLElement | null>(null);
const commentSentinel = ref<HTMLElement | null>(null);

let postObserver: IntersectionObserver | null = null;
let commentObserver: IntersectionObserver | null = null;

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
};

// Function to observe current tab's sentinel
const observeCurrentTab = () => {
    nextTick(() => {
        if (activeTabs.value === 'posts' && postSentinel.value && postObserver) {
            postObserver.observe(postSentinel.value);
        } else if (activeTabs.value === 'comments' && commentSentinel.value && commentObserver) {
            commentObserver.observe(commentSentinel.value);
        }
    });
};

// Watch for tab changes
watch(activeTabs, (newTab) => {
    // Unobserve all sentinels first
    if (postObserver && postSentinel.value) {
        postObserver.unobserve(postSentinel.value);
    }
    if (commentObserver && commentSentinel.value) {
        commentObserver.unobserve(commentSentinel.value);
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

                <!-- Name + Join -->
                <div class="text-center">
                    <div class="flex flex-wrap items-center justify-center gap-2">
                        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ user.role == 'user' ? user.clean_name : user.name }}</h1>
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
                </div>

                <!-- Stats -->
                <div class="flex gap-8">
                    <div class="text-center">
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ user.posts_count }}</p>
                        <p class="text-sm text-gray-500">Postingan</p>
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
                            activeTabs == 'inkrip'
                                ? 'border-gray-900 text-gray-900 dark:border-white dark:text-white'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
                        "
                        @click="activeTabs = 'inkrip'"
                    >
                        Inkrip
                    </button>
                </div>
            </div>
        </div>

        <div class="px-8">
            <!-- POSTS -->
            <div v-if="activeTabs == 'posts'">
                <div
                    v-if="user.private_post == false"
                    v-for="post in posts"
                    :key="post.id"
                    class="mb-4 rounded-lg border border-gray-200 bg-white transition-shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
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
                                <div class="flex items-center gap-1 text-xs text-gray-500">
                                    <Clock class="h-3 w-3"></Clock>
                                    <p>dibuat {{ formatTime(post.created_at) }}</p>
                                </div>
                            </div>
                        </div>

                        <p class="mb-3 text-gray-700 dark:text-gray-300">{{ post.content }}</p>
                        <img v-if="post.image" :src="`/storage/${post.image}`" class="max-h-80 max-w-80 rounded-lg object-contain" alt="post image" />
                    </div>
                    <div class="border-t border-gray-100 px-5 py-3 dark:border-gray-700">
                        <div class="flex gap-4">
                            <Button
                                @click="$inertia.visit(route('user.post.details', post.hash_id))"
                                class="flex items-center gap-1.5 text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200"
                                ><MessageCircle class="h-4 w-4" /> Komentar</Button
                            >

                            <Button
                                @click="copyPostUrl(post.hash_id)"
                                class="flex items-center gap-1.5 text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200"
                                ><Share2 class="h-4 w-4" /> Bagikan</Button
                            >
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

            <!-- INKRIP -->
            <div v-else-if="activeTabs == 'inkrip'">
                <div class="py-20 text-center">
                    <h2 class="mb-2 text-xl font-semibold text-gray-900 dark:text-white">Segera Hadir!</h2>
                    <p class="text-sm text-gray-500">Fitur Inkrip sedang dalam tahap pengembangan.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
