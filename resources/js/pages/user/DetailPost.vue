<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import relativeTime from 'dayjs/plugin/relativeTime';
import { EllipsisVertical } from 'lucide-vue-next';
import { ref } from 'vue';

dayjs.locale('id');
dayjs.extend(relativeTime);

const successComment = ref<String | null>();

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
            name: string;
            email: string;
            hash_id: string;
        };
        comments: Array<{
            id: number;
            content: string;
            user_id: number;
            post_id: number;
            created_at: string;
            user: {
                id: number;
                name: string;
            };
        }>;
    };
}>();

const form = useForm({
    post_id: props.data.id,
    user_id: usePage().props.auth.user.id,
    content: '',
});

function submit() {
    form.post(route('post.store.comment'), {
        onError: (Errors) => {
            console.log('erros', Errors);
        },
        onSuccess: (Success) => {
            form.content = '';
        },
    });
}

function formatTime(date: string) {
    return dayjs(date).fromNow();
}

function deleteComment(id: number) {
    try {
        router.delete(route('user.comment.delete', id), {
            onSuccess: () => {
                successComment.value = 'Komentar telah di hapus';
            },
            onError: () => {
                successComment.value = 'Gagal menghapus komentar, periksa kembali jaringan anda';
            },
        });
    } catch (e) {
        successComment.value = 'Gagal menghapus komentar, periksa kembali jaringan anda';
    }
}

const closeAlert = () => {
    successComment.value = null;
};
</script>

<template>
    <Head title="Detail Post" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div v-if="successComment" class="p-4">
            <Alert>
                <Rocket class="h-4 w-4" />
                <div class="flex flex-row justify-between">
                    <AlertTitle :class="successComment && successComment.includes('Gagal') ? 'text-red-600' : 'text-green-600'">{{
                        successComment && successComment.includes('Gagal') ? 'Error' : 'Berhasil'
                    }}</AlertTitle>
                    <h1 @click="closeAlert" class="cursor-pointer text-lg font-bold text-gray-400">X</h1>
                </div>
                <AlertDescription>
                    {{ successComment }}
                </AlertDescription>
            </Alert>
        </div>

        <div class="">
            <div class="space-y-4 rounded bg-white p-4 shadow dark:bg-black">
                <h1
                    class="cursor-pointer text-2xl font-bold"
                    @click="
                        $inertia.visit(route('user.details', { hashId: data.user.hash_id, name: data.user.name.split(' ').join('-').toLowerCase() }))
                    "
                >
                    {{ data.user.name }}
                </h1>
                <hr />
                <p>{{ data.content }}</p>
                <img v-if="data.image" :src="`/storage/${data.image}`" alt="Post Image" class="h-80 w-80 object-contain" />
                <p class="text-gray-400">Dibuat {{ formatTime(data.created_at) }}</p>
            </div>
            <div class="p-4">
                <h2 class="mt-4 text-xl font-bold">Komentar</h2>
                <div v-if="data.comments.length > 0" class="max-h-96 space-y-4 overflow-y-auto p-4">
                    <div class="space-y-1 p-2" v-for="comment in data.comments" :key="comment.id">
                        <div class="flex flex-row">
                            <div clas="flex flex-col">
                                <p class="font-bold">{{ comment.user.name }}</p>
                                <p>{{ comment.content }}</p>
                                <p class="text-gray-400">{{ formatTime(comment.created_at) }}</p>
                            </div>
                            <div class="">
                                <DropdownMenu>
                                    <DropdownMenuTrigger><EllipsisVertical class="cursor-pointer"></EllipsisVertical> </DropdownMenuTrigger>
                                    <DropdownMenuContent>
                                        <DropdownMenuItem>Laporkan</DropdownMenuItem>
                                        <DropdownMenuItem @click="deleteComment(comment.id)" v-if="comment.user.id === usePage().props.auth.user.id"
                                            >Hapus</DropdownMenuItem
                                        >
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <form @submit.prevent="submit" class="flex flex-col gap-2">
                        <Input class="w-8/12" v-model="form.content" placeholder="Kirim Komentar" />
                        <Button type="submit" class="w-fit">Kirim</Button>
                    </form>
                    <div v-if="form.errors.content" class="text-red-500">
                        {{ form.errors.content }}
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
