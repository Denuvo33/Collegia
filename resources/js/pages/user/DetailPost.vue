<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Toaster } from '@/components/ui/sonner';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import relativeTime from 'dayjs/plugin/relativeTime';
import { AlertTriangle, CheckCircle, Clock, EllipsisVertical, Image as ImageIcon, MessageSquare, Send, Share2, X } from 'lucide-vue-next';
import 'vue-sonner/style.css';

import { computed, onMounted, onUnmounted, ref } from 'vue';
import { toast } from 'vue-sonner';

dayjs.locale('id');
dayjs.extend(relativeTime);

const successComment = ref<String | null>();
const reportValue = ref('spam');

// Pagination state for infinite scroll
const comments = ref<Array<any>>([]);
const currentPage = ref(1);
const hasMoreComments = ref(true);
const loadingComments = ref(false);
const observer = ref<IntersectionObserver | null>(null);
const loadMoreTrigger = ref<HTMLElement | null>(null);

// Cache configuration - limits to prevent memory bloat
const CACHE_CONFIG = {
    MAX_CACHED_POSTS: 5,
    MAX_COMMENTS_PER_POST: 50,
    CACHE_EXPIRY_MS: 5 * 60 * 1000,
};

// Cache utility functions with security and memory management
const getCacheKey = (postId: number) => `post_comments_${postId}`;

const getCachedComments = (postId: number) => {
    try {
        const cached = sessionStorage.getItem(getCacheKey(postId));
        if (!cached) return null;

        const data = JSON.parse(cached);

        // Check expiry
        if (Date.now() - data.timestamp > CACHE_CONFIG.CACHE_EXPIRY_MS) {
            sessionStorage.removeItem(getCacheKey(postId));
            return null;
        }

        return data;
    } catch (e) {
        // Silent fail on parse errors, remove corrupted cache
        sessionStorage.removeItem(getCacheKey(postId));
        return null;
    }
};

const setCachedComments = (postId: number, comments: any[], currentPage: number, total: number) => {
    try {
        // Limit comments to prevent memory bloat
        const limitedComments = comments.slice(0, CACHE_CONFIG.MAX_COMMENTS_PER_POST);

        const data = {
            comments: limitedComments,
            currentPage: Math.min(currentPage, Math.ceil(CACHE_CONFIG.MAX_COMMENTS_PER_POST / 10)),
            total,
            timestamp: Date.now(),
        };

        // Clean old caches if we exceed max cached posts
        cleanOldCaches();

        sessionStorage.setItem(getCacheKey(postId), JSON.stringify(data));
    } catch (e) {
        // If quota exceeded, clear all caches
        console.warn('Cache quota exceeded, clearing old caches');
        clearAllCommentCaches();
    }
};

const invalidateCache = (postId: number) => {
    sessionStorage.removeItem(getCacheKey(postId));
};

const cleanOldCaches = () => {
    try {
        const cacheKeys: { key: string; timestamp: number }[] = [];

        // Find all comment caches
        for (let i = 0; i < sessionStorage.length; i++) {
            const key = sessionStorage.key(i);
            if (key?.startsWith('post_comments_')) {
                const data = sessionStorage.getItem(key);
                if (data) {
                    try {
                        const parsed = JSON.parse(data);
                        cacheKeys.push({ key, timestamp: parsed.timestamp || 0 });
                    } catch (e) {
                        // Remove corrupted cache
                        sessionStorage.removeItem(key);
                    }
                }
            }
        }

        // If we have too many caches, remove oldest ones
        if (cacheKeys.length > CACHE_CONFIG.MAX_CACHED_POSTS) {
            cacheKeys.sort((a, b) => a.timestamp - b.timestamp);
            const toRemove = cacheKeys.slice(0, cacheKeys.length - CACHE_CONFIG.MAX_CACHED_POSTS);
            toRemove.forEach(({ key }) => sessionStorage.removeItem(key));
        }
    } catch (e) {
        console.error('Error cleaning caches:', e);
    }
};

const clearAllCommentCaches = () => {
    const keysToRemove: string[] = [];
    for (let i = 0; i < sessionStorage.length; i++) {
        const key = sessionStorage.key(i);
        if (key?.startsWith('post_comments_')) {
            keysToRemove.push(key);
        }
    }
    keysToRemove.forEach((key) => sessionStorage.removeItem(key));
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Home',
        href: '/user/home',
    },
    {
        title: 'Detail Post',
        href: '/user/post/details',
    },
];

