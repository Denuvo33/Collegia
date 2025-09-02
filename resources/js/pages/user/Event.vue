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
import { router, usePage } from '@inertiajs/vue3';
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
        title: 'Event',
        href: '/user/event',
    },
];

const reportValue = ref('spam');

defineProps<{
    data: Array<{
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
    }>;
}>();

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
    if (confirm('Apakah Anda yakin ingin menghapus event ini?')) {
        router.delete(route('user.delete.event', { id: id, userId: usePage().props.auth.user.id }));
    }
}

function reportEvent(id: number) {
    router.post(
        route('user.event.report'),
        {
            post_id: id,
            reason: reportValue.value,
        },
        {
            preserveScroll: true,
        },
    );
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster />

        <!-- Nuxt, vue-sonner v1 because inserting inline CSS with JS to the head tag -->
        <ClientOnly>
            <Toaster />
        </ClientOnly>

        <!-- Nuxt, vue-sonner v2 no need to ClientOnly -->
        <Toaster />

        <div class="space-y-6 p-4">
            <div>
                <Button @click="$inertia.visit(route('user.create-event'))"> Buat Event </Button>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="event in sortList(data)"
                    :key="event.id"
                    class="flex flex-col overflow-hidden rounded-2xl border bg-white shadow-sm transition hover:shadow-lg dark:bg-black"
                >
                    <div class="flex flex-1 flex-col justify-between p-4">
                        <div>
                            <div class="flex flex-row justify-between">
                                <h2 class="mb-2 text-lg font-semibold">{{ event.title }}</h2>
                                <DropdownMenu>
                                    <DropdownMenuTrigger><EllipsisVertical></EllipsisVertical></DropdownMenuTrigger>
                                    <DropdownMenuContent>
                                        <DropdownMenuItem v-if="event.user.id === usePage().props.auth.user.id" @click="deleteEvent(event.id)"
                                            >Hapus</DropdownMenuItem
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
                            <p class="line-clamp-3 text-gray-400">
                                {{ event.content }}
                            </p>
                        </div>
                        <div v-if="event.image">
                            <img :src="`/storage/${event.image}`" alt="Event" class="mt-2 h-58 w-full object-cover shadow" />
                        </div>

                        <div class="mt-4 space-y-2 text-sm text-gray-600">
                            <div class="flex items-center gap-2">
                                <User class="h-4 w-4" />
                                <span>{{ event.organizer }}</span>
                            </div>

                            <div class="flex items-center gap-2">
                                <Calendar class="h-4 w-4" />
                                <span>{{ new Date(event.date_event).toLocaleDateString() }}</span>
                            </div>

                            <div class="flex items-center gap-2">
                                <MapPin class="h-4 w-4" />
                                <span>{{ event.location }}</span>
                            </div>

                            <div class="flex items-center gap-2">
                                <Wallet class="h-4 w-4" />
                                <span>{{ event.price == 0 ? 'Gratis' : 'Rp ' + event.price }}</span>
                            </div>

                            <div
                                class="w-fit rounded p-1 text-white"
                                :class="
                                    getEventStatus(event.date_event) == 'Akan Datang'
                                        ? 'bg-blue-500'
                                        : getEventStatus(event.date_event) == 'Sedang Berjalan'
                                          ? 'bg-green-500'
                                          : 'bg-gray-500'
                                "
                            >
                                <h1>Status: {{ getEventStatus(event.date_event) }}</h1>
                            </div>
                            <div>Dibuat {{ formatTime(event.created_at) }}</div>
                            <div>
                                <Button
                                    @click="
                                        $inertia.visit(
                                            route('user.details.event', {
                                                hashId: event.hash_id,
                                                title: event.title.toLowerCase().split(' ').join('-'),
                                            }),
                                        )
                                    "
                                    >Lihat Selengkapnya</Button
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
