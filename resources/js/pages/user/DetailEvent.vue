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
import { Card, CardContent } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Separator } from '@/components/ui/separator';
import { Toaster } from '@/components/ui/sonner';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import relativeTime from 'dayjs/plugin/relativeTime';
import { ArrowLeft, Building2, Calendar, Clock, EllipsisVertical, MapPin, Share2, User } from 'lucide-vue-next';
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
            role: string;
            clean_name: string;
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
            return 'bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700';
        case 'Sedang Berjalan':
            return 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-800';
        case 'Selesai':
            return 'bg-neutral-100 text-neutral-600 border border-neutral-200 dark:bg-neutral-800 dark:text-neutral-400 dark:border-neutral-700';
        default:
            return '';
    }
}

function formatTime(date: string) {
    return dayjs(date).fromNow();
}

function copyEvent() {
    const url = window.location.href;
    navigator.clipboard.writeText(url).then(
        () => {
            toast.success('Link event disalin ke clipboard!', {
                duration: 3000,
                dismissible: true,
            });
        },
        () => {
            toast.error('Gagal menyalin link event.', {
                duration: 3000,
                dismissible: true,
            });
        },
    );
}

function formatPrice(price: number) {
    if (price === 0) return 'Gratis';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(price);
}
</script>

<template>
    <Head :title="event.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster />

        <!-- Hero Section -->
        <div class="bg-gradient-to-br from-slate-50 via-white to-slate-100 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900">
            <div class="mx-auto max-w-6xl px-6 py-12">
                <!-- Navigation -->
                <div class="mb-8 flex items-center justify-between">
                    <Button
                        variant="ghost"
                        size="sm"
                        @click="goBack"
                        class="text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100"
                    >
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Kembali
                    </Button>

                    <div class="flex items-center gap-2">
                        <Button
                            variant="ghost"
                            @click="copyEvent"
                            size="sm"
                            class="text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100"
                        >
                            <Share2 class="h-4 w-4" />
                        </Button>

                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100"
                                >
                                    <EllipsisVertical class="h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-48">
                                <DropdownMenuItem
                                    @select.prevent
                                    v-if="event.user.id === usePage().props.auth.user.id"
                                    class="text-red-600 focus:text-red-600"
                                >
                                    <AlertDialog>
                                        <AlertDialogTrigger class="w-full text-left">Hapus Event</AlertDialogTrigger>
                                        <AlertDialogContent>
                                            <AlertDialogHeader>
                                                <AlertDialogTitle>Konfirmasi Penghapusan</AlertDialogTitle>
                                                <AlertDialogDescription>
                                                    Event ini akan dihapus secara permanen dan tidak dapat dikembalikan.
                                                </AlertDialogDescription>
                                            </AlertDialogHeader>
                                            <AlertDialogFooter>
                                                <AlertDialogCancel>Batal</AlertDialogCancel>
                                                <AlertDialogAction @click="deleteEvent(event.id)" class="bg-red-600 hover:bg-red-700">
                                                    Hapus
                                                </AlertDialogAction>
                                            </AlertDialogFooter>
                                        </AlertDialogContent>
                                    </AlertDialog>
                                </DropdownMenuItem>

                                <AlertDialog>
                                    <AlertDialogTrigger as-child>
                                        <DropdownMenuItem @select.prevent class="text-amber-600 focus:text-amber-600">
                                            Laporkan Event
                                        </DropdownMenuItem>
                                    </AlertDialogTrigger>
                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle>Laporkan Event</AlertDialogTitle>
                                            <AlertDialogDescription>
                                                Pilih alasan pelaporan. Tim moderator akan meninjau laporan Anda.
                                            </AlertDialogDescription>
                                            <div class="mt-4">
                                                <RadioGroup v-model="reportValue" default-value="spam" class="space-y-3">
                                                    <div class="flex items-center space-x-3">
                                                        <RadioGroupItem id="r1" value="spam" />
                                                        <Label for="r1" class="font-medium">Spam</Label>
                                                    </div>
                                                    <div class="flex items-center space-x-3">
                                                        <RadioGroupItem id="r2" value="inappropriate" />
                                                        <Label for="r2" class="font-medium">Tidak Pantas</Label>
                                                    </div>
                                                    <div class="flex items-center space-x-3">
                                                        <RadioGroupItem id="r3" value="harassment" />
                                                        <Label for="r3" class="font-medium">Pelecehan</Label>
                                                    </div>
                                                    <div class="flex items-center space-x-3">
                                                        <RadioGroupItem id="r4" value="abusive" />
                                                        <Label for="r4" class="font-medium">Kekerasan</Label>
                                                    </div>
                                                    <div class="flex items-center space-x-3">
                                                        <RadioGroupItem id="r5" value="porn" />
                                                        <Label for="r5" class="font-medium">Pornografi</Label>
                                                    </div>
                                                    <div class="flex items-center space-x-3">
                                                        <RadioGroupItem id="r6" value="pretend to be other people" />
                                                        <Label for="r6" class="font-medium">Identitas Palsu</Label>
                                                    </div>
                                                </RadioGroup>
                                            </div>
                                        </AlertDialogHeader>
                                        <AlertDialogFooter>
                                            <AlertDialogCancel>Batal</AlertDialogCancel>
                                            <AlertDialogAction @click="reportEvent(event.id)" class="bg-amber-600 hover:bg-amber-700">
                                                Kirim Laporan
                                            </AlertDialogAction>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>

                <!-- Event Header -->
                <div class="grid grid-cols-1 gap-12 lg:grid-cols-3">
                    <!-- Event Image -->
                    <div class="lg:col-span-2">
                        <div class="relative mb-8">
                            <div v-if="event.image" class="overflow-hidden rounded-2xl shadow-2xl">
                                <img
                                    :src="`/storage/${event.image}`"
                                    :alt="event.title"
                                    class="h-[400px] w-full object-cover transition-transform duration-300 hover:scale-105"
                                />
                            </div>
                            <div
                                v-else
                                class="flex h-[400px] items-center justify-center rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-900"
                            >
                                <Calendar class="h-24 w-24 text-slate-400" />
                            </div>

                            <!-- Status Badge -->
                            <div class="absolute top-6 left-6">
                                <span
                                    :class="[
                                        'inline-flex items-center rounded-full px-4 py-2 text-sm font-semibold backdrop-blur-sm',
                                        statusClass(getEventStatus(event.date_event)),
                                    ]"
                                >
                                    <span class="mr-2 h-2 w-2 rounded-full bg-current"></span>
                                    {{ getEventStatus(event.date_event) }}
                                </span>
                            </div>
                        </div>

                        <!-- Event Title & Description -->
                        <div class="space-y-6">
                            <div>
                                <h1 class="mb-4 text-4xl font-bold text-slate-900 lg:text-5xl dark:text-slate-100">
                                    {{ event.title }}
                                </h1>

                                <div class="flex items-center gap-4 text-slate-600 dark:text-slate-400">
                                    <div class="flex items-center gap-2">
                                        <Building2 class="h-4 w-4" />
                                        <span class="font-medium">{{ event.organizer }}</span>
                                    </div>
                                    <span class="text-slate-400">•</span>
                                    <div class="flex items-center gap-2">
                                        <Clock class="h-4 w-4" />
                                        <span class="text-sm">{{ formatTime(event.created_at) }}</span>
                                    </div>
                                </div>
                            </div>

                            <Separator />

                            <!-- Event Description -->
                            <div class="prose prose-slate dark:prose-invert max-w-none">
                                <h3 class="text-xl font-semibold text-slate-900 dark:text-slate-100">Tentang Event</h3>
                                <p class="leading-relaxed text-slate-700 dark:text-slate-300">
                                    {{ event.content }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Event Details Sidebar -->
                    <div class="lg:col-span-1">
                        <div class="sticky top-8">
                            <Card class="overflow-hidden border-0 bg-white/70 shadow-xl backdrop-blur-sm dark:bg-slate-900/70">
                                <CardContent class="p-8">
                                    <!-- Price -->
                                    <div class="mb-8 text-center">
                                        <div class="text-3xl font-bold text-slate-900 dark:text-slate-100">
                                            {{ formatPrice(event.price) }}
                                        </div>
                                        <p class="text-sm text-slate-600 dark:text-slate-400">per tiket</p>
                                    </div>

                                    <Separator class="mb-8" />

                                    <!-- Event Details -->
                                    <div class="space-y-6">
                                        <div
                                            class="group flex items-start gap-4 rounded-xl p-4 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800"
                                        >
                                            <div class="rounded-lg bg-slate-100 p-2 dark:bg-slate-800">
                                                <Calendar class="h-5 w-5 text-slate-600 dark:text-slate-400" />
                                            </div>
                                            <div>
                                                <h4 class="font-semibold text-slate-900 dark:text-slate-100">Tanggal</h4>
                                                <p class="text-slate-600 dark:text-slate-400">
                                                    {{
                                                        new Date(event.date_event).toLocaleDateString('id-ID', {
                                                            weekday: 'long',
                                                            year: 'numeric',
                                                            month: 'long',
                                                            day: 'numeric',
                                                        })
                                                    }}
                                                </p>
                                            </div>
                                        </div>

                                        <div
                                            class="group flex items-start gap-4 rounded-xl p-4 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800"
                                        >
                                            <div class="rounded-lg bg-slate-100 p-2 dark:bg-slate-800">
                                                <MapPin class="h-5 w-5 text-slate-600 dark:text-slate-400" />
                                            </div>
                                            <div>
                                                <h4 class="font-semibold text-slate-900 dark:text-slate-100">Lokasi</h4>
                                                <p class="text-slate-600 dark:text-slate-400">{{ event.location }}</p>
                                            </div>
                                        </div>

                                        <div
                                            class="group flex items-start gap-4 rounded-xl p-4 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800"
                                        >
                                            <div class="rounded-lg bg-slate-100 p-2 dark:bg-slate-800">
                                                <User class="h-5 w-5 text-slate-600 dark:text-slate-400" />
                                            </div>
                                            <div>
                                                <h4 class="font-semibold text-slate-900 dark:text-slate-100">Pembuat</h4>
                                                <div class="flex flex-wrap gap-2">
                                                    <p class="text-slate-600 dark:text-slate-400">{{ event.user?.clean_name }}</p>
                                                    <div
                                                        v-if="event.user?.role === 'moderator'"
                                                        class="flex h-fit items-center rounded bg-blue-600 p-1 font-bold text-white"
                                                    >
                                                        Moderator
                                                    </div>
                                                    <div
                                                        v-if="event.user?.role === 'community'"
                                                        class="flex h-fit items-center rounded bg-yellow-500 p-1 font-bold text-white shadow"
                                                    >
                                                        Komunitas
                                                    </div>
                                                </div>
                                                <Button
                                                    v-if="usePage().props.auth.user.role == 'admin' || usePage().props.auth.user.id == event.user.id"
                                                    @click="deleteEvent(event.id)"
                                                    class="w-full bg-red-500"
                                                    >Delete</Button
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
