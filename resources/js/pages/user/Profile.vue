<script setup lang="ts">
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Toaster } from '@/components/ui/sonner';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage, useRemember } from '@inertiajs/vue3';
import axios from 'axios';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import relativeTime from 'dayjs/plugin/relativeTime';
import { EllipsisVertical, MessageCircle } from 'lucide-vue-next';
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
    };
}>();

const activatedTabs = useRemember(ref<'post' | 'comment' | 'inkrip'>('post'), 'activeTabs');

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
    console.log('Loading more posts...');

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
    console.log('Loading more comments...');

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
                if (entry.isIntersecting && activatedTabs.value === 'post') {
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
                if (entry.isIntersecting && activatedTabs.value === 'comment') {
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
        if (activatedTabs.value === 'post' && postSentinel.value && postObserver) {
            postObserver.observe(postSentinel.value);
        } else if (activatedTabs.value === 'comment' && commentSentinel.value && commentObserver) {
            commentObserver.observe(commentSentinel.value);
        }
    });
};

// Watch for tab changes
watch(activatedTabs, () => {
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

function deletePost(id: number) {
    return router.delete(route('user.post.delete', id), {
        onError: (errors) => {
            console.error('error: ', errors);
            toast.error('Terjadi kesalahan saat menghapus.');
        },
        onSuccess: () => {
            // Remove the deleted post from the local state
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
</script>

<template>
    <Head title="Profile" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster />

        <div class="flex flex-col items-center gap-4">
            <h1 class="text-xl font-bold">{{ data.name }}</h1>
            <div class="flex flex-wrap gap-5">
                <div class="flex flex-col rounded border p-2 shadow">
                    <p>{{ posts_count }}</p>
                    <p>Postingan</p>
                </div>
                <div class="flex flex-col rounded border p-2">
                    <p>0</p>
                    <p>Inkrip</p>
                </div>
            </div>
            <p class="text-gray-400">Bergabung {{ formatTime(data.created_at) }}</p>
        </div>
        <hr />

        <!-- Selection -->
        <div class="flex flex-row justify-center gap-8">
            <button
                class="text-xl"
                @click="activatedTabs = 'post'"
                :class="activatedTabs == 'post' ? 'border-b-2 border-black dark:border-white' : ''"
            >
                Post
            </button>
            <button
                class="text-xl"
                :class="activatedTabs == 'comment' ? 'border-b-2 border-black dark:border-white' : ''"
                @click="activatedTabs = 'comment'"
            >
                Komentar
            </button>
            <button
                class="text-xl"
                :class="activatedTabs == 'inkrip' ? 'border-b-2 border-black dark:border-white' : ''"
                @click="activatedTabs = 'inkrip'"
            >
                Inkrip
            </button>
        </div>

        <!-- Posts Tab -->
        <div class="flex flex-col p-2" v-if="activatedTabs == 'post'">
            <div v-for="post in sortList(posts)" :key="post.id" class="p-2">
                <div class="space-y-2 rounded bg-white p-4 shadow dark:bg-black">
                    <div class="flex">
                        <h1 class="cursor-pointer font-bold" @click="$inertia.visit(`/user/post/${post.hash_id}`)">
                            {{ data.name }}
                        </h1>
                        <div class="ml-auto">
                            <DropdownMenu>
                                <DropdownMenuTrigger>
                                    <EllipsisVertical></EllipsisVertical>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent>
                                    <DropdownMenuItem @click="deletePost(post.id)">Hapus</DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>

                    <h1>{{ post.content }}</h1>
                    <img :src="`/storage/${post.image}`" v-if="post.image" alt="post image" class="h-80 w-80 object-contain" />
                    <p class="text-gray-400">dibuat {{ formatTime(post.created_at) }}</p>
                    <MessageCircle class="cursor-pointer" @click="$inertia.visit(route('user.post.details', post.hash_id))"></MessageCircle>
                </div>
            </div>

            <!-- Post Sentinel -->
            <div v-if="postPage < postLastPage" ref="postSentinel" class="h-1"></div>

            <!-- Post Loading/End States -->
            <div v-if="postLoading" class="flex justify-center p-4">Loading more posts...</div>
            <div v-else-if="postPage >= postLastPage && posts.length > 0" class="flex justify-center p-4 text-gray-500">No more posts to load</div>
            <div v-else-if="posts.length == 0" class="flex justify-center">Tidak ada postingan</div>
        </div>

        <!-- Comments Tab -->
        <div class="flex flex-col p-2" v-else-if="activatedTabs == 'comment'">
            <div
                v-for="comment in sortList(comments)"
                :key="comment.id"
                class="p-2"
                @click="$inertia.visit(route('user.post.details', comment.hash_id))"
            >
                <div class="rounded bg-white p-4 shadow dark:bg-black">
                    <h1 class="font-bold">{{ data.name }}</h1>
                    <h1>{{ comment.content }}</h1>
                    <p class="text-gray-400">dibuat {{ formatTime(comment.created_at) }}</p>
                </div>
            </div>

            <!-- Comment Sentinel -->
            <div v-if="commentPage < commentLastPage" ref="commentSentinel" class="h-1"></div>

            <!-- Comment Loading/End States -->
            <div v-if="commentLoading" class="flex justify-center p-4">Loading more comments...</div>
            <div v-else-if="commentPage >= commentLastPage && comments.length > 0" class="flex justify-center p-4 text-gray-500">
                No more comments to load
            </div>
            <div v-else-if="comments.length == 0" class="flex justify-center">Tidak ada Komentar</div>
        </div>

        <!-- Inkrip Tab -->
        <div v-else-if="activatedTabs == 'inkrip'" class="p-8 text-center text-gray-500">Inkrip content coming soon...</div>
    </AppLayout>
</template>
