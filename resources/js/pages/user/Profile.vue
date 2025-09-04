<script setup lang="ts">
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Toaster } from '@/components/ui/sonner';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage, useRemember } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import relativeTime from 'dayjs/plugin/relativeTime';
import { EllipsisVertical, MessageCircle } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import 'vue-sonner/style.css';

const page = usePage();

dayjs.locale('id');

dayjs.extend(relativeTime);

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

function deletePost(id: number) {
    return router.delete(route('user.post.delete', id), {
        onError: (errors) => {
            console.error('error: ', errors);
            toast.error('Terjadi kesalahan saat menghapus.');
        },
        onSuccess: (success) => {},
        // preserveState: false,
    });
}

watch(
    () => page.props.flash,
    (flash: any) => {
        if (flash?.success) {
            // Panggil toast dari Sonner untuk notifikasi sukses
            toast.success(flash.success);
        }
        if (flash?.error) {
            // Panggil toast dari Sonner untuk notifikasi error
            toast.error(flash.error);
        }
    },
    { deep: true },
);

defineProps<{
    data: {
        name: string;
        created_at: string;

        comments: Array<{
            id: number;
            content: string;
            created_at: string;
            hash_id: string;
        }>;

        posts: Array<{
            content: string;
            image: string | null;
            id: number;
            hash_id: string;
            created_at: string;
        }>;
    };
}>();

const sortList = <T extends { created_at: string }>(list: Array<T>) => {
    return list.sort((a, b) => {
        const dateA = new Date(a.created_at);
        const dateB = new Date(b.created_at);

        return dateB.getTime() - dateA.getTime();
    });
};
</script>

<template>
    <Head title="Profile" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster />

        <!-- Nuxt, vue-sonner v1 because inserting inline CSS with JS to the head tag -->
        <ClientOnly>
            <Toaster />
        </ClientOnly>

        <!-- Nuxt, vue-sonner v2 no need to ClientOnly -->
        <Toaster />
        <div class="flex flex-col items-center gap-4">
            <h1 class="text-xl font-bold">{{ data.name }}</h1>
            <div class="flex flex-wrap gap-5">
                <div class="flex flex-col rounded border p-2 shadow">
                    <p>{{ data.posts.length }}</p>
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
        <div class="flex flex-col p-2" v-if="activatedTabs == 'post'">
            <div v-for="post in sortList(data.posts)" :key="post.id" class="p-2">
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

                    <h1>
                        {{ post.content }}
                    </h1>
                    <img :src="`/storage/${post.image}`" v-if="post.image" alt="post image" class="h-80 w-80 object-contain" />
                    <p class="text-gray-400">dibuat {{ formatTime(post.created_at) }}</p>
                    <MessageCircle class="cursor-pointer" @click="$inertia.visit(route('user.post.details', post.hash_id))"></MessageCircle>
                </div>
            </div>
            <div v-if="data.posts.length == 0" class="flex justify-center">Tidak ada postingan</div>
        </div>
        <div class="flex flex-col p-2" v-if="activatedTabs == 'comment'">
            <div
                v-for="comment in sortList(data.comments)"
                :key="comment.id"
                class="p-2"
                @click="$inertia.visit(route('user.post.details', comment.hash_id))"
            >
                <div class="rounded bg-white p-4 shadow dark:bg-black">
                    <h1 class="font-bold">
                        {{ data.name }}
                    </h1>
                    <h1>
                        {{ comment.content }}
                    </h1>

                    <p class="text-gray-400">dibuat {{ formatTime(comment.created_at) }}</p>
                </div>
            </div>
            <div v-if="data.comments.length == 0" class="flex justify-center">Tidak ada Komentar</div>
        </div>
    </AppLayout>
</template>