const props = defineProps<{
    data: {
        id: number;
        user_id: number;
        content: string;
        hash_id: string;
        image?: string;
        created_at: string;

        user: {
            id: number;
            name: string;
            email: string;
            hash_id: string;
            role: string;
            clean_name: string;
        };
        comments: {
            data: Array<{
                id: number;
                content: string;
                user_id: number;
                post_id: number;
                created_at: string;
                user: {
                    id: number;
                    name: string;
                    clean_name: string;
                    hash_id: string;
                    role: string;
                };
            }>;
            current_page: number;
            last_page: number;
            per_page: number;
            total: number;
        };
    };
}>();

// Initialize comments from cache or props
const cached = getCachedComments(props.data.id);
if (cached && cached.comments.length > 0) {
    // Use cached data
    comments.value = cached.comments;
    currentPage.value = cached.currentPage;
    hasMoreComments.value = currentPage.value < Math.ceil(cached.total / 10);
} else {
    // Use server data
    comments.value = props.data.comments.data || [];
    currentPage.value = props.data.comments.current_page || 1;
    hasMoreComments.value = currentPage.value < (props.data.comments.last_page || 1);
}

const form = useForm({
    post_id: props.data.id,
    user_id: usePage().props.auth.user.id,
    content: '',
});

const sortedComment = computed(() => {
    return comments.value.sort((a, b) => {
        return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
    });
});

// Load more comments function
const loadMoreComments = async () => {
    if (loadingComments.value || !hasMoreComments.value) return;

    loadingComments.value = true;
    try {
        const response = await fetch(`/user/post/${props.data.id}/comments?page=${currentPage.value + 1}`);
        const data = await response.json();

        if (data.data && data.data.length > 0) {
            comments.value = [...comments.value, ...data.data];
            currentPage.value = data.current_page;
            hasMoreComments.value = currentPage.value < data.last_page;

            // Update cache with new comments
            setCachedComments(props.data.id, comments.value, currentPage.value, data.total);
        } else {
            hasMoreComments.value = false;
        }
    } catch (error) {
        console.error('Error loading comments:', error);
    } finally {
        loadingComments.value = false;
    }
};

// Setup intersection observer for infinite scroll
onMounted(() => {
    observer.value = new IntersectionObserver(
        (entries) => {
            if (entries[0].isIntersecting && hasMoreComments.value && !loadingComments.value) {
                loadMoreComments();
            }
        },
        { threshold: 0.1 },
    );

    // Start observing when component is mounted
    setTimeout(() => {
        if (loadMoreTrigger.value) {
            observer.value?.observe(loadMoreTrigger.value);
        }
    }, 100);

    // Cache initial comments if not already cached
    if (!getCachedComments(props.data.id)) {
        setCachedComments(props.data.id, comments.value, currentPage.value, props.data.comments.total || 0);
    }
});

// Cleanup observer on unmount
onUnmounted(() => {
    if (observer.value) {
        observer.value.disconnect();
    }

    // Save current state to cache before unmounting
    setCachedComments(props.data.id, comments.value, currentPage.value, props.data.comments.total || 0);
});

function submit() {
    form.post(route('post.store.comment'), {
        onError: (Errors) => {
            if (Errors.error) {
                toast.error(Errors.error);
                return;
            }

            toast.error('Gagal Membuat Komentar');
        },
        onSuccess: () => {
            // Create the new comment object with current data
            const newComment = {
                id: Date.now(), // Temporary ID until page is reloaded
                content: form.content,
                user_id: usePage().props.auth.user.id,
                post_id: props.data.id,
                created_at: new Date().toISOString(),
                user: {
                    id: usePage().props.auth.user.id,
                    name: usePage().props.auth.user.role == 'user' ? usePage().props.auth.user.clean_name : usePage().props.auth.user.name,
                    clean_name: usePage().props.auth.user.clean_name,
                    hash_id: (usePage().props.auth.user as any).hash_id || '',
                    role: (usePage().props.auth.user as any).role || 'user',
                },
            };

            // Add the new comment to the top of the list
            comments.value = [newComment, ...comments.value];

            // Invalidate cache since we have a new comment
            invalidateCache(props.data.id);

            // Update total count
            if (props.data.comments.total !== undefined) {
                props.data.comments.total++;
            }

            // Clear the form
            form.content = '';
        },
    });
}

function formatTime(date: string) {
    return dayjs(date).fromNow();
}

