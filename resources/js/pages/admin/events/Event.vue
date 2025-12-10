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
import { ChevronLeft, ChevronRight, ExternalLink, Search, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

import 'vue-sonner/style.css';

type Event = {
    id: number;
    title: string;
    hash_id: string;
    organizer: string;
    created_at: string;
    date_event: string;
    status: 'akan datang' | 'berjalan' | 'selesai';
    promoted_till: string | null;
};

const props = defineProps<{
    events: {
        data: Event[];
        last_page: number;
        current_page: number;
        next_page_url: string | null;
    };
    filters?: {
        status?: string;
        search?: string;
    };
}>();

const allEvents = ref<Event[]>([...props.events.data]);
const statusFilter = ref<string>(props.filters?.status || 'all');
const searchQuery = ref<string>(props.filters?.search || '');

// Calculate event status based on date_event
const getEventStatus = (dateEvent: string): 'akan datang' | 'berjalan' | 'selesai' => {
    const eventDate = new Date(dateEvent);
    const now = new Date();

    // Set time to start of day for accurate comparison
    const eventDay = new Date(eventDate.getFullYear(), eventDate.getMonth(), eventDate.getDate());
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

    if (eventDay > today) {
        return 'akan datang';
    } else if (eventDay.getTime() === today.getTime()) {
        return 'berjalan';
    } else {
        return 'selesai';
    }
};

// Pagination logic
const currentPage = ref(props.events.current_page);
const lastPage = props.events.last_page;

const filteredEvents = computed(() => {
    let filtered = allEvents.value;

    // Filter by status
    if (statusFilter.value !== 'all') {
        filtered = filtered.filter((event) => event.status === statusFilter.value);
    }

    // Filter by search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter((event) => event.title.toLowerCase().includes(query) || event.organizer.toLowerCase().includes(query));
    }

    return filtered;
});

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
        router.get(route('admin.event', { page, status: statusFilter.value, search: searchQuery.value }), {
            preserveState: true,
            preserveScroll: true,
        });
    }
};

