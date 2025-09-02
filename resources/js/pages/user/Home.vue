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
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import relativeTime from 'dayjs/plugin/relativeTime';
import { EllipsisVertical, Heart, MessageCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
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
            hash_id: string;
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
            <div>
                <Button @click="$inertia.visit(route('user.create-post'))" class="text-lg">Tulis Cerita</Button>
            </div>
            <div class="fixed right-8 bottom-8 z-50">
                <Button class="text-lg font-bold" @click="$inertia.visit(route('user.create-post'))">+</Button>
            </div>
            <div class="space-y-4 rounded bg-white p-4 shadow dark:bg-black" v-for="posts in sortedPosts" :key="posts.id">
                <div class="flex flex-wrap gap-2">
                    <h1 class="cursor-pointer text-2xl font-bold" @click="$inertia.visit(`/user/post/${posts.hash_id}`)">{{ posts.user.name }}</h1>
                    <p v-if="posts.user.role === 'moderator'" class="rounded bg-blue-600 px-1 py-1 text-white dark:text-black">Moderator</p>
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
