<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Toaster } from '@/components/ui/sonner';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { AlertCircle, Calendar, CheckCircle2, Clock, CreditCard, DollarSign, FileText, XCircle } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { toast } from 'vue-sonner';
import 'vue-sonner/style.css';

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
    event_id: number | null;
    event: {
        id: number;
        title: string;
        date_event: string;
        location: string;
        is_visible: boolean;
    } | null;
}

interface PaginatedTransactions {
    data: Transaction[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Props {
    transactions: PaginatedTransactions;
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
                onError: function () {},
            });
        }
    } catch (error: any) {
        if (error.response?.data?.message) {
            toast.error(error.response.data.message);
        } else {
            toast.error('Gagal memuat pembayaran. Silakan coba lagi.');
        }
        console.error('Continue payment error:', error);
    }
};

//Infinite scroll state
const transactions = ref<Transaction[]>(props.transactions?.data || []);
const currentPage = ref(props.transactions?.current_page || 1);
const lastPage = ref(props.transactions?.last_page || 1);
const isLoading = ref(false);
const hasMore = computed(() => currentPage.value < lastPage.value);

const loadMore = async () => {
    if (isLoading.value || !hasMore.value) return;

    isLoading.value = true;
    try {
        const response = await axios.get(route('user.transaction.paginate'), {
            params: { page: currentPage.value + 1 },
        });

        transactions.value.push(...response.data.data);
        currentPage.value = response.data.current_page;
        lastPage.value = response.data.last_page;
    } catch (error) {
        console.error('Failed to load more transactions:', error);
    } finally {
        isLoading.value = false;
    }
};

const handleScroll = () => {
    const scrollPosition = window.innerHeight + window.scrollY;
    const bottomPosition = document.documentElement.scrollHeight - 200;

    if (scrollPosition >= bottomPosition && hasMore.value && !isLoading.value) {
        loadMore();
    }
};

// Load Midtrans Snap script
onMounted(() => {
    const script = document.createElement('script');
    script.src = 'https://app.sandbox.midtrans.com/snap/snap.js';
    script.setAttribute('data-client-key', usePage().props.midtrans_client_key as string);
    document.head.appendChild(script);

    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});

// Delete transaction
const deleteTransaction = async (orderId: string) => {
    try {
        const response = await axios.post(route('user.transaction.cancel', orderId));

        // Remove transaction from the list
        transactions.value = transactions.value.filter((t) => t.order_id !== orderId);

        toast.success(response.data.message || 'Transaksi dan event berhasil dibatalkan', {
            duration: 3000,
        });
    } catch (error: any) {
        const errorMessage = error.response?.data?.error || 'Gagal membatalkan transaksi';
        toast.error(errorMessage, {
            duration: 5000,
        });
    }
};
</script>

<template>
    <Head title="Transaksi" />
    <Toaster />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold dark:text-white">Riwayat Transaksi</h1>
            </div>

            <div v-if="transactions.length === 0" class="py-12 text-center">
                <CreditCard class="mx-auto mb-4 h-16 w-16 text-gray-400" />
                <p class="text-lg text-gray-600 dark:text-gray-400">Belum ada transaksi</p>
            </div>

            <div v-else class="grid gap-4">
                <Card v-for="transaction in transactions" :key="transaction.id" class="transition-shadow hover:shadow-lg">
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
                            <div v-if="transaction.transaction_status === 'pending' && transaction.snap_token" class="w-fit space-y-2 border-t pt-3">
                                <Button @click="continuePayment(transaction)" class="w-full" variant="default">
                                    <CreditCard class="mr-2 h-4 w-4" />
                                    Lanjutkan Pembayaran
                                </Button>
                                <AlertDialog>
                                    <AlertDialogTrigger class="w-full">
                                        <Button @click="" class="w-full" variant="destructive">
                                            <XCircle class="mr-2 h-4 w-4" />
                                            Batalkan Transaksi
                                        </Button></AlertDialogTrigger
                                    >
                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle>Apakah anda yakin ingin membatalkan transaksi ini?</AlertDialogTitle>
                                            <AlertDialogDescription>
                                                <template v-if="transaction.transaction_type === 'event_creation'">
                                                    Transaksi ini tidak dapat diulangi.
                                                    <strong>Transaksi dan Event yang dibuat akan dihapus</strong> dari akun anda.
                                                </template>
                                                <template v-else-if="transaction.transaction_type === 'event_promotion'">
                                                    Transaksi promosi ini tidak dapat diulangi. <strong>Hanya transaksi yang akan dihapus</strong>,
                                                    event tetap ada.
                                                </template>
                                                <template v-else> Transaksi ini tidak dapat diulangi dan akan dihapus dari akun anda. </template>
                                            </AlertDialogDescription>
                                        </AlertDialogHeader>
                                        <AlertDialogFooter>
                                            <AlertDialogCancel>Batal</AlertDialogCancel>
                                            <AlertDialogAction @click="deleteTransaction(transaction.order_id)">Ya, Batalkan</AlertDialogAction>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Loading indicator -->
            <div v-if="isLoading" class="py-8 text-center">
                <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-blue-600 border-r-transparent"></div>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Memuat transaksi...</p>
            </div>

            <!-- End of list indicator -->
            <div v-if="!hasMore && transactions.length > 0" class="py-8 text-center text-gray-500 dark:text-gray-400">
                <p>Semua transaksi telah ditampilkan</p>
            </div>
        </div>
    </AppLayout>
</template>
