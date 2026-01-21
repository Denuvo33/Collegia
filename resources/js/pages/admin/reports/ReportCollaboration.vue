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
import { Toaster } from '@/components/ui/sonner';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, ExternalLink } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

import 'vue-sonner/style.css';

type ReportCollaboration = {
    id: number;
    collaboration_id: number;
    reason: string;
    created_at: string;
};

type Collaboration = {
    title: string;
    hash_id: string;
    id: number;
};

const props = defineProps<{
    collaborations: {
        data: (ReportCollaboration & { collaboration: Collaboration })[];
        last_page: number;
        current_page: number;
        next_page_url: string | null;
    };
}>();

const reportCollaborations = ref<(ReportCollaboration & { collaboration: Collaboration })[]>([...props.collaborations.data]);

// Pagination logic
const currentPage = ref(props.collaborations.current_page);
const lastPage = props.collaborations.last_page;

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
        router.get(route('admin.report.collaborations', { page }), {
            preserveState: true,
            preserveScroll: true,
        });
    }
};

const deleteCollaboration = (id: number) => {
    router.delete(route('admin.collaboration.destroy', id), {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            toast.success('Kolaborasi berhasil dihapus', {
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
            toast.error(errors?.message || 'Terjadi kesalahan saat menghapus kolaborasi', {
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

const deleteFalseReport = (id: number) => {
    router.delete(route('admin.reports.collaboration.destroy', id), {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            toast.success('False report berhasil dihapus', {
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
            toast.error(errors?.message || 'Terjadi kesalahan saat menghapus false report', {
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

const navigateToCollaboration = (collaboration: { hash_id: string; title: string }) => {
    const url = route('user.collaboration.details', collaboration.hash_id);

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
    <Head title="Report Collaborations" />
    <AdminLayout>
        <Toaster />

        <div class="min-h-screen bg-white p-4 md:p-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold tracking-tight text-black md:text-4xl">Report Collaborations</h1>
                <p class="mt-2 text-sm text-gray-600">Monitor and manage reported collaborations</p>
            </div>

            <!-- Table Responsive Container -->
            <div class="overflow-x-auto border border-gray-200">
                <table class="w-full min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th class="px-3 py-4 text-left text-xs font-semibold tracking-wider whitespace-nowrap text-gray-700 uppercase md:px-6">
                                No
                            </th>
                            <th class="px-3 py-4 text-left text-xs font-semibold tracking-wider whitespace-nowrap text-gray-700 uppercase md:px-6">
                                Collab ID
                            </th>
                            <th class="px-3 py-4 text-left text-xs font-semibold tracking-wider text-gray-700 uppercase md:px-6">Reason</th>
                            <th class="px-3 py-4 text-left text-xs font-semibold tracking-wider whitespace-nowrap text-gray-700 uppercase md:px-6">
                                Reported At
                            </th>
                            <th class="px-3 py-4 text-left text-xs font-semibold tracking-wider text-gray-700 uppercase md:px-6">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="(report, index) in reportCollaborations" :key="report.id" class="transition-colors hover:bg-gray-50">
                            <td class="px-3 py-4 text-sm font-medium whitespace-nowrap text-gray-900 md:px-6">
                                {{ getRowNumber(index) }}
                            </td>
                            <td class="px-3 py-4 md:px-6">
                                <button
                                    @click="navigateToCollaboration({ hash_id: report.collaboration.hash_id, title: report.collaboration.title })"
                                    class="flex items-center gap-2 text-sm font-medium text-black underline decoration-gray-400 decoration-1 underline-offset-2 transition-colors hover:text-gray-600 hover:decoration-gray-600"
                                >
                                    #{{ report.collaboration.id }}
                                    <ExternalLink :size="14" />
                                </button>
                            </td>
                            <td class="px-3 py-4 md:px-6">
                                <div class="max-w-xs text-sm text-gray-900 md:max-w-md">{{ report.reason }}</div>
                            </td>
                            <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-600 md:px-6">
                                {{ formatDate(report.created_at) }}
                            </td>
                            <td class="px-3 py-4 text-sm md:px-6">
                                <div class="flex flex-col gap-2 sm:flex-row">
                                    <AlertDialog>
                                        <AlertDialogTrigger
                                            class="rounded bg-red-500 px-2 py-2 text-xs font-bold whitespace-nowrap text-white md:px-3 md:text-sm"
                                            >Delete Collab</AlertDialogTrigger
                                        >
                                        <AlertDialogContent>
                                            <AlertDialogHeader>
                                                <AlertDialogTitle>Are you absolutely sure? (#{{ report.collaboration.id }})</AlertDialogTitle>
                                                <AlertDialogDescription>
                                                    This action cannot be undone. This will permanently delete the collaboration along with all its
                                                    reports.
                                                </AlertDialogDescription>
                                            </AlertDialogHeader>
                                            <AlertDialogFooter>
                                                <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                <AlertDialogAction @click="deleteCollaboration(report.collaboration.id)">Continue</AlertDialogAction>
                                            </AlertDialogFooter>
                                        </AlertDialogContent>
                                    </AlertDialog>
                                    <AlertDialog>
                                        <AlertDialogTrigger
                                            class="rounded bg-yellow-500 px-2 py-2 text-xs font-bold whitespace-nowrap text-white md:px-3 md:text-sm"
                                            >Delete False Report</AlertDialogTrigger
                                        >
                                        <AlertDialogContent>
                                            <AlertDialogHeader>
                                                <AlertDialogTitle>Are you absolutely sure? (#{{ report.collaboration.id }})</AlertDialogTitle>
                                                <AlertDialogDescription>
                                                    This action cannot be undone. This will permanently delete the false report.
                                                </AlertDialogDescription>
                                            </AlertDialogHeader>
                                            <AlertDialogFooter>
                                                <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                <AlertDialogAction @click="deleteFalseReport(report.id)">Continue</AlertDialogAction>
                                            </AlertDialogFooter>
                                        </AlertDialogContent>
                                    </AlertDialog>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="reportCollaborations.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">No reports found</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-6 flex flex-col items-center justify-between gap-4 border-t border-gray-200 bg-white px-4 py-3 sm:flex-row">
                <div class="flex w-full items-center gap-2 overflow-x-auto sm:w-auto">
                    <button
                        @click="goToPage(currentPage - 1)"
                        :disabled="currentPage === 1"
                        class="flex items-center gap-1 border border-gray-300 bg-white px-2 py-2 text-xs font-medium whitespace-nowrap text-black transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 md:px-3 md:text-sm"
                    >
                        <ChevronLeft :size="16" />
                        <span class="hidden sm:inline">Previous</span>
                        <span class="sm:hidden">Prev</span>
                    </button>

                    <div class="flex items-center gap-1">
                        <template v-for="(page, idx) in paginationNumbers" :key="idx">
                            <button
                                v-if="page !== '...'"
                                @click="goToPage(page as number)"
                                :class="[
                                    'min-w-[32px] border px-2 py-2 text-xs font-medium transition-colors md:min-w-[40px] md:px-3 md:text-sm',
                                    currentPage === page
                                        ? 'border-black bg-black text-white'
                                        : 'border-gray-300 bg-white text-black hover:bg-gray-50',
                                ]"
                            >
                                {{ page }}
                            </button>
                            <span v-else class="px-1 text-gray-500 md:px-2">...</span>
                        </template>
                    </div>

                    <button
                        @click="goToPage(currentPage + 1)"
                        :disabled="currentPage === lastPage"
                        class="flex items-center gap-1 border border-gray-300 bg-white px-2 py-2 text-xs font-medium whitespace-nowrap text-black transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 md:px-3 md:text-sm"
                    >
                        <span class="hidden sm:inline">Next</span>
                        <span class="sm:hidden">Next</span>
                        <ChevronRight :size="16" />
                    </button>
                </div>

                <div class="text-xs text-gray-600 md:text-sm">Page {{ currentPage }} of {{ lastPage }}</div>
            </div>
        </div>
    </AdminLayout>
</template>
