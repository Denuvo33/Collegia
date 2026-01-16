<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Toaster } from '@/components/ui/sonner';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import relativeTime from 'dayjs/plugin/relativeTime';
import { Calendar, Edit, Heart, MapPin, Share2, Trash2, Users, Workflow } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import 'vue-sonner/style.css';

dayjs.extend(relativeTime);
dayjs.locale('id');

type User = {
    id: number;
    name: string;
    email: string;
    role: string;
    hash_id: string;
    clean_name: string;
};

type Collaboration = {
    id: number;
    user_id: number;
    title: string;
    description: string;
    type: 'collaboration' | 'volunteer';
    start_date: string | null;
    end_date: string | null;
    image: string | null;
    location: string | null;
    participants_needed: number | null;
    hash_id: string;
    created_at: string;
    user: User;
    interests_count?: number;
    interested_users?: User[];
};

const props = defineProps<{
    collaboration: Collaboration;
    isInterested: boolean;
}>();

const page = usePage();
const currentUser = page.props.auth?.user as unknown as User | undefined;
const isOwner = currentUser?.id === props.collaboration.user_id;

const isInterested = ref(props.isInterested);
const interestsCount = ref(props.collaboration.interests_count || 0);
const interestedUsers = ref<User[]>(props.collaboration.interested_users || []);
const isLoading = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Home', href: '/user/home' },
    { title: props.collaboration.title, href: `/user/collaboration/${props.collaboration.hash_id}` },
];

function formatDate(date: string) {
    return dayjs(date).format('DD MMMM YYYY, HH:mm');
}

function formatRelativeDate(date: string) {
    return dayjs(date).fromNow();
}

function copyUrl() {
    const url = `${window.location.origin}/user/collaboration/${props.collaboration.hash_id}`;
    navigator.clipboard.writeText(url).then(() => {
        toast.success('Link berhasil disalin!', { duration: 3000 });
    });
}

async function toggleInterest() {
    if (isLoading.value) return;
    isLoading.value = true;

    try {
        const response = await axios.post(route('user.collaboration.interest', props.collaboration.hash_id));
        isInterested.value = response.data.isInterested;
        interestsCount.value = response.data.interestsCount;

        if (isInterested.value) {
            toast.success('Anda tertarik dengan kolaborasi ini!');
        } else {
            toast.info('Anda sudah tidak tertarik lagi.');
        }
    } catch (error) {
        toast.error('Gagal memproses permintaan.');
    } finally {
        isLoading.value = false;
    }
}

function editCollaboration() {
    router.visit(route('user.collaboration.edit', props.collaboration.hash_id));
}

function deleteCollaboration() {
    router.delete(route('user.collaboration.delete', props.collaboration.hash_id), {
        onError: () => toast.error('Gagal menghapus kolaborasi.'),
    });
}

watch(
    () => page.props.flash,
    (flash: any) => {
        if (flash?.success) toast.success(flash.success, { duration: 5000 });
        if (flash?.error) toast.error(flash.error, { duration: 5000 });
    },
    { deep: true, immediate: true },
);
</script>

