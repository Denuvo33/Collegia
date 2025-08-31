<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Calendar, MapPin, User, Wallet } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Event',
        href: '/user/event',
    },
];

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

        user: {
            name: string;
        };
    }>;
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
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4">
            <div>
                <Button @click="$inertia.visit(route('user.create-event'))"> Buat Event </Button>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="event in data"
                    :key="event.id"
                    class="flex flex-col overflow-hidden rounded-2xl border bg-white shadow-sm transition hover:shadow-lg dark:bg-black"
                >
                    <div v-if="event.image">
                        <img :src="`/storage/${event.image}`" alt="Event" class="mt-2 h-58 w-full object-cover shadow" />
                    </div>

                    <div class="flex flex-1 flex-col justify-between p-4">
                        <div>
                            <h2 class="mb-2 text-lg font-semibold">{{ event.title }}</h2>
                            <p class="line-clamp-3 text-gray-400">
                                {{ event.content }}
                            </p>
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
                                          : 'bg-red-500'
                                "
                            >
                                <h1>Status: {{ getEventStatus(event.date_event) }}</h1>
                            </div>
                            <div>
                                <Button>Lihat Selengkapnya</Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
