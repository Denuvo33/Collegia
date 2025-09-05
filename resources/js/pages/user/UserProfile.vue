<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useRemember } from '@inertiajs/vue3';
import axios from 'axios';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import relativeTime from 'dayjs/plugin/relativeTime';
import { Lock, MessageCircle } from 'lucide-vue-next';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

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
</script>

<template>
    <Head :title="`${user.name} - Profile`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col p-4">
            <h1 class="text-xl font-bold">{{ user.name }}</h1>
            <div class="flex flex-wrap gap-5">
                <div class="flex flex-col rounded border p-2 shadow">
                    <p>{{ user.posts_count }}</p>
                    <p>Postingaan</p>
                </div>
                <div class="flex flex-col rounded border p-2">
                    <p>0</p>
                    <p>Inkrip</p>
                </div>
            </div>
            <p>Bergabung {{ formatTime(user.created_at) }}</p>
        </div>

        <div class="flex justify-center gap-8">
            <button class="text-lg" @click="activeTabs = 'posts'" :class="activeTabs == 'posts' ? 'border-b-2 border-black dark:border-white' : ''">
                Post
            </button>
            <button
                @click="activeTabs = 'comments'"
                class="text-lg"
                :class="activeTabs == 'comments' ? 'border-b-2 border-black dark:border-white' : ''"
            >
                Comment
            </button>
            <button @click="activeTabs = 'inkrip'" class="text-lg" :class="activeTabs == 'inkrip' ? 'border-b-2 border-black dark:border-white' : ''">
                Inkrip
            </button>
        </div>

        <hr />

        <div class="p-1">
            <!-- POSTS -->
            <div v-if="activeTabs == 'posts'">
                <div
                    v-if="user.private_post == false"
                    v-for="(post, index) in posts"
                    :key="post.id"
                    @click="$inertia.visit(route('user.post.details', post.hash_id))"
                    class="p-4"
                >
                    <div class="flex flex-col gap-4 rounded bg-white p-4 shadow dark:bg-black">
                        <p class="font-bold">{{ user.name }}</p>
                        <hr />
                        <p>{{ post.content }}</p>
                        <img v-if="post.image" :src="`/storage/${post.image}`" class="h-80 w-80 object-contain" alt="post image" />
                        <p class="text-gray-400">dibuat {{ formatTime(post.created_at) }}</p>
                        <MessageCircle class="cursor-pointer" />
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
                    <div class="flex flex-col rounded bg-white p-4 shadow dark:bg-black">
                        <p class="font-bold">{{ user.name }}</p>
                        <hr />
                        <p>{{ comment.content }}</p>
                        <p class="text-gray-400">dibuat {{ formatTime(comment.created_at) }}</p>
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