const deleteEvent = (id: number) => {
    if (confirm('Are you sure you want to delete this event?')) {
        router.delete(route('admin.event.destroy', id), {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => {
                allEvents.value = allEvents.value.filter((e) => e.id !== id);
                toast.success('Event berhasil dihapus', {
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
                toast.error(errors?.message || 'Terjadi kesalahan saat menghapus event', {
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

const deleteAllCompleteEvents = () => {
    router.delete(route('admin.events.delete.completed'), {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            allEvents.value = allEvents.value.filter((e) => e.status !== 'selesai');
            toast.success('All complete events have been deleted', {
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
            toast.error(errors?.message || 'An error occurred while deleting complete events', {
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

const navigateToEvent = (event: Event) => {
    const url = route('user.details.event', {
        hashId: event.hash_id,
        title: event.title.toLowerCase().split(' ').join('-'),
    });
    window.open(url, '_blank');
};

const filterByStatus = (status: string) => {
    statusFilter.value = status;
    currentPage.value = 1;
    router.get(route('admin.event', { page: 1, status: status, search: searchQuery.value }), {
        preserveState: true,
        preserveScroll: true,
    });
};

const handleSearch = () => {
    currentPage.value = 1;
    router.get(route('admin.event', { page: 1, status: statusFilter.value, search: searchQuery.value }), {
        preserveState: true,
        preserveScroll: true,
    });
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatDateTime = (date: string) => {
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
    <Head title="Events Management" />
    <AdminLayout>
        <Toaster />

        <div class="min-h-screen bg-white p-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-4xl font-bold tracking-tight text-black">Events Management</h1>
                <p class="mt-2 text-sm text-gray-600">Monitor and manage all events</p>
            </div>

            <!-- Filters -->
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <!-- Search Bar -->
                <div class="relative max-w-md flex-1">
                    <Search class="absolute top-1/2 left-3 -translate-y-1/2 text-gray-400" :size="18" />
                    <input
                        v-model="searchQuery"
                        @keyup.enter="handleSearch"
                        type="text"
                        placeholder="Search by title or organizer..."
                        class="w-full border border-gray-300 bg-white py-2.5 pr-4 pl-10 text-sm text-black transition-colors focus:border-black focus:ring-1 focus:ring-black focus:outline-none"
                    />
                </div>

                <!-- Status Filter -->
                <div class="flex gap-2">
                    <button
                        @click="filterByStatus('all')"
                        :class="[
                            'border px-4 py-2 text-sm font-medium transition-colors',
                            statusFilter === 'all' ? 'border-black bg-black text-white' : 'border-gray-300 bg-white text-black hover:bg-gray-50',
                        ]"
                    >
                        All
                    </button>
                    <button
                        @click="filterByStatus('akan datang')"
                        :class="[
                            'border px-4 py-2 text-sm font-medium transition-colors',
                            statusFilter === 'akan datang'
                                ? 'border-black bg-black text-white'
                                : 'border-gray-300 bg-white text-black hover:bg-gray-50',
                        ]"
                    >
                        Akan Datang
                    </button>
                    <button
                        @click="filterByStatus('berjalan')"
                        :class="[
                            'border px-4 py-2 text-sm font-medium transition-colors',
                            statusFilter === 'berjalan' ? 'border-black bg-black text-white' : 'border-gray-300 bg-white text-black hover:bg-gray-50',
                        ]"
                    >
                        Berjalan
                    </button>
                    <button
                        @click="filterByStatus('selesai')"
                        :class="[
                            'border px-4 py-2 text-sm font-medium transition-colors',
                            statusFilter === 'selesai' ? 'border-black bg-black text-white' : 'border-gray-300 bg-white text-black hover:bg-gray-50',
                        ]"
                    >
                        Selesai
                    </button>
                    <AlertDialog>
                        <AlertDialogTrigger class="rounded bg-red-500 p-2 text-white">Delete All Complete Events</AlertDialogTrigger>
                        <AlertDialogContent>
                            <AlertDialogHeader>
                                <AlertDialogTitle>Are you absolutely sure?</AlertDialogTitle>
                                <AlertDialogDescription>
                                    You will delete all events with status "selesai". This action cannot be undone.
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                                <AlertDialogCancel>Cancel</AlertDialogCancel>
                                <AlertDialogAction @click="deleteAllCompleteEvents">Continue</AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>
                </div>
            </div>

            <!-- Table -->
            <div class="border border-gray-200">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-700 uppercase">No</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-700 uppercase">Title</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-700 uppercase">Organizer</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-700 uppercase">Created At</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-700 uppercase">Event Date</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-700 uppercase">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-700 uppercase">Promoted</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-700 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="(event, index) in filteredEvents" :key="event.id" class="transition-colors hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium whitespace-nowrap text-gray-900">
                                {{ getRowNumber(index) }}
                            </td>
                            <td class="px-6 py-4">
                                <button
                                    @click="navigateToEvent(event)"
                                    class="flex items-center gap-2 text-sm font-medium text-black underline decoration-gray-400 decoration-1 underline-offset-2 transition-colors hover:text-gray-600 hover:decoration-gray-600"
                                >
                                    {{ event.title }}
                                    <ExternalLink :size="14" />
                                </button>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">{{ event.organizer }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-600">
                                {{ formatDate(event.created_at) }}
                            </td>
                            <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-600">
                                {{ formatDateTime(event.date_event) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="[
                                        'inline-flex border px-3 py-1 text-xs font-medium tracking-wide uppercase',
                                        getEventStatus(event.date_event) === 'akan datang'
                                            ? 'border-gray-300 bg-blue-500 text-gray-800'
                                            : getEventStatus(event.date_event) === 'berjalan'
                                              ? 'border-black bg-black text-white'
                                              : 'border-gray-400 bg-gray-200 text-gray-600',
                                    ]"
                                >
                                    {{ getEventStatus(event.date_event) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <p
                                    v-if="event.promoted_till != null && new Date(event.promoted_till) >= new Date()"
                                    class="font-bold text-yellow-600"
                                >
                                    Active
                                </p>
                            </td>
                            <td class="px-6 py-4 text-sm whitespace-nowrap">
                                <button
                                    @click="deleteEvent(event.id)"
                                    class="text-black transition-colors hover:text-gray-600 focus:outline-none"
                                    title="Delete event"
                                >
                                    <Trash2 :size="18" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="filteredEvents.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500">No events found</td>
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
