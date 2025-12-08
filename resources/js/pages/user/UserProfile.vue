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
    <Head :title="`${user.name} - Profile`" />

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
                        {{ user.name.charAt(0).toUpperCase() }}
                    </div>
                    <div
                        class="absolute -right-1 -bottom-1 flex h-6 w-6 items-center justify-center rounded-full border-2 border-slate-900 bg-emerald-400 shadow-sm"
                    >
                        <div class="h-2 w-2 rounded-full bg-emerald-600"></div>
                    </div>
                </div>

                <!-- Name + Join -->
                <div class="text-center">
                    <div class="flex flex-wrap gap-2">
                        <h1 class="mb-2 text-3xl font-bold text-black dark:text-white">{{ user.name }}</h1>
                        <div v-if="user.role === 'moderator'" class="flex h-fit items-center rounded bg-blue-600 p-1 font-bold text-white">
                            Moderator
                        </div>
                        <div v-if="user.role === 'community'" class="flex h-fit items-center rounded bg-yellow-500 p-1 font-bold text-white shadow">
                            Komunitas
                        </div>
                    </div>
                    <div class="flex items-center justify-center gap-2 text-slate-300">
                        <Clock class="h-4 w-4" />
                        <p class="text-sm font-medium text-gray-500">Bergabung {{ formatTime(user.created_at) }}</p>
                    </div>
                </div>

                <!-- Stats -->
                <div class="flex flex-wrap justify-center gap-4">
                    <div
                        class="min-w-[100px] rounded-xl border border-white/10 bg-white/5 p-4 text-center shadow-lg backdrop-blur-sm transition hover:bg-white/10"
                    >
                        <p class="mb-1 text-2xl font-bold text-black dark:text-white">{{ user.posts_count }}</p>
                        <p class="text-sm font-medium text-slate-500">Postingan</p>
                    </div>
                    <div
                        class="min-w-[100px] rounded-xl border border-white/10 bg-white/5 p-4 text-center shadow-lg backdrop-blur-sm transition hover:bg-white/10"
                    >
                        <p class="mb-1 text-2xl font-bold text-black dark:text-white">{{ user.comments_count }}</p>
                        <p class="text-sm font-medium text-slate-500">Komentar</p>
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
                        @click="activeTabs = 'posts'"
                        :class="
                            activeTabs == 'posts'
                                ? 'scale-105 transform bg-white text-blue-600 shadow-lg dark:bg-gray-700 dark:text-blue-400'
                                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200'
                        "
                    >
                        <span class="relative z-10">Postingan</span>
                    </button>
                    <button
                        class="relative overflow-hidden rounded-lg px-6 py-3 text-sm font-semibold transition-all duration-300"
                        :class="
                            activeTabs == 'comments'
                                ? 'scale-105 transform bg-white text-blue-600 shadow-lg dark:bg-gray-700 dark:text-blue-400'
                                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200'
                        "
                        @click="activeTabs = 'comments'"
                    >
                        <span class="relative z-10">Komentar</span>
                    </button>

                    <button
                        class="relative overflow-hidden rounded-lg px-6 py-3 text-sm font-semibold transition-all duration-300"
                        :class="
                            activeTabs == 'inkrip'
                                ? 'scale-105 transform bg-white text-blue-600 shadow-lg dark:bg-gray-700 dark:text-blue-400'
                                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200'
                        "
                        @click="activeTabs = 'inkrip'"
                    >
                        <span class="relative z-10">Inkrip</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="p-8">
            <!-- POSTS -->
            <div v-if="activeTabs == 'posts'">
                <div
                    v-if="user.private_post == false"
                    v-for="post in posts"
                    :key="post.id"
                    class="group transform p-4 transition-all hover:-translate-y-1 hover:scale-[1.02]"
                >
                    <div
                        class="dakr:hover:shadow-gray-700 flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-6 shadow hover:shadow-lg dark:border-gray-700 dark:bg-gray-800"
                    >
                        <div>
                            <p class="font-bold">{{ user.name }}</p>
                            <div class="flex flex-row items-center gap-1 text-sm text-gray-500">
                                <Clock class="h-3 w-3"></Clock>
                                <p class="text-gray-400">dibuat {{ formatTime(post.created_at) }}</p>
                            </div>
                        </div>

                        <p>{{ post.content }}</p>
                        <img v-if="post.image" :src="`/storage/${post.image}`" class="h-80 w-80 rounded object-contain" alt="post image" />
                        <div class="-mx-6 mt-4 -mb-6 flex items-center justify-start border-t px-6 py-3 dark:border-gray-700">
                            <div class="flex gap-4">
                                <Button
                                    @click="$inertia.visit(route('user.post.details', post.hash_id))"
                                    class="flex cursor-pointer hover:text-blue-600"
                                    ><MessageCircle /> Komentar</Button
                                >

                                <Button @click="copyPostUrl(post.hash_id)" class="flex cursor-pointer hover:text-blue-600"><Share2 /> Bagikan</Button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-row justify-center p-4" v-else-if="user.private_post == true">
                    <Lock></Lock>
                    <p>Private</p>
                </div>
                <div v-if="postPage < postLastPage" ref="postSentinel" class="h-1"></div>
                <div v-if="postLoading" class="p-4 text-center">Loading posts...</div>
                <div v-else-if="postPage >= postLastPage && posts.length > 0" class="p-4 text-center text-gray-500">No more posts to load</div>
            </div>

            <!-- COMMENTS -->
            <div v-else-if="activeTabs == 'comments'">
                <div
                    v-if="user.private_comment == false"
                    v-for="comment in comments"
                    :key="comment.id"
                    @click="$inertia.visit(route('user.post.details', comment.hash_id))"
                    class="p-4"
                >
                    <div
                        class="flex transform flex-col rounded-2xl bg-white p-4 shadow transition-all hover:-translate-y-1 hover:scale-[1.02] hover:shadow-lg dark:bg-gray-800 dark:hover:shadow-gray-700"
                    >
                        <p class="font-bold">{{ user.name }}</p>
                        <div class="flex items-center gap-1 text-sm text-gray-500">
                            <Clock class="h-3 w-3" />
                            <p class="text-gray-400">dibuat {{ formatTime(comment.created_at) }}</p>
                        </div>
                        <p>{{ comment.content }}</p>
                    </div>
                </div>
                <div class="flex flex-row justify-center p-4" v-else-if="user.private_comment == true">
                    <Lock></Lock>
                    <p>Private</p>
                </div>
                <div v-if="commentPage < commentLastPage" ref="commentSentinel" class="h-1"></div>
                <div v-if="commentLoading" class="p-4 text-center">Loading comments...</div>
                <div v-else-if="commentPage >= commentLastPage && comments.length > 0" class="p-4 text-center text-gray-500">
                    No more comments to load
                </div>
            </div>

            <!-- INKRIP -->
            <div v-else-if="activeTabs == 'inkrip'">
                <div class="p-8 text-center text-gray-500">Inkrip content coming soon...</div>
            </div>
        </div>
    </AppLayout>
</template>
