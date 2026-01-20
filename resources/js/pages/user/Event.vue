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
import axios from 'axios';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import relativeTime from 'dayjs/plugin/relativeTime';
import { Calendar, Clock, EllipsisVertical, Eye, Filter, Flag, Gem, Heart, MapPin, Plus, Search, Share2, Trash2, User } from 'lucide-vue-next';
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
    is_promoted: boolean;
    content: string;
    created_at: string;
    image: string;
    price: number | null;
    organizer: string;
    date_event: string;
    location: string;
    hash_id: string;
    promoted_till: string | null;
    interests_count?: number;

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
    currentFilter?: string;
    currentSearch?: string;
}>();

const promotionForm = ref({
    event_id: null as number | null,
    start_date: new Date().toISOString().split('T')[0],
    end_date: new Date(Date.now() + 86400000).toISOString().split('T')[0], // +1 day
});

const promotionCost = computed(() => {
    const start = new Date(promotionForm.value.start_date);
    const end = new Date(promotionForm.value.end_date);
    const days = Math.max(1, Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24)));
    return { days, total: days * 5000 };
});

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(amount);
};

const isPromotionDialogOpen = ref(false);

const selectedFilter = ref(props.currentFilter || 'terbaru');
const searchQuery = ref(props.currentSearch || '');

// Computed property for smart pagination
const paginationPages = computed(() => {
    const current = props.data.current_page;
    const last = props.data.last_page;
    const pages: number[] = [];

    if (last <= 7) {
        for (let i = 1; i <= last; i++) {
            pages.push(i);
        }
    } else {
        // Always include page 1
        pages.push(1);

        // Calculate range around current page
        let start = Math.max(2, current - 2);
        let end = Math.min(last - 1, current + 2);

        // Adjust if we're near the start
        if (current <= 4) {
            end = 6;
        }

        // Adjust if we're near the end
        if (current >= last - 3) {
            start = last - 5;
        }

        // Add ellipsis if needed
        if (start > 2) {
            pages.push(-1); // -1 represents ellipsis
        }

        // Add middle pages
        for (let i = start; i <= end; i++) {
            pages.push(i);
        }

        // Add ellipsis if needed
        if (end < last - 1) {
            pages.push(-1); // -1 represents ellipsis
        }

        // Always include last page
        pages.push(last);
    }

    return pages;
});

const changeFilter = (filter: string) => {
    selectedFilter.value = filter;
    router.get(
        route('user.event'),
        { filter, search: searchQuery.value },
        {
            preserveScroll: false,
            preserveState: true,
        },
    );
};

const performSearch = () => {
    router.get(
        route('user.event'),
        { filter: selectedFilter.value, search: searchQuery.value },
        {
            preserveScroll: false,
            preserveState: true,
        },
    );
};

const initiatePromotion = async (eventId: number) => {
    try {
        // Validate dates
        if (new Date(promotionForm.value.start_date) >= new Date(promotionForm.value.end_date)) {
            toast.error('Tanggal akhir harus setelah tanggal mulai');
            return;
        }

        const response = await axios.post(route('user.event.promotion.initiate'), {
            event_id: eventId,
            start_date: promotionForm.value.start_date,
            end_date: promotionForm.value.end_date,
        });

        const snapToken = response.data.snap_token;
        const orderId = response.data.order_id;

        if (snapToken && (window as any).snap) {
            // Close dialog before opening Snap to avoid z-index conflict
            isPromotionDialogOpen.value = false;

            // Wait for dialog animation to complete before opening Snap
            setTimeout(() => {
                let paymentMethodSelected = false;

                (window as any).snap.pay(snapToken, {
                    onSuccess: function () {
                        paymentMethodSelected = true;
                        toast.success('Pembayaran promosi berhasil! Event Anda akan dipromosikan.');
                        setTimeout(() => {
                            router.reload();
                        }, 1500);
                    },
                    onPending: function () {
                        paymentMethodSelected = true;
                        toast.info('Pembayaran sedang diproses.');
                        setTimeout(() => {
                            router.visit(route('user.transaction'));
                        }, 1500);
                    },
                    onError: function () {
                        paymentMethodSelected = true;
                        toast.error('Terjadi kesalahan. Silakan cek transaksi Anda.');
                        setTimeout(() => {
                            router.visit(route('user.transaction'));
                        }, 1500);
                    },
                    onClose: function () {
                        // If user closed without selecting payment method, cancel the transaction
                        if (!paymentMethodSelected) {
                            axios
                                .post(route('user.transaction.cancel', orderId))
                                .then(() => {
                                    toast.info('Pembayaran dibatalkan.');
                                })
                                .catch((error) => {
                                    console.error('Failed to cancel transaction:', error);
                                });
                        } else {
                            toast.info('Cek status pembayaran di menu Transaksi.');
                            router.visit(route('user.transaction'));
                        }
                    },
                });
            }, 500); // Increased delay to ensure dialog is fully closed
        } else {
            toast.error('Gagal memuat sistem pembayaran. Silakan refresh halaman dan coba lagi.');
        }
    } catch (error: any) {
        console.error('Failed to initiate promotion payment', error);
        toast.error(error.response?.data?.message || error.response?.data?.error || 'Gagal memulai pembayaran promosi');
    }
};
const isPromotedActive = (promotedTill: string | null) => {
    if (!promotedTill) return false;
    return new Date(promotedTill) >= new Date();
};