<template>
    <Head :title="collaboration.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster />
        <div class="mx-auto max-w-4xl p-8">
            <Card class="overflow-hidden">
                <!-- Image Header -->
                <div v-if="collaboration.image" class="relative h-64 w-full bg-gray-100 dark:bg-gray-800">
                    <img :src="`/storage/${collaboration.image}`" :alt="collaboration.title" class="h-full w-full object-contain" />
                    <div class="absolute top-4 left-4">
                        <span
                            :class="collaboration.type === 'collaboration' ? 'bg-green-500' : 'bg-purple-500'"
                            class="rounded-full px-3 py-1 text-sm font-semibold text-white"
                        >
                            {{ collaboration.type === 'collaboration' ? 'Kolaborasi' : 'Relawan' }}
                        </span>
                    </div>
                </div>

                <CardHeader>
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <!-- Type Badge (if no image) -->
                            <div v-if="!collaboration.image" class="mb-2">
                                <span
                                    :class="collaboration.type === 'collaboration' ? 'bg-green-500' : 'bg-purple-500'"
                                    class="rounded-full px-3 py-1 text-sm font-semibold text-white"
                                >
                                    {{ collaboration.type === 'collaboration' ? 'Kolaborasi' : 'Relawan' }}
                                </span>
                            </div>

                            <CardTitle class="text-2xl">{{ collaboration.title }}</CardTitle>

                            <!-- Author Info -->
                            <div class="mt-3 flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 text-lg font-bold dark:bg-gray-700">
                                    {{ collaboration.user.name.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <p class="font-semibold">
                                        {{ collaboration.user.role === 'user' ? collaboration.user.clean_name : collaboration.user.name }}
                                    </p>
                                    <p class="text-sm text-gray-500">{{ formatRelativeDate(collaboration.created_at) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-2">
                            <Button variant="outline" size="icon" @click="copyUrl">
                                <Share2 class="h-4 w-4" />
                            </Button>
                            <template v-if="isOwner">
                                <Button variant="outline" size="icon" @click="editCollaboration">
                                    <Edit class="h-4 w-4" />
                                </Button>
                                <AlertDialog>
                                    <AlertDialogTrigger as-child>
                                        <Button variant="destructive" size="icon">
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </AlertDialogTrigger>
                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle>Hapus Kolaborasi?</AlertDialogTitle>
                                            <AlertDialogDescription>
                                                Anda yakin ingin menghapus kolaborasi ini? Tindakan ini tidak dapat dibatalkan.
                                            </AlertDialogDescription>
                                        </AlertDialogHeader>
                                        <AlertDialogFooter>
                                            <AlertDialogCancel>Batal</AlertDialogCancel>
                                            <Button variant="destructive" @click="deleteCollaboration">Hapus</Button>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog>
                            </template>
                        </div>
                    </div>
                </CardHeader>

                <CardContent class="space-y-6">
                    <!-- Interest Section -->
                    <div
                        class="flex items-center justify-between rounded-lg bg-gradient-to-r from-pink-50 to-red-50 p-4 dark:from-pink-950 dark:to-red-950"
                    >
                        <div class="flex items-center gap-3">
                            <Heart :class="isInterested ? 'fill-red-500 text-red-500' : 'text-gray-400'" class="h-6 w-6" />
                            <div>
                                <p class="font-semibold">{{ interestsCount }} orang tertarik</p>
                                <p class="text-sm text-gray-500">{{ isInterested ? 'Termasuk Anda' : 'Klik untuk menunjukkan minat' }}</p>
                            </div>
                        </div>
                        <Button :variant="isInterested ? 'default' : 'outline'" :disabled="isLoading" @click="toggleInterest">
                            <Heart :class="isInterested ? 'fill-current' : ''" class="mr-2 h-4 w-4" />
                            {{ isInterested ? 'Tertarik' : 'Saya Tertarik' }}
                        </Button>
                    </div>

                    <!-- Description -->
                    <div>
                        <h3 class="mb-2 text-lg font-semibold">Deskripsi</h3>
                        <p class="whitespace-pre-wrap text-gray-700 dark:text-gray-300">{{ collaboration.description }}</p>
                    </div>

                    <!-- Info Grid -->
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <!-- Dates -->
                        <div
                            v-if="collaboration.start_date || collaboration.end_date"
                            class="flex items-start gap-3 rounded-lg bg-gray-50 p-4 dark:bg-gray-800"
                        >
                            <Calendar class="mt-0.5 h-5 w-5 text-blue-500" />
                            <div>
                                <p class="font-semibold">Tanggal</p>
                                <p v-if="collaboration.start_date" class="text-sm text-gray-600 dark:text-gray-400">
                                    Mulai: {{ formatDate(collaboration.start_date) }}
                                </p>
                                <p v-if="collaboration.end_date" class="text-sm text-gray-600 dark:text-gray-400">
                                    Berakhir: {{ formatDate(collaboration.end_date) }}
                                </p>
                            </div>
                        </div>

                        <!-- Location -->
                        <div v-if="collaboration.location" class="flex items-start gap-3 rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                            <MapPin class="mt-0.5 h-5 w-5 text-red-500" />
                            <div>
                                <p class="font-semibold">Lokasi</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ collaboration.location }}</p>
                            </div>
                        </div>

                        <!-- Participants Needed -->
                        <div v-if="collaboration.participants_needed" class="flex items-start gap-3 rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                            <Users class="mt-0.5 h-5 w-5 text-green-500" />
                            <div>
                                <p class="font-semibold">Dibutuhkan</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ collaboration.participants_needed }} orang</p>
                            </div>
                        </div>

                        <!-- Type -->
                        <div class="flex items-start gap-3 rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                            <Workflow class="mt-0.5 h-5 w-5 text-purple-500" />
                            <div>
                                <p class="font-semibold">Tipe</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ collaboration.type === 'collaboration' ? 'Kolaborasi / Partnership' : 'Relawan / Volunteer' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Interested Users List -->
                    <div v-if="interestedUsers.length > 0">
                        <h3 class="mb-3 text-lg font-semibold">Yang Tertarik</h3>
                        <div class="flex flex-wrap gap-2">
                            <div
                                v-for="user in interestedUsers"
                                :key="user.id"
                                class="flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1 dark:bg-gray-700"
                            >
                                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-white text-xs font-bold text-black">
                                    {{ user.name.charAt(0).toUpperCase() }}
                                </div>
                                <span class="text-sm">{{ user.role === 'user' ? user.clean_name : user.name }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Back Button -->
                    <div class="border-t pt-6">
                        <Button variant="outline" @click="$inertia.visit(route('user.home'))"> Kembali ke Home </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
