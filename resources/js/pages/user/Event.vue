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
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import relativeTime from 'dayjs/plugin/relativeTime';
import { Calendar, Clock, EllipsisVertical, Eye, Filter, Flag, MapPin, Plus, Search, Share2, Trash2, User } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

import 'vue-sonner/style.css';

dayjs.locale('id');
dayjs.extend(relativeTime);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Event',
        href: '/user/event',
    },
];

type Event = {
    id: number;
    title: string;
    content: string;
    created_at: string;
    image: string;
    price: number | null;
    organizer: string;
    date_event: string;
    location: string;
    hash_id: string;

    user: {
        id: number;
        name: string;
    };
};

const reportValue = ref('spam');

const props = defineProps<{
    data: {
        data: Event[];
        current_page: number;
        last_page: number;
        next_page_url?: string | null;
    };
}>();

const searchList = [...props.data.data];
const searchQuery = ref('');

function searchEvent(list: typeof props.data.data, query: string) {
    if (!query) return list;
    return list.filter(
        (event) => event.title.toLowerCase().includes(query.toLowerCase()) || event.organizer.toLowerCase().includes(query.toLowerCase()),
    );
}

const filteredList = computed(() => {
    const result = searchEvent(searchList, searchQuery.value);
    return sortList(result);
});

const nextPage = () => {
    if (props.data.next_page_url) {
        router.get(props.data.next_page_url);
    }
};

const backPage = () => {
    if (props.data.current_page > 1) {
        router.get(route('user.event', { page: props.data.current_page - 1 }));
    }
};

watch(
    () => usePage().props.flash,
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

function sortList<T extends { created_at: string }>(list: Array<T>) {
    return list.slice().sort((a, b) => {
        const dateA = new Date(a.created_at);
        const dateB = new Date(b.created_at);

        return dateB.getTime() - dateA.getTime();
    });
}

function oldListSort<T extends { created_at: string }>(list: Array<T>) {
    return list.slice().sort((a, b) => {
        const dateA = new Date(a.created_at);
        const dateB = new Date(b.created_at);

        return dateA.getTime() - dateB.getTime();
    });
}

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
function formatTime(date: string) {
    return dayjs(date).fromNow();
}

function deleteEvent(id: number) {
    router.delete(route('user.delete.event', { id: id, userId: usePage().props.auth.user.id }), {
        preserveScroll: true,
        onError: (errors) => {
            console.error('error delete event: ', errors);
        },
        preserveState: false,
    });
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
            onError: (errors) => {
                console.error('error report event: ', errors);
            },
        },
    );
}

function copyEvent(hashId: string) {
    const postUrl = `${window.location.origin}/user/event/${hashId}`;
    navigator.clipboard.writeText(postUrl).then(() => {
        toast.success('Event url telah disalin');
    });
}
</script>