function deleteComment(hashId: string) {
    try {
        router.delete(route('user.comment.delete', hashId), {
            preserveState: true,
            onSuccess: () => {
                // Remove comment from local state
                comments.value = comments.value.filter((c) => c.hash_id !== hashId);
                // Invalidate cache since comment was deleted
                invalidateCache(props.data.id);
                toast.success('Komentar berhasil dihapus');
            },
            onError: (Errors) => {
                if (Errors.error) {
                    toast.error(Errors.error);
                }
                toast.error('Gagal menghapus komentar');
            },
        });
    } catch (e) {
        toast.error('Gagal menghapus komentar');
    }
}

const closeAlert = () => {
    successComment.value = null;
};

function getInitials(name: string) {
    return name
        .split(' ')
        .map((word) => word.charAt(0))
        .join('')
        .toUpperCase()
        .slice(0, 2);
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
            onSuccess: () => {
                successComment.value = 'Laporan telah dikirim ke moderator, terima kasih atas laporannya';
                reportValue.value = 'spam';
            },

            onError: (errors) => {
                successComment.value = 'Gagal mengirim laporan, periksa kembali jaringan anda';
                console.error(errors);
            },
        },
    );
}
function deletePost(hashId: string) {
    return router.delete(route('user.post.delete', hashId), {
        onError: (errors) => {
            console.error('error: ', errors);
            toast.error('Terjadi kesalahan saat menghapus postingan');
        },
        onSuccess: () => {
            toast.success('Postingan berhasil dihapus');
            // Redirect to home after successful deletion
            setTimeout(() => {
                router.visit(route('user.home'));
            }, 1000);
        },
    });
}
function reportComment(commentId: number) {
    try {
        router.post(
            route('user.comment.report'),
            {
                comment_id: commentId,
                reason: reportValue.value,
            },
            {
                onSuccess: () => {
                    successComment.value = 'Laporan telah dikirim ke moderator, terima kasih atas laporannya';
                    reportValue.value = 'spam';
                },
                onError: (Errors: any) => {
                    ((successComment.value = 'Gagal mengirim laporan, periksa kembali jaringan anda'), console.error(Errors));
                },
            },
        );
    } catch (e) {
        successComment.value = 'Gagal mengirim laporan, periksa kembali jaringan anda';
    }
}
const copyUrl = () => {
    const url = window.location.href;
    navigator.clipboard.writeText(url).then(() => {
        toast.success('Link disalin!', { duration: 3000 });
    });
};
</script>

