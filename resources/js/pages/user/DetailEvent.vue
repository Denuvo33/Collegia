<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import relativeTime from 'dayjs/plugin/relativeTime';
import { Calendar, MapPin, User, Wallet } from 'lucide-vue-next';

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
        user?: {
            id: number;
            name: string;
        };
    };
}>();

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
        <div class="mx-auto max-w-4xl p-6">
            <div class="mb-6 space-y-2">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                    {{ event.title }}
                </h1>
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
                <Button @click="$inertia.visit(route('user.home'))"> Kembali </Button>
            </div>
        </div>
    </AppLayout>
</template>
