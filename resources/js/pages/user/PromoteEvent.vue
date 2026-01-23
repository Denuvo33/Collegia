<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Toaster } from '@/components/ui/sonner';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { ArrowLeft, Calendar, Clock, Gem, MapPin, Sparkles } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

interface Event {
    id: number;
    title: string;
    content: string;
    image: string | null;
    date_event: string;
    location: string;
    organizer: string;
    price: number;
}

const props = defineProps<{
    event: Event;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Event', href: '/user/event' },
    { title: 'Promosikan Event', href: '#' },
];

const startDate = ref(new Date().toISOString().split('T')[0]);
const endDate = ref(new Date(Date.now() + 86400000).toISOString().split('T')[0]);

const promotionCost = computed(() => {
    const start = new Date(startDate.value);
    const end = new Date(endDate.value);
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

const initiatePromotion = async () => {
    try {
        if (new Date(startDate.value) >= new Date(endDate.value)) {
            toast.error('Tanggal akhir harus setelah tanggal mulai');
            return;
        }

        const response = await axios.post(route('user.event.promotion.initiate'), {
            event_id: props.event.id,
            start_date: startDate.value,
            end_date: endDate.value,
        });

        const snapToken = response.data.snap_token;
        const orderId = response.data.order_id;

        if (snapToken && (window as any).snap) {
            let paymentMethodSelected = false;

            (window as any).snap.pay(snapToken, {
                onSuccess: function () {
                    paymentMethodSelected = true;
                    toast.success('Pembayaran promosi berhasil! Event Anda akan dipromosikan.');
                    setTimeout(() => {
                        router.visit(route('user.event'));
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
        } else {
            toast.error('Gagal memuat sistem pembayaran. Silakan refresh halaman dan coba lagi.');
        }
    } catch (error: any) {
        console.error('Failed to initiate promotion payment', error);
        toast.error(error.response?.data?.message || error.response?.data?.error || 'Gagal memulai pembayaran promosi');
    }
};

onMounted(() => {
    const script = document.createElement('script');
    script.src = 'https://app.sandbox.midtrans.com/snap/snap.js';
    script.setAttribute('data-client-key', usePage().props.midtrans_client_key as string);
    document.head.appendChild(script);
});
</script>

<template>
    <Head title="Promosikan Event" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster />

        <div class="container mx-auto max-w-6xl p-6">
            <!-- Back Button -->
            <Button
                variant="ghost"
                @click="router.visit(route('user.event'))"
                class="mb-6 text-gray-600 hover:text-black dark:text-gray-400 dark:hover:text-white"
            >
                <ArrowLeft class="mr-2 h-4 w-4" />
                Kembali ke Event
            </Button>

            <!-- Header -->
            <div class="mb-8 text-center">
                <div class="mb-4 flex justify-center">
                    <div class="rounded-full bg-gradient-to-br from-gray-800 to-black p-4 shadow-2xl dark:from-gray-200 dark:to-white">
                        <Sparkles class="h-8 w-8 text-white dark:text-black" />
                    </div>
                </div>
                <h1 class="mb-2 text-4xl font-bold text-black dark:text-white">Promosikan Event Anda</h1>
                <p class="text-lg text-gray-600 dark:text-gray-400">Tingkatkan visibilitas dan jangkauan event Anda</p>
            </div>

            <div class="grid gap-8 lg:grid-cols-2">
                <!-- Event Details Card -->
                <Card class="border-2 border-gray-200 shadow-xl dark:border-gray-700">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-2xl">
                            <Calendar class="h-6 w-6" />
                            Detail Event
                        </CardTitle>
                        <CardDescription>Event yang akan dipromosikan</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Event Image -->
                        <div v-if="event.image" class="overflow-hidden rounded-lg">
                            <img :src="event.image_url" :alt="event.title" class="h-48 w-full object-cover" />
                        </div>

                        <!-- Event Title -->
                        <div>
                            <h3 class="text-xl font-bold text-black dark:text-white">{{ event.title }}</h3>
                            <p class="mt-2 text-gray-700 dark:text-gray-300">{{ event.content }}</p>
                        </div>

                        <Separator />

                        <!-- Event Meta -->
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                                <Calendar class="h-4 w-4 text-gray-500" />
                                <span>{{
                                    new Date(event.date_event).toLocaleDateString('id-ID', {
                                        weekday: 'long',
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                    })
                                }}</span>
                            </div>
                            <div class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                                <MapPin class="h-4 w-4 text-gray-500" />
                                <span>{{ event.location }}</span>
                            </div>
                            <div class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                                <Clock class="h-4 w-4 text-gray-500" />
                                <span>{{ event.organizer }}</span>
                            </div>
                        </div>

                        <!-- Price Badge -->
                        <div class="rounded-lg bg-gray-100 p-4 dark:bg-gray-800">
                            <div class="text-center">
                                <p class="text-sm text-gray-600 dark:text-gray-400">Harga Tiket</p>
                                <p class="text-2xl font-bold text-black dark:text-white">
                                    {{ event.price === 0 ? 'GRATIS' : formatCurrency(event.price) }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Promotion Form Card -->
                <Card class="border-2 border-gray-200 shadow-xl dark:border-gray-700">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-2xl">
                            <Gem class="h-6 w-6" />
                            Paket Promosi
                        </CardTitle>
                        <CardDescription>Pilih periode promosi untuk event Anda</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- Info Box -->
                        <div class="rounded-lg border-2 border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
                            <h4 class="mb-2 font-semibold text-black dark:text-white">Keuntungan Promosi</h4>
                            <ul class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                                <li>✓ Tampil di bagian teratas daftar event</li>
                                <li>✓ Badge khusus "Dipromosikan"</li>
                                <li>✓ Prioritas dalam pencarian</li>
                                <li>✓ Jangkauan lebih luas</li>
                            </ul>
                        </div>

                        <!-- Date Selection -->
                        <div class="space-y-4">
                            <div class="space-y-2">
                                <Label for="start-date" class="text-base font-semibold">Tanggal Mulai Promosi</Label>
                                <Input
                                    id="start-date"
                                    type="date"
                                    v-model="startDate"
                                    :min="new Date().toISOString().split('T')[0]"
                                    class="border-2"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label for="end-date" class="text-base font-semibold">Tanggal Selesai Promosi</Label>
                                <Input id="end-date" type="date" v-model="endDate" :min="startDate" class="border-2" />
                            </div>
                        </div>

                        <Separator />

                        <!-- Cost Calculation -->
                        <div
                            class="space-y-3 rounded-lg bg-gradient-to-br from-gray-900 to-black p-6 text-white dark:from-gray-100 dark:to-white dark:text-black"
                        >
                            <div class="flex justify-between text-sm">
                                <span>Durasi Promosi</span>
                                <span class="font-bold">{{ promotionCost.days }} Hari</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span>Biaya per Hari</span>
                                <span class="font-bold">Rp 5.000</span>
                            </div>
                            <Separator class="bg-white/20 dark:bg-black/20" />
                            <div class="flex justify-between text-lg">
                                <span class="font-bold">Total Biaya</span>
                                <span class="font-bold">{{ formatCurrency(promotionCost.total) }}</span>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <Button
                            @click="initiatePromotion"
                            class="hover:shadow-3xl w-full transform bg-gradient-to-r from-gray-900 to-black py-6 text-lg font-bold text-white shadow-2xl transition-all hover:scale-105 dark:from-gray-100 dark:to-white dark:text-black"
                        >
                            <Gem class="mr-2 h-5 w-5" />
                            Promosikan Sekarang
                        </Button>

                        <p class="text-center text-xs text-gray-500 dark:text-gray-400">
                            Dengan melanjutkan, transaksi akan dibuat dan pembayaran dapat dilanjutkan di halaman Transaksi
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
