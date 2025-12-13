<script setup lang="ts">
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
import { Label } from '@/components/ui/label';
import { Toaster } from '@/components/ui/sonner';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import relativeTime from 'dayjs/plugin/relativeTime';
import { Clock, EllipsisVertical, Eye, MessageCircle, Share2 } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

import 'vue-sonner/style.css';

import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';

dayjs.extend(relativeTime);
dayjs.locale('id');

const page = usePage();

const reportValue = ref('spam');

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Home',
        href: '/user/home',
    },
];
type User = {
    id: number;
    name: string;
    email: string;
    role: string;
    hash_id: string;
    clean_name: string;
};

type Post = {
    id: number;
    user_id: number;
    content: string;
    hash_id: string;
    image?: string;
    created_at: string;
    user: User;
};

const props = defineProps<{
    data: {
        current_page: number;
        last_page: number;
        data: Post[];
        next_page_url?: string | null;
    };
}>();

const posts = ref<Post[]>([...props.data.data]);
const currentPage = ref(props.data.current_page);
const lastPage = ref(props.data.last_page);
const loading = ref(false);

const loadMore = async () => {
    if (loading.value || currentPage.value >= lastPage.value) return;
    loading.value = true;

    try {
        const res = await axios.get(`/user/home?page=${currentPage.value + 1}`, {
            headers: { Accept: 'application/json' },
        });

        const newData = res.data;
        posts.value.push(...newData.data);

        currentPage.value = newData.current_page;
        lastPage.value = newData.last_page;
    } finally {
        loading.value = false;
    }
};
const sentinel = ref<HTMLElement | null>(null);
let observer: IntersectionObserver | null = null;

onMounted(() => {
    observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    loadMore();
                }
            });
        },
        { rootMargin: '200px' },
    );
    if (sentinel.value) observer.observe(sentinel.value);
});

onBeforeUnmount(() => {
    if (observer && sentinel.value) observer.disconnect();
});

const sortedPosts = computed(() => {
    return posts.value.slice().sort((a, b) => {
        const dateA = new Date(a.created_at);
        const dateB = new Date(b.created_at);
        return dateB.getTime() - dateA.getTime();
    });
});

function formatDate(date: string) {
    return dayjs(date).fromNow();
}

function deletePost(id: number) {
    return router.delete(route('user.post.delete', id), {
        onError: (errors) => {
            console.error('error: ', errors);
            toast.error('Terjadi kesalahan saat menghapus.');
        },
        onSuccess: (success) => {},

        preserveState: false,
        preserveScroll: true,
    });
}

watch(
    () => page.props.flash,
    (flash: any) => {
        if (flash?.success) {
            toast.success(flash.success, {
                dismissible: true,
                duration: 5000,
                action: {
                    label: 'Tutup',
                    onClick: () => {
                        toast.dismiss();
                    },
                },
            });
        }
        if (flash?.error) {
            toast.error(flash.error, {
                dismissible: true,
                duration: 5000,
                action: {
                    label: 'Tutup',
                    onClick: () => {
                        toast.dismiss();
                    },
                },
            });
        }
    },
    { deep: true, immediate: true },
);

function reportPost(postId: number) {
    router.post(
        route('user.post.report'),
        {
            post_id: postId,
            reason: reportValue.value,
        },
        {
            preserveScroll: true,

            onError: (errors) => {
                toast.error('Gagal mengirim laporan.');
                console.error(errors);
            },
        },
    );
}

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
    <Head title="Home" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster />

        <!-- Nuxt, vue-sonner v1 because inserting inline CSS with JS to the head tag -->
        <ClientOnly>
            <Toaster />
        </ClientOnly>

        <!-- Nuxt, vue-sonner v2 no need to ClientOnly -->
        <Toaster />
        <div class="space-y-4 p-8">
            <div>
                <Button @click="$inertia.visit(route('user.create-post'))" class="text-lg">Tulis Cerita</Button>
            </div>
            <div class="fixed right-8 bottom-8 z-50">
                <Button class="text-lg font-bold" @click="$inertia.visit(route('user.create-post'))">+</Button>
            </div>
            <div
                class="group transform space-y-4 rounded-2xl border border-gray-200 bg-white p-4 shadow transition-all hover:-translate-y-1 hover:scale-[1.02] hover:shadow-lg dark:border-gray-700 dark:bg-gray-800 dark:hover:shadow-gray-700"
                v-for="posts in sortedPosts"
                :key="posts.id"
            >
                <div class="flex flex-wrap gap-2">
                    <div class="flex flex-row items-center gap-1">
                        <div class="relative">
                            <div
                                class="border-white/ border/20 flex h-10 w-10 items-center justify-center rounded-full bg-white text-2xl font-bold text-black shadow-lg"
                            >
                                {{ posts.user.name.charAt(0).toUpperCase() }}
                            </div>
                        </div>
                        <div class="flex flex-col">
                            <h1 class="cursor-pointer text-2xl font-bold" @click="$inertia.visit(`/user/post/${posts.hash_id}`)">
                                {{ posts.user.role == 'user' ? posts.user.clean_name : posts.user.name }}
                            </h1>
                            <div class="flex items-center gap-1 text-sm text-gray-500">
                                <Clock class="h-3 w-3" />
                                <span>{{ formatDate(posts.created_at) }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="posts.user.role === 'moderator'" class="flex h-fit items-center rounded bg-blue-600 p-1 font-bold text-white">
                        Moderator
                    </div>
                    <div v-if="posts.user.role === 'community'" class="flex h-fit items-center rounded bg-yellow-500 p-1 font-bold text-white shadow">
                        Komunitas
                    </div>
                    <div class="ml-auto">
                        <DropdownMenu>
                            <DropdownMenuTrigger><EllipsisVertical></EllipsisVertical></DropdownMenuTrigger>
                            <DropdownMenuContent>
                                <DropdownMenuItem
                                    @click="
                                        $inertia.visit(
                                            route('user.details', {
                                                hashId: posts.user.hash_id,
                                                name: posts.user.name.toLowerCase().split(' ').join('-'),
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
                                            <AlertDialogAction @click="reportPost(posts.id)">Kirim</AlertDialogAction>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog>

                                <DropdownMenuItem v-if="posts.user.id === usePage().props.auth.user.id" @click="deletePost(posts.id)"
                                    >Hapus</DropdownMenuItem
                                >
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>

                <p>{{ posts.content }}</p>

                <img v-if="posts.image" :src="`/storage/${posts.image}`" alt="Post Image" class="h-80 w-60 rounded object-cover" />

                <div class="flex flex-row gap-6 border-t border-gray-100 px-6 pt-4 pb-6 dark:border-gray-700">
                    <input type="hidden" name="like" id="like" />
                    <!-- <Label class="cursor-pointer">
                        <Heart for="like" />
                    </Label> -->
                    <Label class="cursor-pointer" @click="$inertia.visit(`/user/post/${posts.hash_id}`)">
                        <MessageCircle />
                        Komentar
                    </Label>
                    <Label class="cursor-pointer" @click="copyPostUrl(posts.hash_id)">
                        <Share2 />
                        Bagikan
                    </Label>
                </div>
            </div>
            <div ref="sentinel"></div>
            <div v-if="loading" class="py-4 text-center">Loading...</div>
            <div v-else-if="currentPage >= lastPage && posts.length > 0" class="flex justify-center p-8 text-gray-500">
                <div class="text-center">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700">
                        <Eye class="h-6 w-6" />
                    </div>
                    <p>Tidak ada postingan lagi</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