<template>
    <Head title="Detail Post" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster />
        <div class="min-h-screen">
            <div class="mx-auto max-w-4xl px-4 py-6">
                <!-- Success/Error Alert -->
                <div v-if="successComment" class="mb-6">
                    <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
                        <Alert
                            :class="successComment && successComment.includes('Gagal') ? 'border-red-200 bg-red-50' : 'border-green-200 bg-green-50'"
                        >
                            <CheckCircle v-if="!successComment.includes('Gagal')" class="h-5 w-5 text-green-600" />
                            <AlertTriangle v-else class="h-5 w-5 text-red-600" />
                            <div class="flex flex-row items-start justify-between">
                                <div>
                                    <AlertTitle
                                        :class="
                                            successComment && successComment.includes('Gagal')
                                                ? 'font-semibold text-red-700'
                                                : 'font-semibold text-green-700'
                                        "
                                    >
                                        {{ successComment && successComment.includes('Gagal') ? 'Error' : 'Berhasil' }}
                                    </AlertTitle>
                                    <AlertDescription :class="successComment && successComment.includes('Gagal') ? 'text-red-600' : 'text-green-600'">
                                        {{ successComment }}
                                    </AlertDescription>
                                </div>
                                <button @click="closeAlert" class="rounded-lg p-1 transition-colors hover:bg-gray-200">
                                    <X class="h-4 w-4 text-gray-500" />
                                </button>
                            </div>
                        </Alert>
                    </div>
                </div>

                <!-- Main Post Card -->
                <div
                    class="mb-8 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm hover:shadow-lg dark:border-gray-700 dark:bg-gray-800"
                >
                    <!-- Post Header -->
                    <div class="border-b border-gray-100 p-6">
                        <div class="mb-4 flex items-center gap-4">
                            <div class="flex-shrink-0">
                                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white text-sm font-semibold text-black shadow">
                                    {{ getInitials(data.user.clean_name) }}
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="flex flex-wrap gap-2">
                                    <h1
                                        class="cursor-pointer text-xl font-bold text-gray-900 transition-colors hover:text-indigo-600 dark:text-white"
                                        @click="
                                            $inertia.visit(
                                                route('user.details', {
                                                    hashId: data.user.hash_id,
                                                    name: data.user.clean_name.split(' ').join('-').toLowerCase(),
                                                }),
                                            )
                                        "
                                    >
                                        {{ data.user.role == 'user' ? data.user.clean_name : data.user.name }}
                                    </h1>
                                    <div
                                        v-if="data.user.role === 'moderator'"
                                        class="flex h-fit items-center rounded bg-blue-600 p-1 font-bold text-white"
                                    >
                                        Moderator
                                    </div>
                                    <div
                                        v-if="data.user.role === 'community'"
                                        class="flex h-fit items-center rounded bg-yellow-500 p-1 font-bold text-white shadow"
                                    >
                                        Komunitas
                                    </div>
                                </div>

                                <div class="mt-1 flex items-center gap-2 text-sm text-gray-500">
                                    <Clock class="h-4 w-4" />
                                    <span>Dibuat {{ formatTime(data.created_at) }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-8 w-8 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100"
                                    @click="copyUrl"
                                >
                                    <Share2 class="h-4 w-4" />
                                </Button>
                                <DropdownMenu>
                                    <DropdownMenuTrigger><EllipsisVertical></EllipsisVertical></DropdownMenuTrigger>
                                    <DropdownMenuContent>
                                        <DropdownMenuItem @click="copyUrl">Salin Link</DropdownMenuItem>
                                        <DropdownMenuItem
                                            @click="
                                                $inertia.visit(
                                                    route('user.details', {
                                                        hashId: data.user.hash_id,
                                                        name: data.user.clean_name.toLowerCase().split(' ').join('-'),
                                                    }),
                                                )
                                            "
                                            >Profile</DropdownMenuItem
                                        >
                                        <AlertDialog>
                                            <AlertDialogTrigger as-child>
                                                <DropdownMenuItem @select.prevent>Laporkan</DropdownMenuItem>
                                            </AlertDialogTrigger>

                                            <AlertDialogContent>
                                                <AlertDialogHeader>
                                                    <AlertDialogTitle>Pilih alasan laporan</AlertDialogTitle>
                                                    <AlertDialogDescription>
                                                        Laporan ini akan dikirim ke moderator untuk ditinjau lebih lanjut.
                                                    </AlertDialogDescription>
                                                    <RadioGroup v-model="reportValue" default-value="spam">
                                                        <div class="flex items-center space-x-2">
                                                            <RadioGroupItem id="r1" value="spam" />
                                                            <Label for="r1">Spam</Label>
                                                        </div>
                                                        <div class="flex items-center space-x-2">
                                                            <RadioGroupItem id="r2" value="inappropriate" />
                                                            <Label for="r2">Tidak Pantas</Label>
                                                        </div>
                                                        <div class="flex items-center space-x-2">
                                                            <RadioGroupItem id="r3" value="harassment" />
                                                            <Label for="r3">Pelecehan</Label>
                                                        </div>
                                                        <div class="flex items-center space-x-2">
                                                            <RadioGroupItem id="r3" value="abusive" />
                                                            <Label for="r3">Kekerasan</Label>
                                                        </div>
                                                        <div class="flex items-center space-x-2">
                                                            <RadioGroupItem id="r3" value="porn" />
                                                            <Label for="r3">Pornografi</Label>
                                                        </div>
                                                        <div class="flex items-center space-x-2">
                                                            <RadioGroupItem id="r3" value="pretend to be other people" />
                                                            <Label for="r3">Berpura-pura menjadi orang lain</Label>
                                                        </div>
                                                    </RadioGroup>
                                                </AlertDialogHeader>
                                                <AlertDialogFooter>
                                                    <AlertDialogCancel>Batal</AlertDialogCancel>
                                                    <AlertDialogAction @click="reportPost(data.id)">Kirim</AlertDialogAction>
                                                </AlertDialogFooter>
                                            </AlertDialogContent>
                                        </AlertDialog>

                                        <DropdownMenuItem
                                            class="text-red-500 hover:bg-red-50 hover:text-red-700"
                                            v-if="
                                                data.user.id === usePage().props.auth.user.id ||
                                                usePage().props.auth.user.role === 'admin' ||
                                                usePage().props.auth.user.role === 'moderator'
                                            "
                                            @click="deletePost(data.hash_id)"
                                            >Hapus</DropdownMenuItem
                                        >
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    </div>

                    <!-- Post Content -->
                    <div class="p-6">
                        <div class="prose mb-6 max-w-none">
                            <p class="text-lg leading-relaxed text-gray-800 dark:text-white">{{ data.content }}</p>
                        </div>

                        <!-- Post Image -->
                        <div v-if="data.image" class="mb-4">
                            <div
                                class="relative overflow-hidden rounded-xl border border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-transparent"
                            >
                                <img :src="`/storage/${data.image}`" alt="Post Image" class="h-auto max-h-96 w-full object-contain" />
                                <div class="absolute top-3 right-3 rounded-lg bg-black/20 p-2 backdrop-blur-sm">
                                    <ImageIcon class="h-4 w-4 text-white" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Comments Section -->
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <!-- Comments Header -->
                    <div class="border-b border-gray-100 p-6">
                        <div class="flex items-center gap-3">
                            <div class="rounded-lg bg-blue-100 p-2">
                                <MessageSquare class="h-5 w-5 text-blue-600" />
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Komentar</h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ props.data.comments.total }} komentar</p>
                            </div>
                        </div>
                    </div>

                    <!-- Comments List -->
                    <div class="divide-y divide-gray-100">
                        <div v-if="comments.length > 0" class="max-h-96 overflow-y-auto">
                            <div
                                v-for="comment in sortedComment"
                                :key="comment.id"
                                class="bark:bg-gray-700 p-6 transition-colors hover:bg-gray-50 hover:dark:bg-gray-700"
                            >
                                <div class="flex gap-4">
                                    <!-- Avatar -->
                                    <div class="flex-shrink-0">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-black">
                                            {{ getInitials(comment.user.clean_name) }}
                                        </div>
                                    </div>

                                    <!-- Comment Content -->
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-start justify-between gap-4">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap gap-2">
                                                    <h4
                                                        class="mb-1 cursor-pointer truncate font-semibold text-gray-900 hover:underline dark:text-white"
                                                        @click="
                                                            $inertia.visit(
                                                                route('user.details', {
                                                                    hashId: comment.user.hash_id,
                                                                    name: comment.user.clean_name.split(' ').join('-').toLowerCase(),
                                                                }),
                                                            )
                                                        "
                                                    >
                                                        {{ comment.user.role == 'user' ? comment.user.clean_name : comment.user.name }}
                                                    </h4>
                                                    <div
                                                        v-if="comment.user.role === 'moderator'"
                                                        class="flex h-fit items-center rounded bg-blue-600 p-1 font-bold text-white"
                                                    >
                                                        Moderator
                                                    </div>
                                                    <div
                                                        v-if="comment.user.role === 'community'"
                                                        class="flex h-fit items-center rounded bg-yellow-500 p-1 font-bold text-white shadow"
                                                    >
                                                        Komunitas
                                                    </div>
                                                </div>

                                                <p class="mb-2 leading-relaxed break-words text-gray-700 dark:text-gray-300">
                                                    {{ comment.content }}
                                                </p>
                                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                                    <Clock class="h-3 w-3" />
                                                    <span>{{ formatTime(comment.created_at) }}</span>
                                                </div>
                                            </div>

                                            <!-- Comment Actions -->
                                            <div class="flex-shrink-0">
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger>
                                                        <div class="rounded-lg p-2 transition-colors hover:bg-gray-200 dark:hover:bg-gray-600">
                                                            <EllipsisVertical class="h-4 w-4 text-gray-500" />
                                                        </div>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end" class="w-40">
                                                        <AlertDialog>
                                                            <AlertDialogTrigger as-child>
                                                                <DropdownMenuItem @select.prevent>Laporkan</DropdownMenuItem>
                                                            </AlertDialogTrigger>

                                                            <AlertDialogContent>
                                                                <AlertDialogHeader>
                                                                    <AlertDialogTitle>Pilih alasan laporan</AlertDialogTitle>
                                                                    <AlertDialogDescription>
                                                                        Laporan ini akan dikirim ke moderator untuk ditinjau lebih lanjut.
                                                                    </AlertDialogDescription>
                                                                    <RadioGroup v-model="reportValue" default-value="spam">
                                                                        <div class="flex items-center space-x-2">
                                                                            <RadioGroupItem id="r1" value="spam" />
                                                                            <Label for="r1">Spam</Label>
                                                                        </div>
                                                                        <div class="flex items-center space-x-2">
                                                                            <RadioGroupItem id="r2" value="inappropriate" />
                                                                            <Label for="r2">Tidak Pantas</Label>
                                                                        </div>
                                                                        <div class="flex items-center space-x-2">
                                                                            <RadioGroupItem id="r3" value="harassment" />
                                                                            <Label for="r3">Pelecehan</Label>
                                                                        </div>
                                                                        <div class="flex items-center space-x-2">
                                                                            <RadioGroupItem id="r3" value="abusive" />
                                                                            <Label for="r3">Kekerasan</Label>
                                                                        </div>
                                                                        <div class="flex items-center space-x-2">
                                                                            <RadioGroupItem id="r3" value="porn" />
                                                                            <Label for="r3">Pornografi</Label>
                                                                        </div>
                                                                        <div class="flex items-center space-x-2">
                                                                            <RadioGroupItem id="r3" value="pretend to be other people" />
                                                                            <Label for="r3">Berpura-pura menjadi orang lain</Label>
                                                                        </div>
                                                                    </RadioGroup>
                                                                </AlertDialogHeader>
                                                                <AlertDialogFooter>
                                                                    <AlertDialogCancel>Batal</AlertDialogCancel>
                                                                    <AlertDialogAction @click="reportComment(comment.id)">Kirim</AlertDialogAction>
                                                                </AlertDialogFooter>
                                                            </AlertDialogContent>
                                                        </AlertDialog>
                                                        <DropdownMenuItem
                                                            v-if="
                                                                comment.user.id === usePage().props.auth.user.id ||
                                                                usePage().props.auth.user.role === 'moderator'
                                                            "
                                                            @click="deleteComment(comment.hash_id)"
                                                            class="text-sm text-red-600 hover:bg-red-50 hover:text-red-700"
                                                        >
                                                            Hapus
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Load more trigger for intersection observer -->
                            <div v-if="hasMoreComments" ref="loadMoreTrigger" class="p-4 text-center">
                                <div v-if="loadingComments" class="flex items-center justify-center gap-2 text-gray-500">
                                    <div class="h-5 w-5 animate-spin rounded-full border-2 border-gray-300 border-t-blue-600"></div>
                                    <span>Memuat komentar...</span>
                                </div>
                            </div>
                        </div>

                        <!-- Empty Comments State -->
                        <div v-else class="p-12 text-center">
                            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
                                <MessageSquare class="h-8 w-8 text-gray-400" />
                            </div>
                            <h3 class="mb-2 text-lg font-semibold text-gray-700">Belum ada komentar</h3>
                            <p class="text-gray-500">Jadilah yang pertama berkomentar pada postingan ini</p>
                        </div>
                    </div>

                    <!-- Comment Form -->
                    <div class="border-t border-gray-100 bg-gray-50 p-6 dark:border-gray-700 dark:bg-gray-900">
                        <form @submit.prevent="submit" class="space-y-4">
                            <div class="flex gap-4">
                                <div class="flex-shrink-0">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-sm font-medium text-black">
                                        {{ getInitials(usePage().props.auth.user.name) }}
                                    </div>
                                </div>
                                <div class="flex-1 space-y-3">
                                    <div class="relative">
                                        <Input
                                            v-model="form.content"
                                            placeholder="Tulis komentar Anda..."
                                            type="text"
                                            maxlength="500"
                                            class="focus:ring-opacity-50 w-full rounded-xl border-gray-200 py-3 pr-12 focus:border-indigo-300 focus:ring focus:ring-indigo-200"
                                            :disabled="form.processing"
                                        />
                                        <div class="mt-1 text-right text-xs text-gray-500">{{ form.content.length }}/500</div>
                                        <Button
                                            type="submit"
                                            :disabled="form.processing || !form.content.trim()"
                                            class="absolute top-1 right-2 h-8 w-8 rounded-lg bg-indigo-600 p-0 hover:bg-indigo-700 disabled:opacity-50"
                                        >
                                            <Send class="h-4 w-4" />
                                        </Button>
                                    </div>
                                    <div v-if="form.errors.content" class="flex items-center gap-2 text-sm text-red-600">
                                        <AlertTriangle class="h-4 w-4" />
                                        {{ form.errors.content }}
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Bottom Spacing -->
                <div class="h-8"></div>
            </div>
        </div>
    </AppLayout>
</template>
