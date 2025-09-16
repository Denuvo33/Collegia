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
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Toaster } from '@/components/ui/sonner';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import relativeTime from 'dayjs/plugin/relativeTime';
import { Calendar, EllipsisVertical, MapPin, User, Wallet } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import 'vue-sonner/style.css';

dayjs.locale('id');
dayjs.extend(relativeTime);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Home',
        href: '/user/home',
    },
    {
        title: 'Detail event',
        href: '/user/event/details',
    },
];

const reportValue = ref('spam');

defineProps<{
    event: {
        id: number;
        title: string;
        content: string;
        price: number;
        location: string;
        organizer: string;
        date_event: string;
        image: string | null;
        created_at: string;
        status: string;

        user: {
            id: number;
            name: string;
        };
    };
}>();
function goBack() {
    window.history.back();
}

watch(
    () => usePage().props.flash,

    (flash: any) => {
        if (flash.success) {
            toast.success(flash.success, {
                dismissible: true,
                duration: 4000,
            });
        }
        if (flash.error) {
            toast.error(flash.error, {
                dismissible: true,
                duration: 4000,
            });
        }
    },
    {
        deep: true,
        immediate: true,
    },
);

function getEventStatus(dateEvent: string): 'Akan Datang' | 'Sedang Berjalan' | 'Selesai' {
    const today = new Date();
    const eventDate = new Date(dateEvent);

    today.setHours(0, 0, 0, 0);
    eventDate.setHours(0, 0, 0, 0);

    if (eventDate > today) {
        return 'Akan Datang';
    } else if (eventDate.getTime() === today.getTime()) {
        return 'Sedang Berjalan';
    } else {
        return 'Selesai';
    }
}

function deleteEvent(id: number) {
    router.delete(route('user.delete.event', { id: id, userId: usePage().props.auth.user.id }));
}

function reportEvent(id: number) {
    router.post(
        route('user.event.report'),
        {
            event_id: id,
            reason: reportValue.value,
        },
        {
            preserveScroll: true,
        },
    );
}

function statusClass(status: string) {
    switch (status) {
        case 'Akan Datang':
            return 'bg-blue-100 text-blue-700';
        case 'Sedang Berjalan':
            return 'bg-green-100 text-green-700';
        case 'Selesai':
            return 'bg-gray-200 text-gray-600';
        default:
            return '';
    }
}
function formatTime(date: string) {
    return dayjs(date).fromNow();
}
</script>

<template>
    <Head :title="event.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster />

        <!-- Nuxt, vue-sonner v1 because inserting inline CSS with JS to the head tag -->
        <ClientOnly>
            <Toaster />
        </ClientOnly>

        <!-- Nuxt, vue-sonner v2 no need to ClientOnly -->
        <Toaster />
        <div class="mx-auto max-w-4xl p-6">
            <div class="mb-6 space-y-2">
                <div class="flex flex-row justify-between">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                        {{ event.title }}
                    </h1>
                    <DropdownMenu>
                        <DropdownMenuTrigger><EllipsisVertical></EllipsisVertical></DropdownMenuTrigger>
                        <DropdownMenuContent>
                            <DropdownMenuItem @select.prevent v-if="event.user.id === usePage().props.auth.user.id">
                                <AlertDialog>
                                    <AlertDialogTrigger>Hapus</AlertDialogTrigger>
                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle>Apakah Anda yakin?</AlertDialogTitle>
                                            <AlertDialogDescription> Anda akan menghapus event ini </AlertDialogDescription>
                                        </AlertDialogHeader>
                                        <AlertDialogFooter>
                                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                                            <AlertDialogAction @click="deleteEvent(event.id)">Continue</AlertDialogAction>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog></DropdownMenuItem
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
                                        <AlertDialogAction @click="reportEvent(event.id)">Kirim</AlertDialogAction>
                                    </AlertDialogFooter>
                                </AlertDialogContent>
                            </AlertDialog>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
                <p class="text-gray-600 dark:text-gray-400">
                    Diselenggarakan oleh
                    <span class="font-semibold">{{ event.organizer }}</span>
                </p>

                <span :class="['inline-block rounded-full px-3 py-1 text-sm font-semibold', statusClass(getEventStatus(event.date_event))]">
                    {{ getEventStatus(event.date_event) }}
                </span>
            </div>

            <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                    <Calendar class="h-5 w-5" />
                    <span>{{ new Date(event.date_event).toLocaleDateString() }}</span>
                </div>

                <div class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                    <MapPin class="h-5 w-5" />
                    <span>{{ event.location }}</span>
                </div>

                <div class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                    <User class="h-5 w-5" />
                    <span>{{ event.user?.name }}</span>
                </div>

                <div class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                    <Wallet class="h-5 w-5" />
                    <span>{{ event.price == 0 ? 'Gratis' : 'Rp ' + event.price }}</span>
                </div>
            </div>

            <div class="text-gray-500">Dibuat {{ formatTime(event.created_at) }}</div>

            <div class="prose dark:prose-invert max-w-none">
                <h2 class="mb-2 text-xl font-semibold">Tentang Event</h2>
                <p>{{ event.content }}</p>
            </div>

            <div v-if="event.image" class="mb-6">
                <img :src="`/storage/${event.image}`" alt="Event" class="h-120 w-fit rounded-2xl object-contain shadow-md" />
            </div>

            <div class="mt-10 flex justify-end">
                <Button @click="goBack"> Kembali </Button>
            </div>
        </div>
    </AppLayout>
</template>
