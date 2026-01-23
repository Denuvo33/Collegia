<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Toaster } from '@/components/ui/sonner';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm, usePage, useRemember } from '@inertiajs/vue3';
import axios from 'axios';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import relativeTime from 'dayjs/plugin/relativeTime';
import {
    Calendar,
    Clock,
    EllipsisVertical,
    Eye,
    Gem,
    HandHeart,
    MapPin,
    MessageCircle,
    MessageSquare,
    Pencil,
    Share2,
    Tag,
    Workflow,
} from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
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
    type: 'post' | 'collaboration' | 'volunteer';
    title?: string;
    description?: string;
    location?: string;
    start_date?: string;
    end_date?: string;
    participants_needed?: number;
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
    collaborations_count: number;
    data: {
        id: number;
        name: string;
        clean_name: string;
        created_at: string;
        role: string;
        bio?: string;
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

// Post filter
const POST_FILTER_CACHE_KEY = 'profile_post_filter_cache';
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
        return; // Use cached data, no need to load
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

// Bio editing
const bioDialog = ref(false);
const bioForm = useForm({
    bio: props.data.bio || '',
});

const bioWordCount = computed(() => {
    return bioForm.bio
        ? bioForm.bio
              .trim()
              .split(/\s+/)
              .filter((word) => word.length > 0).length
        : 0;
});

function saveBio() {
    bioForm.patch(route('user.profile.bio.update'), {
        preserveScroll: true,
        onSuccess: () => {
            bioDialog.value = false;
            toast.success('Bio berhasil diperbarui!');
        },
        onError: () => {
            toast.error('Gagal memperbarui bio.');
        },
    });
}

function clearBio() {
    bioForm.bio = '';
    saveBio();
}

function openBioDialog() {
    bioForm.bio = props.data.bio || '';
    bioDialog.value = true;
}

// Posts state
const posts = ref<Post[]>([]);
const postPage = ref(0);
const postLastPage = ref(1);
const postLoading = ref(false);

// Posts cache: Map<filterType, {posts, page, lastPage}>
const postsCacheMap = new Map<PostFilterType, { posts: Post[]; page: number; lastPage: number }>();
// Don't pre-cache initial data - let it load from API with proper filter

// Comments state
const comments = ref<Comment[]>([...props.data.comments.data]);
const commentPage = ref(props.data.comments.current_page);
const commentLastPage = ref(props.data.comments.last_page);
const commentLoading = ref(false);

// Comments cache flag
const commentsCached = ref(false);

// Load more posts
const loadMorePosts = async () => {
    if (postLoading.value || postPage.value >= postLastPage.value) return;
    postLoading.value = true;

    try {
        const res = await axios.get('/user/profile/posts', {
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

        // Update cache for current filter
        postsCacheMap.set(activePostFilter.value, {
            posts: [...posts.value],
            page: postPage.value,
            lastPage: postLastPage.value,
        });
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

    // If already cached and on first page, skip loading
    if (commentsCached.value && commentPage.value === props.data.comments.current_page) {
        return;
    }

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
        commentsCached.value = true;
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

function deletePost(hashId: string) {
    return router.delete(route('user.post.delete', hashId), {
        onError: (errors) => {
            console.error('error: ', errors);
            toast.error('Gagal menghapus post');
        },
        onSuccess: () => {
            posts.value = posts.value.filter((post) => post.hash_id !== hashId);
            // Clear cache since data changed
            postsCacheMap.clear();
            // Re-cache current filter
            postsCacheMap.set(activePostFilter.value, {
                posts: [...posts.value],
                page: postPage.value,
                lastPage: postLastPage.value,
            });
            toast.success('Post berhasil dihapus');
        },
    });
}

function deleteCollaboration(hashId: string) {
    return router.delete(route('user.collaboration.delete', hashId), {
        onError: (errors) => {
            console.error('error: ', errors);
            toast.error('Gagal menghapus');
        },
        onSuccess: () => {
            posts.value = posts.value.filter((post) => post.hash_id !== hashId);
            // Clear cache since data changed
            postsCacheMap.clear();
            // Re-cache current filter
            postsCacheMap.set(activePostFilter.value, {
                posts: [...posts.value],
                page: postPage.value,
                lastPage: postLastPage.value,
            });
            toast.success('Berhasil dihapus');
        },
    });
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

function navigateToItem(item: Post) {
    if (item.type === 'post') {
        router.visit(`/user/post/${item.hash_id}`);
    } else {
        router.visit(`/user/collaboration/${item.hash_id}`);
    }
}

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
    // Load initial data for current filter
    if (posts.value.length === 0) {
        loadMorePosts();
    }
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
const deleteEvent = (hashId: string) => {
    router.delete(route('user.delete.event', hashId), {
        onSuccess: () => {
            // Remove event from the list
            props.data.events = props.data.events.filter((e) => e.hash_id !== hashId);
            toast.success('Event berhasil dihapus');
        },
        onError: (errors) => {
            console.error('Failed to delete event:', errors);
            toast.error('Gagal menghapus event');
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

                <!-- Name + Role + Bio -->
                <div class="w-full text-center">
                    <div class="flex flex-wrap items-center justify-center gap-2">
                        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ data.role == 'user' ? data.clean_name : data.name }}</h1>
                        <span
                            v-if="data.role === 'user'"
                            class="rounded-md bg-green-100 px-2 py-1 text-sm font-medium text-green-700 dark:bg-green-900 dark:text-green-300"
                        >
                            Mahasiswa
                        </span>
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

                    <!-- Bio Section -->
                    <div class="mx-auto mt-4 max-w-md">
                        <div v-if="data.bio" class="group relative rounded-lg bg-gray-50 p-3 dark:bg-gray-700/50">
                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ data.bio }}</p>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="absolute top-1 right-1 h-6 w-6 opacity-0 transition-opacity group-hover:opacity-100"
                                @click="openBioDialog"
                            >
                                <Pencil class="h-3 w-3" />
                            </Button>
                        </div>
                        <Button v-else variant="outline" size="sm" class="mt-2" @click="openBioDialog">
                            <Pencil class="mr-2 h-3 w-3" />
                            Tambah Bio
                        </Button>
                    </div>
                </div>

                <!-- Stats -->
                <div class="flex gap-8">
                    <div class="text-center">
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ posts_count }}</p>
                        <p class="text-sm text-gray-500">Cerita</p>
                    </div>
                    <div class="h-12 w-px bg-gray-200 dark:bg-gray-700"></div>
                    <div class="text-center">
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ collaborations_count }}</p>
                        <p class="text-sm text-gray-500">Kolaborasi/Relawan</p>
                    </div>
                    <div class="h-12 w-px bg-gray-200 dark:bg-gray-700"></div>
                    <div class="text-center">
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ data.events.length }}</p>
                        <p class="text-sm text-gray-500">Event</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bio Edit Dialog -->
        <Dialog v-model:open="bioDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Edit Bio</DialogTitle>
                    <DialogDescription>Tulis bio Anda (maksimal 20 kata)</DialogDescription>
                </DialogHeader>
                <div class="space-y-4 py-4">
                    <Textarea v-model="bioForm.bio" placeholder="Tulis bio Anda..." class="min-h-[100px]" rows="4" />
                    <div class="flex items-center justify-between text-sm">
                        <span :class="bioWordCount > 20 ? 'text-red-500' : 'text-gray-500'">{{ bioWordCount }} / 20 kata</span>
                    </div>
                </div>
                <DialogFooter class="gap-2">
                    <Button v-if="data.bio" variant="outline" @click="clearBio">Hapus Bio</Button>
                    <Button variant="outline" @click="bioDialog = false">Batal</Button>
                    <Button @click="saveBio" :disabled="bioWordCount > 20 || bioForm.processing">Simpan</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

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
                </div>
            </div>
        </div>

        <!-- Posts Tab -->
        <div class="space-y-4 px-6" v-if="activatedTabs == 'post'">
            <!-- Filter Tabs -->
            <div
                class="sticky top-0 z-30 -mx-6 bg-slate-50/95 px-6 py-3 backdrop-blur supports-[backdrop-filter]:bg-slate-50/60 dark:bg-slate-950/95 dark:supports-[backdrop-filter]:bg-slate-950/60"
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
                v-for="post in sortList(posts)"
                :key="post.type + '-' + post.id"
                :class="[
                    'cursor-pointer overflow-hidden rounded-2xl border-y border-r border-l-4 bg-white shadow transition-shadow hover:shadow-lg dark:border-y-gray-700 dark:border-r-gray-700 dark:bg-gray-800',
                    getTypeStyles(post.type).border,
                    getTypeStyles(post.type).gradient,
                ]"
                @click="navigateToItem(post)"
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
                                <h3 class="font-medium text-gray-900 dark:text-white">
                                    {{ data.clean_name }}
                                </h3>
                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                    <!-- Type Badge with Icon -->
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
                                    <Clock class="h-3 w-3" />
                                    <span>{{ formatTime(post.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                        <DropdownMenu>
                            <DropdownMenuTrigger @click.stop class="rounded p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <EllipsisVertical class="h-5 w-5 text-gray-400" />
                            </DropdownMenuTrigger>
                            <DropdownMenuContent>
                                <DropdownMenuItem
                                    v-if="post.type === 'post'"
                                    @click.stop="deletePost(post.hash_id)"
                                    class="text-red-600 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-900/20"
                                >
                                    Hapus
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    v-else
                                    @click.stop="deleteCollaboration(post.hash_id)"
                                    class="text-red-600 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-900/20"
                                >
                                    Hapus
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    <!-- Title for collaboration/volunteer -->
                    <div v-if="post.title" class="mb-2 text-xl font-bold text-gray-900 dark:text-white">
                        {{ post.title }}
                    </div>

                    <!-- Post Image -->
                    <div v-if="post.image" class="mb-3">
                        <img :src="post.image_url" alt="post image" class="max-h-[500px] w-auto max-w-full rounded-lg object-cover" />
                    </div>

                    <!-- Post Content -->
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

                <!-- Post Actions (only for posts) -->
                <div v-if="post.type === 'post'" class="border-t border-gray-100 px-5 py-3 dark:border-gray-700">
                    <div class="flex items-center gap-4">
                        <button
                            class="flex items-center gap-1.5 text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200"
                            @click.stop="$inertia.visit(route('user.post.details', post.hash_id))"
                        >
                            <MessageCircle class="h-4 w-4" />
                            <span>Komentar</span>
                        </button>
                        <button
                            class="flex items-center gap-1.5 text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200"
                            @click.stop="copyPostUrl(post.hash_id)"
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
                    <img v-if="event.image" :src="event.image_url" alt="Event" class="h-full w-full object-cover" />
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
                                    @click="deleteEvent(event.hash_id)"
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
