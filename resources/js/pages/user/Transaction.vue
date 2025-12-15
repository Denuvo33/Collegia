<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { AlertCircle, Calendar, CheckCircle2, Clock, CreditCard, DollarSign, FileText, XCircle } from 'lucide-vue-next';
import { onMounted } from 'vue';

interface Transaction {
    id: number;
    order_id: string;
    transaction_type: string;
    amount: number;
    transaction_status: string;
    payment_type: string | null;
    snap_token: string | null;
    created_at: string;
    paid_at: string | null;
    promotion_days: number | null;
    promotion_start_date: string | null;
    promotion_end_date: string | null;
    event: {
        id: number;
        title: string;
        date_event: string;
        location: string;
        is_visible: boolean;
    } | null;
}

interface Props {
    transactions: Transaction[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Transaksi',
        href: '/user/transaction',
    },
];

const getStatusBadge = (status: string) => {
    const variants: Record<string, { variant: 'default' | 'secondary' | 'destructive' | 'outline'; text: string; icon: any }> = {
        pending: { variant: 'secondary', text: 'Menunggu Pembayaran', icon: Clock },
        success: { variant: 'default', text: 'Berhasil', icon: CheckCircle2 },
        failed: { variant: 'destructive', text: 'Gagal', icon: XCircle },
        expired: { variant: 'destructive', text: 'Kadaluarsa', icon: AlertCircle },
        cancelled: { variant: 'destructive', text: 'Dibatalkan', icon: XCircle },
    };
    return variants[status] || { variant: 'secondary', text: status, icon: AlertCircle };
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(amount);
};

const formatDate = (dateString: string) => {
    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'long',
        timeStyle: 'short',
    }).format(new Date(dateString));
};

const continuePayment = async (transaction: Transaction) => {
    try {
        // Check if token might be expired (simple client-side check)
        const transactionAge = (Date.now() - new Date(transaction.created_at).getTime()) / 1000 / 60; // minutes

        let snapToken = transaction.snap_token;

        // If older than 1 minute, refresh the token
        if (transactionAge >= 1) {
            const response = await axios.post(route('user.transaction.refresh', transaction.order_id));
            snapToken = response.data.snap_token;

            if (response.data.message) {
                console.log(response.data.message);
            }
        }

        if ((window as any).snap && snapToken) {
            (window as any).snap.pay(snapToken, {
                onSuccess: function () {
                    router.reload();
                },
                onPending: function () {
                    router.reload();
                },
                onError: function () {
                    alert('Pembayaran gagal. Silakan coba lagi.');
                },
            });
        }
    } catch (error: any) {
        if (error.response?.data?.message) {
            alert(error.response.data.message);
        } else {
            alert('Gagal memuat pembayaran. Silakan coba lagi.');
        }
        console.error('Continue payment error:', error);
    }
};

// Load Midtrans Snap script
onMounted(() => {
    const script = document.createElement('script');
    script.src = 'https://app.sandbox.midtrans.com/snap/snap.js';
    script.setAttribute('data-client-key', usePage().props.midtrans_client_key as string);
    document.head.appendChild(script);
});
</script>

<template>
    <Head title="Transaksi" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold dark:text-white">Riwayat Transaksi</h1>
                <p class="text-gray-600 dark:text-gray-400">Kelola pembayaran event Anda</p>
            </div>

            <div v-if="props.transactions.length === 0" class="py-12 text-center">
                <CreditCard class="mx-auto mb-4 h-16 w-16 text-gray-400" />
                <p class="text-lg text-gray-600 dark:text-gray-400">Belum ada transaksi</p>
            </div>

            <div v-else class="grid gap-4">
                <Card v-for="transaction in props.transactions" :key="transaction.id" class="transition-shadow hover:shadow-lg">
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <CardTitle class="mb-2 text-lg">
                                    {{ transaction.event?.title || 'Event Deleted' }}
                                </CardTitle>
                                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                    <CreditCard class="h-4 w-4" />
                                    <span class="font-mono">{{ transaction.order_id }}</span>
                                </div>
                            </div>
                            <Badge :variant="getStatusBadge(transaction.transaction_status).variant">
                                <component :is="getStatusBadge(transaction.transaction_status).icon" class="mr-1 h-3 w-3" />
                                {{ getStatusBadge(transaction.transaction_status).text }}
                            </Badge>
                        </div>
                    </CardHeader>

                    <CardContent>
                        <div class="grid gap-3">
                            <!-- Amount -->
                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                    <DollarSign class="h-4 w-4" />
                                    Total Pembayaran
                                </span>
                                <span class="text-lg font-bold">{{ formatCurrency(transaction.amount) }}</span>
                            </div>

                            <!-- Event Details (if exists) -->
                            <div v-if="transaction.event" class="space-y-2 border-t pt-3">
                                <!-- Transaction Type Badge -->
                                <div class="flex items-center justify-between text-sm">
                                    <span class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                        <FileText class="h-4 w-4" />
                                        Jenis Transaksi
                                    </span>
                                    <Badge :variant="transaction.transaction_type === 'event_promotion' ? 'default' : 'secondary'">
                                        {{ transaction.transaction_type === 'event_promotion' ? 'Promosi Event' : 'Pembuatan Event' }}
                                    </Badge>
                                </div>

                                <!-- Promotion Details (if promotion) -->
                                <div
                                    v-if="transaction.transaction_type === 'event_promotion'"
                                    class="space-y-2 rounded bg-green-50 p-3 dark:bg-green-900/20"
                                >
                                    <div class="mb-1 font-semibold text-green-800 dark:text-green-200">Detail Promosi</div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Durasi</span>
                                        <span>{{ transaction.promotion_days }} hari</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Mulai</span>
                                        <span>{{ formatDate(transaction.promotion_start_date!) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Selesai</span>
                                        <span>{{ formatDate(transaction.promotion_end_date!) }}</span>
                                    </div>
                                </div>

                                <!-- Event Info -->
                                <div class="flex items-center justify-between text-sm">
                                    <span class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                        <Calendar class="h-4 w-4" />
                                        Tanggal Event
                                    </span>
                                    <span>{{ formatDate(transaction.event.date_event) }}</span>
                                </div>
                                <div v-if="transaction.transaction_type === 'event_creation'" class="flex items-center justify-between text-sm">
                                    <span class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                        <FileText class="h-4 w-4" />
                                        Status Event
                                    </span>
                                    <Badge :variant="transaction.event.is_visible ? 'default' : 'secondary'">
                                        {{ transaction.event.is_visible ? 'Terlihat' : 'Belum Terlihat' }}
                                    </Badge>
                                </div>
                            </div>

                            <!-- Transaction Details -->
                            <div class="space-y-2 border-t pt-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Dibuat</span>
                                    <span>{{ formatDate(transaction.created_at) }}</span>
                                </div>
                                <div v-if="transaction.paid_at" class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Dibayar</span>
                                    <span>{{ formatDate(transaction.paid_at) }}</span>
                                </div>
                                <div v-if="transaction.payment_type" class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Metode Pembayaran</span>
                                    <span class="uppercase">{{ transaction.payment_type.replace('_', ' ') }}</span>
                                </div>
                            </div>

                            <!-- Action Button -->
                            <div
                                v-if="transaction.transaction_status === 'pending' && transaction.snap_token && transaction.event_id !== null"
                                class="w-fit border-t pt-3"
                            >
                                <Button @click="continuePayment(transaction)" class="w-full" variant="default">
                                    <CreditCard class="mr-2 h-4 w-4" />
                                    Lanjutkan Pembayaran
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
