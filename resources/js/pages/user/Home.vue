<script setup lang="ts">
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Label } from '@/components/ui/label';
import { Toaster } from '@/components/ui/sonner';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import relativeTime from 'dayjs/plugin/relativeTime';
import { EllipsisVertical, Heart, MessageCircle } from 'lucide-vue-next';
import { computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import 'vue-sonner/style.css';

dayjs.extend(relativeTime);
dayjs.locale('id');

const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Home',
        href: '/user/home',
    },
];

const props = defineProps<{
    data: Array<{
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
            role: string;
        };
    }>;
}>();

const sortedPosts = computed(() => {
    return props.data.slice().sort((a, b) => {
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
        <div class="space-y-4 p-4">
            <div class="space-y-4 rounded bg-white p-4 shadow dark:bg-black" v-for="posts in sortedPosts" :key="posts.id">
                <div class="flex flex-wrap gap-2">
                    <h1 class="cursor-pointer text-2xl font-bold" @click="$inertia.visit(`/user/post/${posts.hash_id}`)">{{ posts.user.name }}</h1>
                    <p v-if="posts.user.role === 'moderator'" class="rounded bg-blue-600 px-1 py-1 text-white dark:text-black">Moderator</p>
                    <div class="ml-auto">
                        <DropdownMenu>
                            <DropdownMenuTrigger><EllipsisVertical></EllipsisVertical></DropdownMenuTrigger>
                            <DropdownMenuContent>
                                <DropdownMenuItem>Profile</DropdownMenuItem>
                                <DropdownMenuItem>Laporkan Postingan</DropdownMenuItem>
                                <DropdownMenuItem v-if="posts.user.id === usePage().props.auth.user.id" @click="deletePost(posts.id)"
                                    >Hapus</DropdownMenuItem
                                >
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
                <hr />
                <p>{{ posts.content }}</p>
                <img v-if="posts.image" :src="`/storage/${posts.image}`" alt="Post Image" class="h-80 w-80 object-contain" />
                <div class="flex flex-row gap-6">
                    <input type="hidden" name="like" id="like" />
                    <Label class="cursor-pointer">
                        <Heart for="like" />
                    </Label>
                    <Label class="cursor-pointer" @click="$inertia.visit(`/user/post/${posts.hash_id}`)">
                        <MessageCircle />
                    </Label>
                </div>
                <p class="text-gray-400">Dibuat {{ formatDate(posts.created_at) }}</p>
            </div>
        </div>
    </AppLayout>
</template>
