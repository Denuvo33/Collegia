<script setup lang="ts">
import { Toaster } from '@/components/ui/sonner';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, ExternalLink, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import 'vue-sonner/style.css';

type ReportEvent = {
    id: number;
    event_id: number;
    reason: string;
    created_at: string;
};

type Event = {
    title: string;
    hash_id: string;
};

const props = defineProps<{
    events: {
        data: (ReportEvent & { event: Event })[];
        last_page: number;
        current_page: number;
        next_page_url: string | null;
    };
}>();

const reportEvents = ref<(ReportEvent & { event: Event })[]>([...props.events.data]);

// Pagination logic
const currentPage = ref(props.events.current_page);
const lastPage = props.events.last_page;

const paginationNumbers = computed(() => {
    const delta = 3;
    const range = [];
    const rangeWithDots: (string | number)[] = [];
    let l: number;

    for (let i = 1; i <= lastPage; i++) {
        if (i === 1 || i === lastPage || (i >= currentPage.value - delta && i <= currentPage.value + delta)) {
            range.push(i);
        }
    }

    range.forEach((i) => {
        if (l) {
            if (i - l === 2) {
                rangeWithDots.push(l + 1);
            } else if (i - l !== 1) {
                rangeWithDots.push('...');
            }
        }
        rangeWithDots.push(i);
        l = i;
    });

    return rangeWithDots.slice(0, 7);
});

const goToPage = (page: number) => {
    if (page >= 1 && page <= lastPage) {
        router.get(route('admin.reports.events', { page }), {
            preserveState: true,
            preserveScroll: true,
        });
    }
};

const deleteReport = (id: number) => {
    router.delete(route('user.delete.event', id), {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            toast.success('Report berhasil dihapus', {
                dismissible: true,
                duration: 5000,
                action: {
                    label: 'Tutup',
                    onClick: () => {
                        toast.dismiss();
                    },
                },
            });
        },
        onError: (errors) => {
            toast.error(errors?.message || 'Terjadi kesalahan saat menghapus report', {
                dismissible: true,
                duration: 5000,
                action: {
                    label: 'Tutup',
                    onClick: () => {
                        toast.dismiss();
                    },
                },
            });
        },
    });
};

const navigateToEvent = (event: { hash_id: string; title: string }) => {
    const url = route('user.details.event', {
        hashId: event.hash_id,
        title: event.title.toLowerCase().split(' ').join('-'),
    });

    window.open(url, '_blank');
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getRowNumber = (index: number) => {
    return (currentPage.value - 1) * 10 + index + 1;
};

const page = usePage();

watch(
    () => page.props.flash,
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
</script>

<template>
    <Head title="Report Events" />
    <AdminLayout>
        <Toaster />

        <div class="min-h-screen bg-white p-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-4xl font-bold tracking-tight text-black">Report Events</h1>
                <p class="mt-2 text-sm text-gray-600">Monitor and manage reported events</p>
            </div>

            <!-- Table -->
            <div class="border border-gray-200">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-700 uppercase">No</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-700 uppercase">Event ID</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-700 uppercase">Reason</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-700 uppercase">Reported At</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-700 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="(report, index) in reportEvents" :key="report.id" class="transition-colors hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium whitespace-nowrap text-gray-900">
                                {{ getRowNumber(index) }}
                            </td>
                            <td class="px-6 py-4">
                                <button
                                    @click="navigateToEvent({ hash_id: report.event.hash_id, title: report.event.title })"
                                    class="flex items-center gap-2 text-sm font-medium text-black underline decoration-gray-400 decoration-1 underline-offset-2 transition-colors hover:text-gray-600 hover:decoration-gray-600"
                                >
                                    #{{ report.event_id }}
                                    <ExternalLink :size="14" />
                                </button>
                            </td>
                            <td class="px-6 py-4">
                                <div class="max-w-md text-sm text-gray-900">{{ report.reason }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-600">
                                {{ formatDate(report.created_at) }}
                            </td>
                            <td class="px-6 py-4 text-sm whitespace-nowrap">
                                <button
                                    @click="deleteReport(report.event_id)"
                                    class="text-black transition-colors hover:text-gray-600 focus:outline-none"
                                    title="Delete report"
                                >
                                    <Trash2 :size="18" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="reportEvents.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">No reports found</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-6 flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3">
                <div class="flex items-center gap-2">
                    <button
                        @click="goToPage(currentPage - 1)"
                        :disabled="currentPage === 1"
                        class="flex items-center gap-1 border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-black transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <ChevronLeft :size="16" />
                        Previous
                    </button>

                    <div class="flex items-center gap-1">
                        <template v-for="(page, idx) in paginationNumbers" :key="idx">
                            <button
                                v-if="page !== '...'"
                                @click="goToPage(page as number)"
                                :class="[
                                    'min-w-[40px] border px-3 py-2 text-sm font-medium transition-colors',
                                    currentPage === page
                                        ? 'border-black bg-black text-white'
                                        : 'border-gray-300 bg-white text-black hover:bg-gray-50',
                                ]"
                            >
                                {{ page }}
                            </button>
                            <span v-else class="px-2 text-gray-500">...</span>
                        </template>
                    </div>

                    <button
                        @click="goToPage(currentPage + 1)"
                        :disabled="currentPage === lastPage"
                        class="flex items-center gap-1 border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-black transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Next
                        <ChevronRight :size="16" />
                    </button>
                </div>

                <div class="text-sm text-gray-600">Page {{ currentPage }} of {{ lastPage }}</div>
            </div>
        </div>
    </AdminLayout>
</template>
