<script setup lang="ts">
import { Toaster } from '@/components/ui/sonner';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, ShieldAlert, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import 'vue-sonner/style.css';

type ReportComment = {
    id: number;
    comment_id: number;
    reason: string;
    created_at: string;
};

type Comment = {
    id: number;
    content: string;
};

const props = defineProps<{
    comments: {
        data: (ReportComment & { comment: Comment })[];
        last_page: number;
        current_page: number;
        next_page_url: string | null;
    };
}>();

const reportComments = ref<(ReportComment & { comment: Comment })[]>([...props.comments.data]);

// Pagination logic
const currentPage = ref(props.comments.current_page);
const lastPage = props.comments.last_page;

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
        router.get(route('admin.reports.comments', { page }), {
            preserveState: true,
            preserveScroll: true,
        });
    }
};

const deleteComment = (commentId: number) => {
    if (confirm('Are you sure you want to delete this comment? This action cannot be undone.')) {
        router.delete(route('user.comment.delete', commentId), {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => {
                reportComments.value = reportComments.value.filter((r) => r.comment_id !== commentId);
                toast.success('Komentar berhasil dihapus', {
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
                toast.error(errors?.message || 'Terjadi kesalahan saat menghapus komentar', {
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
    }
};

const deleteFalseReport = (reportId: number) => {
    if (confirm('Tandai laporan ini sebagai laporan palsu dan hapus?')) {
        router.delete(route('admin.reports.comment.destroy', reportId), {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => {
                reportComments.value = reportComments.value.filter((r) => r.id !== reportId);
                toast.success('Laporan palsu berhasil dihapus', {
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
                toast.error(errors?.message || 'Terjadi kesalahan saat menghapus laporan', {
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
    }
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

const truncateText = (text: string, maxLength: number = 100) => {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
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
    <Head title="Report Comments" />
    <AdminLayout>
        <Toaster />

        <div class="min-h-screen bg-white p-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-4xl font-bold tracking-tight text-black">Report Comments</h1>
                <p class="mt-2 text-sm text-gray-600">Monitor and manage reported comments</p>
            </div>

            <!-- Table -->
            <div class="border border-gray-200">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-700 uppercase">No</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-700 uppercase">Comment ID</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-700 uppercase">Comment</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-700 uppercase">Reason</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-700 uppercase">Reported At</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-700 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="(report, index) in reportComments" :key="report.id" class="transition-colors hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium whitespace-nowrap text-gray-900">
                                {{ getRowNumber(index) }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-black">#{{ report.comment_id }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="max-w-xs text-sm text-gray-900" :title="report.comment.content">
                                    {{ truncateText(report.comment.content, 80) }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="max-w-md text-sm text-gray-900">{{ report.reason }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-600">
                                {{ formatDate(report.created_at) }}
                            </td>
                            <td class="px-6 py-4 text-sm whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <button
                                        @click="deleteComment(report.comment_id)"
                                        class="text-black transition-colors hover:text-red-600 focus:outline-none"
                                        title="Delete comment"
                                    >
                                        <Trash2 :size="18" />
                                    </button>
                                    <button
                                        @click="deleteFalseReport(report.id)"
                                        class="text-gray-600 transition-colors hover:text-black focus:outline-none"
                                        title="Mark as false report"
                                    >
                                        <ShieldAlert :size="18" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="reportComments.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">No reports found</td>
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

            <!-- Legend -->
            <div class="mt-6 flex items-center gap-6 rounded border border-gray-200 bg-gray-50 px-4 py-3">
                <div class="flex items-center gap-2">
                    <Trash2 :size="16" class="text-black" />
                    <span class="text-sm text-gray-700">Delete Comment</span>
                </div>
                <div class="flex items-center gap-2">
                    <ShieldAlert :size="16" class="text-gray-600" />
                    <span class="text-sm text-gray-700">Mark as False Report</span>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