const goToPage = (page: number) => {
    if (page !== props.data.current_page && page >= 1 && page <= props.data.last_page) {
        router.get(
            route('user.event'),
            { page, filter: selectedFilter.value, search: searchQuery.value },
            {
                preserveScroll: false,
                preserveState: true,
            },
        );
    }
};

const nextPage = () => {
    if (props.data.next_page_url) {
        goToPage(props.data.current_page + 1);
    }
};

const backPage = () => {
    if (props.data.current_page > 1) {
        goToPage(props.data.current_page - 1);
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

function deleteEvent(hashId: string) {
    router.delete(route('user.delete.event', hashId), {
        preserveScroll: true,
        onSuccess: () => toast.success('Event berhasil dihapus'),
        onError: (errors) => {
            console.error('error delete event: ', errors);
            toast.error('Gagal menghapus event');
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
        <div class="relative mb-8 overflow-hidden rounded-b-2xl p-8 shadow-2xl">
            <div class="absolute inset-0 bg-gradient-to-r from-white/5 to-transparent"></div>

            <div class="relative flex flex-col gap-6 md:flex-row md:items-center md:justify-between dark:text-white">
                <div>
                    <h1 class="mb-3 text-4xl font-bold">Jelajahi Event</h1>
                    <p class="text-lg text-slate-400">Temukan event menarik di sekitar Anda</p>
                </div>

                <!-- For community, admin, moderator - direct navigation -->
                <Button
                    class="flex transform items-center gap-3 rounded-xl border border-white/20 bg-white/10 px-8 py-4 font-semibold text-black shadow-lg backdrop-blur-sm transition-all duration-300 hover:scale-105 hover:bg-white/15 dark:text-white"
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
                    <span class="font-medium">Halaman {{ data.current_page }} dari {{ data.last_page }}</span>
                </div>

                <!-- Search and Filter Controls -->
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-gray-400" />
                        <input
                            type="text"
                            v-model="searchQuery"
                            @keyup.enter="performSearch"
                            placeholder="Cari event... (tekan Enter)"
                            class="rounded-lg border border-gray-200 bg-gray-50 py-2 pr-4 pl-10 text-sm transition-all focus:border-transparent focus:ring-2 focus:ring-slate-500 dark:border-gray-600 dark:bg-gray-700"
                        />
                    </div>
                    <Button @click="performSearch" class="bg-slate-700 text-white hover:bg-slate-800">
                        <Search class="h-4 w-4" />
                    </Button>
                    <DropdownMenu>
                        <DropdownMenuTrigger class="flex items-center justify-center gap-2">
                            <Filter class="h-4 w-4" />
                            <span class="capitalize">
                                {{
                                    selectedFilter === 'terbaru'
                                        ? 'Terbaru'
                                        : selectedFilter === 'terlama'
                                          ? 'Terlama'
                                          : selectedFilter === 'dalam_waktu_dekat'
                                            ? 'Dalam waktu dekat'
                                            : 'Berlangsung'
                                }}
                            </span>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent>
                            <DropdownMenuItem @click="changeFilter('terbaru')">Terbaru</DropdownMenuItem>
                            <DropdownMenuItem @click="changeFilter('terlama')">Terlama</DropdownMenuItem>
                            <DropdownMenuItem @click="changeFilter('dalam_waktu_dekat')">Dalam waktu dekat</DropdownMenuItem>
                            <DropdownMenuItem @click="changeFilter('berlangsung')">Berlangsung</DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </div>

        <!-- Events Grid -->
        <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="event in data.data"
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
                            <div
                                v-if="event.is_promoted"
                                class="mt-2 flex flex-row items-center space-x-1 rounded-full bg-yellow-100/90 px-3 py-1 shadow-lg backdrop-blur-sm"
                            >
                                <Gem class="top-4 left-4 h-5 w-5 text-yellow-400" />
                                <p class="font-bold text-black">Dipromosikan</p>
                            </div>
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
                                        v-if="
                                            (event.user && event.user.id === usePage().props.auth.user.id) ||
                                            usePage().props.auth.user.role === 'admin' ||
                                            usePage().props.auth.user.role === 'moderator'
                                        "
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
                                                    <AlertDialogAction @click="deleteEvent(event.hash_id)">Continue</AlertDialogAction>
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

                            <!-- Interests Count -->
                            <div v-if="event.interests_count !== undefined" class="flex items-center gap-3 text-xs text-pink-600 dark:text-pink-400">
                                <Heart class="h-3 w-3" :class="event.interests_count > 0 ? 'fill-current' : ''" />
                                <span>{{ event.interests_count }} orang tertarik</span>
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
                        <div v-if="!isPromotedActive(event.promoted_till) && usePage().props.auth.user.id == event.user.id" class="mt-3">
                            <Button
                                class="group hover:shadow-3xl relative w-full overflow-hidden rounded-xl border-2 border-gray-900 bg-gradient-to-r from-gray-900 via-black to-gray-900 py-4 font-bold text-white shadow-2xl transition-all duration-500 hover:scale-[1.02] hover:border-gray-700 dark:border-gray-100 dark:from-gray-100 dark:via-white dark:to-gray-100 dark:text-black dark:hover:border-gray-300"
                                @click="$inertia.visit(route('user.event.promote.form', event.id))"
                            >
                                <div
                                    class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent opacity-0 transition-opacity duration-500 group-hover:opacity-100 dark:via-black/10"
                                ></div>
                                <div class="relative flex items-center justify-center gap-2">
                                    <Gem class="h-5 w-5 animate-pulse" />
                                    <span class="text-lg">Promosikan Event</span>
                                    <Gem class="h-5 w-5 animate-pulse" />
                                </div>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Pagination -->
        <div v-if="data.last_page > 1" class="mt-8 flex justify-center">
            <div class="flex items-center gap-2">
                <!-- Previous Button -->
                <Button
                    @click="backPage"
                    :disabled="data.current_page === 1"
                    class="rounded-lg px-4 py-2 font-medium transition-all"
                    :class="
                        data.current_page === 1
                            ? 'cursor-not-allowed bg-gray-200 text-gray-400 dark:bg-gray-700 dark:text-gray-500'
                            : 'bg-white text-slate-700 hover:bg-slate-100 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700'
                    "
                >
                    Previous
                </Button>

                <!-- Page Numbers -->
                <div class="flex items-center gap-1">
                    <template v-for="page in paginationPages" :key="page">
                        <!-- Ellipsis -->
                        <span v-if="page === -1" class="px-2 text-slate-600 dark:text-slate-400">...</span>

                        <!-- Page Button -->
                        <Button
                            v-else
                            @click="goToPage(page)"
                            :disabled="data.current_page === page"
                            class="min-w-[40px] rounded-lg px-3 py-2 font-medium transition-all"
                            :class="
                                data.current_page === page
                                    ? 'cursor-default bg-slate-700 text-white dark:bg-slate-600'
                                    : 'bg-white text-slate-700 hover:bg-slate-100 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700'
                            "
                        >
                            {{ page }}
                        </Button>
                    </template>
                </div>

                <!-- Next Button -->
                <Button
                    @click="nextPage"
                    :disabled="data.next_page_url === null"
                    class="rounded-lg px-4 py-2 font-medium transition-all"
                    :class="
                        data.next_page_url === null
                            ? 'cursor-not-allowed bg-gray-200 text-gray-400 dark:bg-gray-700 dark:text-gray-500'
                            : 'bg-white text-slate-700 hover:bg-slate-100 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700'
                    "
                >
                    Next
                </Button>
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