<template>
    <Head title="Event" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster />

        <!-- Hero Section -->
        <div class="relative mb-8 overflow-hidden rounded-b-2xl bg-gradient-to-br from-slate-800 via-slate-700 to-slate-600 p-8 shadow-2xl">
            <div class="absolute inset-0 bg-gradient-to-r from-white/5 to-transparent"></div>
            <div class="absolute top-6 right-6 h-16 w-16 animate-pulse rounded-full bg-white/10"></div>
            <div class="absolute bottom-6 left-6 h-12 w-12 animate-pulse rounded-full bg-white/5 delay-700"></div>

            <div class="relative flex flex-col gap-6 text-white md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="mb-3 text-4xl font-bold">Jelajahi Event</h1>
                    <p class="text-lg text-slate-200">Temukan event menarik di sekitar Anda</p>
                </div>
                <Button
                    class="flex transform items-center gap-3 rounded-xl border border-white/20 bg-white/10 px-8 py-4 font-semibold text-white shadow-lg backdrop-blur-sm transition-all duration-300 hover:scale-105 hover:bg-white/15"
                    @click="$inertia.visit(route('user.create-event'))"
                >
                    <Plus class="h-5 w-5" />
                    Buat Event Baru
                </Button>
            </div>
        </div>

        <!-- Filter & Search Section -->
        <div class="mb-8 border border-gray-100 bg-white p-6 shadow-lg dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-col items-center justify-between gap-4 md:flex-row">
                <div class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                    <Calendar class="h-5 w-5" />
                    <span class="font-medium">{{ data.data.length }} Event Tersedia</span>
                </div>

                <!-- Search and Filter Controls -->
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-gray-400" />
                        <input
                            type="text"
                            v-model="searchQuery"
                            placeholder="Cari event..."
                            class="rounded-lg border border-gray-200 bg-gray-50 py-2 pr-4 pl-10 text-sm transition-all focus:border-transparent focus:ring-2 focus:ring-slate-500 dark:border-gray-600 dark:bg-gray-700"
                        />
                    </div>
                    <DropdownMenu>
                        <DropdownMenuTrigger class="flex items-center justify-center gap-2">
                            <Filter class="h-4 w-4" />
                            Filter
                        </DropdownMenuTrigger>
                        <DropdownMenuContent>
                            <DropdownMenuItem>Terbaru</DropdownMenuItem>
                            <DropdownMenuItem>Terlama</DropdownMenuItem>
                            <DropdownMenuItem>Dalam waktu dekat</DropdownMenuItem>
                            <DropdownMenuItem>Berlangsung</DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </div>

        <!-- Events Grid -->
        <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="event in filteredList"
                :key="event.id"
                class="group transform transition-all duration-300 hover:-translate-y-2 hover:scale-[1.02]"
            >
                <div
                    class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-lg transition-all duration-300 hover:shadow-2xl dark:border-gray-700 dark:bg-gray-800"
                >
                    <!-- Event Image -->
                    <div class="relative h-48 bg-gradient-to-br from-slate-400 via-slate-500 to-slate-600">
                        <img
                            v-if="event.image"
                            :src="`/storage/${event.image}`"
                            :alt="event.title"
                            class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                        />
                        <div v-else class="flex h-full items-center justify-center">
                            <Calendar class="h-16 w-16 text-white/80" />
                        </div>

                        <!-- Status Badge -->
                        <div class="absolute top-4 left-4">
                            <span
                                class="rounded-full border border-white/20 px-3 py-1 text-xs font-bold shadow-lg backdrop-blur-sm"
                                :class="
                                    getEventStatus(event.date_event) == 'Akan Datang'
                                        ? 'bg-blue-500/90 text-white'
                                        : getEventStatus(event.date_event) == 'Sedang Berjalan'
                                          ? 'bg-green-500/90 text-white'
                                          : 'bg-gray-500/90 text-white'
                                "
                            >
                                {{ getEventStatus(event.date_event) }}
                            </span>
                        </div>

                        <!-- Price Badge -->
                        <div class="absolute top-4 right-4 rounded-lg bg-white/90 px-3 py-1 shadow-lg backdrop-blur-sm">
                            <span v-if="event.price" class="text-sm font-bold text-slate-800">
                                {{ event.price == 0 ? 'GRATIS' : `Rp ${event.price.toLocaleString()}` }}
                            </span>
                        </div>

                        <!-- Menu Dropdown -->
                        <div class="absolute right-4 bottom-4">
                            <DropdownMenu>
                                <DropdownMenuTrigger
                                    class="rounded-full border border-white/20 bg-gray-500 p-2 backdrop-blur-sm transition-all hover:bg-gray-700"
                                >
                                    <EllipsisVertical class="h-5 w-5 text-white" />
                                </DropdownMenuTrigger>
                                <DropdownMenuContent>
                                    <DropdownMenuItem
                                        @select.prevent
                                        v-if="event.user && event.user.id === usePage().props.auth.user.id"
                                        class="text-red-600 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-900/20"
                                    >
                                        <Trash2 class="mr-2 h-4 w-4" />
                                        <AlertDialog>
                                            <AlertDialogTrigger>Hapus</AlertDialogTrigger>
                                            <AlertDialogContent>
                                                <AlertDialogHeader>
                                                    <AlertDialogTitle>Apakah anda yakin?</AlertDialogTitle>
                                                    <AlertDialogDescription> Anda akan menghapus event ini. </AlertDialogDescription>
                                                </AlertDialogHeader>
                                                <AlertDialogFooter>
                                                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                    <AlertDialogAction @click="deleteEvent(event.id)">Continue</AlertDialogAction>
                                                </AlertDialogFooter>
                                            </AlertDialogContent>
                                        </AlertDialog>
                                    </DropdownMenuItem>
                                    <AlertDialog>
                                        <AlertDialogTrigger as-child>
                                            <DropdownMenuItem @select.prevent><Flag></Flag> Laporkan</DropdownMenuItem>
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
                                    <DropdownMenuItem @click="copyEvent(`${event.hash_id}/${event.title.split(' ').join('-').toLowerCase()}`)">
                                        <Share2 class="mr-2 h-4 w-4" />
                                        Bagikan
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>

                    <!-- Event Content -->
                    <div class="p-6">
                        <!-- Title -->
                        <h3
                            class="mb-3 line-clamp-2 text-xl font-bold text-slate-900 transition-colors group-hover:text-slate-700 dark:text-white dark:group-hover:text-slate-200"
                        >
                            {{ event.title }}
                        </h3>

                        <!-- Description -->
                        <p class="mb-4 line-clamp-3 leading-relaxed text-slate-600 dark:text-slate-400">
                            {{ event.content }}
                        </p>

                        <!-- Event Meta -->
                        <div class="mb-6 space-y-3">
                            <!-- Organizer -->
                            <div class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-slate-500 to-slate-600">
                                    <User class="h-4 w-4 text-white" />
                                </div>
                                <span class="font-medium">{{ event.organizer || 'Organizer' }}</span>
                            </div>

                            <!-- Date -->
                            <div class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-700">
                                    <Calendar class="h-4 w-4 text-slate-600 dark:text-slate-400" />
                                </div>
                                <span>{{
                                    new Date(event.date_event).toLocaleDateString('id-ID', {
                                        weekday: 'long',
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                    })
                                }}</span>
                            </div>

                            <!-- Location -->
                            <div class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-700">
                                    <MapPin class="h-4 w-4 text-slate-600 dark:text-slate-400" />
                                </div>
                                <span class="line-clamp-1">{{ event.location }}</span>
                            </div>

                            <!-- Created Date -->
                            <div class="flex items-center gap-3 text-xs text-slate-500">
                                <Clock class="h-3 w-3" />
                                <span>Dibuat {{ formatTime(event.created_at) }}</span>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <Button
                            class="flex w-full transform items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-slate-700 to-slate-800 py-3 font-semibold text-white shadow-lg transition-all duration-300 hover:scale-105 hover:from-slate-800 hover:to-slate-900 hover:shadow-xl"
                            @click="
                                $inertia.visit(
                                    route('user.details.event', {
                                        hashId: event.hash_id,
                                        title: event.title.toLowerCase().split(' ').join('-'),
                                    }),
                                )
                            "
                        >
                            <Eye class="h-4 w-4" />
                            Lihat Detail Event
                        </Button>
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="mt-8 flex flex-row justify-center gap-4">
                <Button class="" @click="backPage" :disabled="data.current_page == 1">Previous</Button>
                <Button @click="nextPage" :disabled="props.data.next_page_url == null">Next</Button>
            </div>
        </div>
        <!-- Empty State -->
        <div v-if="data.data.length === 0" class="flex items-center justify-center py-20">
            <div class="max-w-md text-center">
                <div class="mx-auto mb-6 flex h-24 w-24 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                    <Calendar class="h-12 w-12 text-slate-400" />
                </div>
                <h3 class="mb-3 text-xl font-bold text-slate-700 dark:text-slate-300">Belum Ada Event</h3>
                <p class="mb-6 leading-relaxed text-slate-500 dark:text-slate-400">Jadilah yang pertama membuat event menarik untuk komunitas</p>
                <Button
                    class="flex transform items-center gap-2 rounded-xl bg-gradient-to-r from-slate-700 to-slate-800 px-8 py-3 font-semibold text-white shadow-lg transition-all duration-300 hover:scale-105 hover:from-slate-800 hover:to-slate-900 hover:shadow-xl"
                    @click="$inertia.visit(route('user.create-event'))"
                >
                    <Plus class="h-5 w-5" />
                    Buat Event Pertama
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
