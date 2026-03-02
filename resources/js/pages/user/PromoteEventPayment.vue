<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Toaster } from '@/components/ui/sonner';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { ArrowLeft, CheckCircle2, CreditCard, Gem, QrCode, Smartphone, Sparkles } from 'lucide-vue-next';
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

interface Promotion {
    start_date: string;
    end_date: string;
    days: number;
    cost_per_day: number;
    total_cost: number;
}

interface PaymentMethod {
    id: string;
    name: string;
    description: string;
    icon: string;
}

const props = defineProps<{
    event: Event;
    promotion: Promotion;
    payment_methods: PaymentMethod[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Event', href: '/user/event' },
    { title: 'Promosikan Event', href: '#' },
    { title: 'Pilih Pembayaran', href: '#' },
];

const selectedPaymentMethod = ref<string | null>(null);
const isProcessing = ref(false);

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(amount);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const getPaymentIcon = (iconName: string) => {
    switch (iconName) {
        case 'qris':
            return QrCode;
        case 'gopay':
        case 'shopeepay':
            return Smartphone;
        default:
            return CreditCard;
    }
};

const canProceed = computed(() => selectedPaymentMethod.value !== null && !isProcessing.value);

const selectPaymentMethod = (methodId: string) => {
    selectedPaymentMethod.value = methodId;
};

const processPayment = async () => {
    if (!selectedPaymentMethod.value) {
        toast.error('Pilih metode pembayaran terlebih dahulu');
        return;
    }

    isProcessing.value = true;

    try {
        const response = await axios.post(route('user.event.promotion.initiate'), {
            event_id: props.event.id,
            start_date: props.promotion.start_date,
            end_date: props.promotion.end_date,
            payment_method: selectedPaymentMethod.value,
        });

        const snapToken = response.data.snap_token;
        const orderId = response.data.order_id;

        if (snapToken && (window as any).snap) {
            (window as any).snap.pay(snapToken, {
                onSuccess: function () {
                    toast.success('Pembayaran promosi berhasil! Event Anda akan dipromosikan.');
                    setTimeout(() => {
                        router.visit(route('user.event'));
                    }, 1500);
                },
                onPending: function () {
                    toast.info('Pembayaran sedang diproses.');
                    setTimeout(() => {
                        router.visit(route('user.transaction'));
                    }, 1500);
                },
                onError: function () {
                    toast.error('Terjadi kesalahan. Silakan cek transaksi Anda.');
                    setTimeout(() => {
                        router.visit(route('user.transaction'));
                    }, 1500);
                },
                onClose: function () {
                    axios
                        .post(route('user.transaction.cancel', orderId))
                        .then(() => {
                            toast.info('Pembayaran dibatalkan.');
                        })
                        .catch((error) => {
                            console.error('Failed to cancel transaction:', error);
                        });
                    isProcessing.value = false;
                },
            });
        } else {
            toast.error('Gagal memuat sistem pembayaran. Silakan refresh halaman dan coba lagi.');
            isProcessing.value = false;
        }
    } catch (error: any) {
        console.error('Failed to initiate promotion payment', error);
        toast.error(error.response?.data?.message || error.response?.data?.error || 'Gagal memulai pembayaran promosi');
        isProcessing.value = false;
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
    <Head title="Pilih Pembayaran" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster />

        <div class="container mx-auto max-w-4xl p-6">
            <!-- Back Button -->
            <Button
                variant="ghost"
                @click="router.visit(route('user.event.promote.form', event.id))"
                class="mb-6 text-gray-600 hover:text-black dark:text-gray-400 dark:hover:text-white"
            >
                <ArrowLeft class="mr-2 h-4 w-4" />
                Kembali
            </Button>

            <!-- Header -->
            <div class="mb-8 text-center">
                <div class="mb-4 flex justify-center">
                    <div class="rounded-full bg-gradient-to-br from-gray-800 to-black p-4 shadow-2xl dark:from-gray-200 dark:to-white">
                        <CreditCard class="h-8 w-8 text-white dark:text-black" />
                    </div>
                </div>
                <h1 class="mb-2 text-4xl font-bold text-black dark:text-white">Pilih Metode Pembayaran</h1>
                <p class="text-lg text-gray-600 dark:text-gray-400">Pilih metode pembayaran untuk promosi event Anda</p>
            </div>

            <div class="grid gap-8 lg:grid-cols-5">
                <!-- Payment Methods (Left - Wider) -->
                <div class="space-y-4 lg:col-span-3">
                    <h2 class="mb-4 text-xl font-bold text-black dark:text-white">Metode Pembayaran</h2>

                    <div
                        v-for="method in payment_methods"
                        :key="method.id"
                        @click="selectPaymentMethod(method.id)"
                        class="cursor-pointer rounded-xl border-2 p-5 transition-all duration-200"
                        :class="
                            selectedPaymentMethod === method.id
                                ? 'border-black bg-gray-50 shadow-lg dark:border-white dark:bg-gray-800'
                                : 'border-gray-200 hover:border-gray-400 hover:shadow-md dark:border-gray-700 dark:hover:border-gray-500'
                        "
                    >
                        <div class="flex items-center gap-4">
                            <!-- Radio indicator -->
                            <div
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 transition-all"
                                :class="
                                    selectedPaymentMethod === method.id
                                        ? 'border-black bg-black dark:border-white dark:bg-white'
                                        : 'border-gray-300 dark:border-gray-600'
                                "
                            >
                                <CheckCircle2 v-if="selectedPaymentMethod === method.id" class="h-4 w-4 text-white dark:text-black" />
                            </div>

                            <!-- Icon -->
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-lg"
                                :class="
                                    selectedPaymentMethod === method.id
                                        ? 'bg-black text-white dark:bg-white dark:text-black'
                                        : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
                                "
                            >
                                <component :is="getPaymentIcon(method.icon)" class="h-6 w-6" />
                            </div>

                            <!-- Info -->
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-black dark:text-white">{{ method.name }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ method.description }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary (Right - Narrower) -->
                <div class="lg:col-span-2">
                    <Card class="sticky top-6 border-2 border-gray-200 shadow-xl dark:border-gray-700">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-lg">
                                <Sparkles class="h-5 w-5" />
                                Ringkasan Pesanan
                            </CardTitle>
                            <CardDescription>{{ event.title }}</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Promotion Details -->
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Mulai Promosi</span>
                                    <span class="font-medium">{{ formatDate(promotion.start_date) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Selesai Promosi</span>
                                    <span class="font-medium">{{ formatDate(promotion.end_date) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Durasi</span>
                                    <span class="font-bold">{{ promotion.days }} Hari</span>
                                </div>
                            </div>

                            <Separator />

                            <!-- Cost Breakdown -->
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Biaya per Hari</span>
                                    <span>{{ formatCurrency(promotion.cost_per_day) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400"
                                        >{{ promotion.days }} Hari × {{ formatCurrency(promotion.cost_per_day) }}</span
                                    >
                                    <span>{{ formatCurrency(promotion.total_cost) }}</span>
                                </div>
                            </div>

                            <Separator />

                            <!-- Total -->
                            <div
                                class="rounded-lg bg-gradient-to-br from-gray-900 to-black p-4 text-white dark:from-gray-100 dark:to-white dark:text-black"
                            >
                                <div class="flex justify-between text-lg">
                                    <span class="font-bold">Total</span>
                                    <span class="font-bold">{{ formatCurrency(promotion.total_cost) }}</span>
                                </div>
                            </div>

                            <!-- Pay Button -->
                            <Button
                                @click="processPayment"
                                :disabled="!canProceed"
                                class="w-full py-6 text-lg font-bold transition-all"
                                :class="
                                    canProceed
                                        ? 'hover:shadow-3xl bg-gradient-to-r from-gray-900 to-black text-white shadow-2xl hover:scale-105 dark:from-gray-100 dark:to-white dark:text-black'
                                        : 'cursor-not-allowed opacity-50'
                                "
                            >
                                <Gem class="mr-2 h-5 w-5" />
                                {{ isProcessing ? 'Memproses...' : 'Bayar Sekarang' }}
                            </Button>

                            <p v-if="!selectedPaymentMethod" class="text-center text-xs text-amber-600 dark:text-amber-400">
                                Pilih metode pembayaran untuk melanjutkan
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
