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
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import relativeTime from 'dayjs/plugin/relativeTime';
import { AlertTriangle, CheckCircle, Clock, EllipsisVertical, Image as ImageIcon, MessageSquare, Send, X } from 'lucide-vue-next';

import { ref } from 'vue';

dayjs.locale('id');
dayjs.extend(relativeTime);

const successComment = ref<String | null>();
const reportValue = ref('spam');

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
                hash_id: string;
            };
        }>;
    };
}>();

console.log('content is ', props.data.content);

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
            },

            onError: (errors) => {
                successComment.value = 'Gagal mengirim laporan, periksa kembali jaringan anda';
                console.error(errors);
            },
        },
    );
}
function deletePost(id: number) {
    return router.delete(route('user.post.delete', id), {
        onError: (errors) => {
            console.error('error: ', errors);
            successComment.value = 'Terjadi kesalahan saat menghapus.';
        },
        onSuccess: (success) => {},
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
</script>

<template>
    <Head title="Detail Post" />

    <AppLayout :breadcrumbs="breadcrumbs">
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
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-sm font-semibold text-white"
                                >
                                    {{ getInitials(data.user.name) }}
                                </div>
                            </div>
                            <div class="flex-1">
                                <h1
                                    class="cursor-pointer text-xl font-bold text-gray-900 transition-colors hover:text-indigo-600 dark:text-white"
                                    @click="
                                        $inertia.visit(
                                            route('user.details', {
                                                hashId: data.user.hash_id,
                                                name: data.user.name.split(' ').join('-').toLowerCase(),
                                            }),
                                        )
                                    "
                                >
                                    {{ data.user.name }}
                                </h1>
                                <div class="mt-1 flex items-center gap-2 text-sm text-gray-500">
                                    <Clock class="h-4 w-4" />
                                    <span>Dibuat {{ formatTime(data.created_at) }}</span>
                                </div>
                            </div>
                            <div>
                                <DropdownMenu>
                                    <DropdownMenuTrigger><EllipsisVertical></EllipsisVertical></DropdownMenuTrigger>
                                    <DropdownMenuContent>
                                        <DropdownMenuItem
                                            @click="
                                                $inertia.visit(
                                                    route('user.details', {
                                                        hashId: data.user.hash_id,
                                                        name: data.user.name.toLowerCase().split(' ').join('-'),
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

                                        <DropdownMenuItem v-if="data.user.id === usePage().props.auth.user.id" @click="deletePost(data.id)"
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
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ data.comments.length }} komentar</p>
                            </div>
                        </div>
                    </div>

                    <!-- Comments List -->
                    <div class="divide-y divide-gray-100">
                        <div v-if="data.comments.length > 0" class="max-h-96 overflow-y-auto">
                            <div
                                v-for="comment in data.comments"
                                :key="comment.id"
                                class="bark:bg-gray-700 p-6 transition-colors hover:bg-gray-50 hover:dark:bg-gray-700"
                            >
                                <div class="flex gap-4">
                                    <!-- Avatar -->
                                    <div class="flex-shrink-0">
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-400 to-cyan-500 text-sm font-medium text-white"
                                        >
                                            {{ getInitials(comment.user.name) }}
                                        </div>
                                    </div>

                                    <!-- Comment Content -->
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-start justify-between gap-4">
                                            <div class="min-w-0 flex-1">
                                                <h4
                                                    class="mb-1 cursor-pointer truncate font-semibold text-gray-900 hover:underline dark:text-white"
                                                    @click="
                                                        $inertia.visit(
                                                            route('user.details', {
                                                                hashId: comment.user.hash_id,
                                                                name: comment.user.name.split(' ').join('-').toLowerCase(),
                                                            }),
                                                        )
                                                    "
                                                >
                                                    {{ comment.user.name }}
                                                </h4>
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
                                                            v-if="comment.user.id === usePage().props.auth.user.id"
                                                            @click="deleteComment(comment.id)"
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
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-violet-400 to-purple-500 text-sm font-medium text-white"
                                    >
                                        {{ getInitials(usePage().props.auth.user.name) }}
                                    </div>
                                </div>
                                <div class="flex-1 space-y-3">
                                    <div class="relative">
                                        <Input
                                            v-model="form.content"
                                            placeholder="Tulis komentar Anda..."
                                            class="focus:ring-opacity-50 w-full rounded-xl border-gray-200 py-3 pr-12 focus:border-indigo-300 focus:ring focus:ring-indigo-200"
                                            :disabled="form.processing"
                                        />
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
