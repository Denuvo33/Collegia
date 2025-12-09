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
import { router, useForm, usePage } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Trash, UserPlus } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import 'vue-sonner/style.css';

type User = {
    id: number;
    name: string;
    email: string;
    created_at: string;
    role: string;
};

const props = defineProps<{
    users: {
        data: User[];
        last_page: number;
        current_page: number;
        next_page_url: string | null;
    };
}>();

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'user',
});

const users = ref<User[]>([...props.users.data]);
const showAddModal = ref(false);

// Pagination logic
const currentPage = ref(props.users.current_page);
const lastPage = props.users.last_page;

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
        // currentPage.value = page;
        // Di sini nanti kirim request ke backend
        // router.get(route('users.index', { page }))
        router.get(route('admin.users', { page }), {
            preserveState: true,
            preserveScroll: true,
        });
    }
};

const deleteUser = (userId: number) => {
    return router.delete(route('admin.users.destroy', userId), {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            toast.success('User berhasil dihapus', {
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
            toast.error('Gagal menghapus user: ' + errors, {
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

const addUser = () => {
    if (form.name && form.email) {
        const user: User = {
            id: Date.now(),
            name: form.name,
            email: form.email,
            created_at: new Date().toISOString(),
            role: form.role,
        };

        form.post(route('admin.users.store'), {
            preserveState: false,
            onSuccess: () => {
                toast.success('User berhasil ditambahkan', {
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
                toast.error('Gagal menambahkan user: ' + errors, {
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
        showAddModal.value = false;
    }
};

const changeRole = (userId: number, newRole: string) => {
    const user = users.value.find((u) => u.id === userId);
    if (user) {
        user.role = newRole;

        router.put(route('admin.users.update', userId), {
            role: newRole,
        });
    }
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
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
            form.reset();
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
    <Head title="Users" />
    <AdminLayout>
        <Toaster />

        <!-- Nuxt, vue-sonner v1 because inserting inline CSS with JS to the head tag -->
        <ClientOnly>
            <Toaster />
        </ClientOnly>

        <!-- Nuxt, vue-sonner v2 no need to ClientOnly -->
        <Toaster />

        <div class="min-h-screen bg-white p-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold tracking-tight text-black">User Management</h1>
                    <p class="mt-2 text-sm text-gray-600">Manage your team members and their roles</p>
                </div>
                <button
                    @click="showAddModal = true"
                    class="flex items-center gap-2 bg-black px-6 py-3 text-sm font-medium text-white transition-all hover:bg-gray-800 focus:ring-2 focus:ring-black focus:ring-offset-2 focus:outline-none"
                >
                    <UserPlus :size="18" />
                    Add User
                </button>
            </div>

            <!-- Table -->
            <div class="max-h-[600px] overflow-auto rounded-lg border border-gray-200 shadow-sm">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-700 uppercase">No</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-700 uppercase">Name</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-700 uppercase">Email</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-700 uppercase">Role</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-700 uppercase">Joined</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-700 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="(user, index) in users" :key="user.id" class="transition-colors hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium whitespace-nowrap text-gray-900">
                                {{ getRowNumber(index) }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-black">{{ user.name }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-600">{{ user.email }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <select
                                    :value="user.role"
                                    @change="changeRole(user.id, ($event.target as HTMLSelectElement).value)"
                                    class="border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium tracking-wide text-black uppercase transition-colors focus:border-black focus:ring-1 focus:ring-black focus:outline-none"
                                >
                                    <option value="user">User</option>
                                    <option value="community">Community</option>
                                    <option value="moderator">Moderator</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </td>
                            <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-600">
                                {{ formatDate(user.created_at) }}
                            </td>
                            <td class="px-6 py-4 text-sm whitespace-nowrap">
                                <AlertDialog>
                                    <AlertDialogTrigger><Trash /></AlertDialogTrigger>
                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle>Are you absolutely sure?</AlertDialogTitle>
                                            <AlertDialogDescription>
                                                This action cannot be undone. This will permanently delete your account and remove your data from our
                                                servers.
                                            </AlertDialogDescription>
                                        </AlertDialogHeader>
                                        <AlertDialogFooter>
                                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                                            <AlertDialogAction @click="deleteUser(user.id)">Continue</AlertDialogAction>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog>
                            </td>
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

        <!-- Add User Modal -->
        <div
            v-if="showAddModal"
            class="bg-opacity-50 fixed inset-0 z-50 flex items-center justify-center bg-black"
            @click.self="showAddModal = false"
        >
            <div class="w-full max-w-md border border-gray-200 bg-white p-8 shadow-2xl">
                <h2 class="mb-6 text-2xl font-bold text-black">Add New User</h2>

                <form @submit.prevent="addUser" class="space-y-5">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-black">Name</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            class="w-full border border-gray-300 bg-white px-4 py-2.5 text-black transition-colors focus:border-black focus:ring-1 focus:ring-black focus:outline-none"
                            placeholder="Enter name"
                        />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-black">Email</label>
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            class="w-full border border-gray-300 bg-white px-4 py-2.5 text-black transition-colors focus:border-black focus:ring-1 focus:ring-black focus:outline-none"
                            placeholder="Enter email"
                        />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-black">Password</label>
                        <input
                            v-model="form.password"
                            type="password"
                            required
                            class="w-full border border-gray-300 bg-white px-4 py-2.5 text-black transition-colors focus:border-black focus:ring-1 focus:ring-black focus:outline-none"
                            placeholder="Enter password"
                        />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-black"> Confirm Password</label>
                        <input
                            v-model="form.password_confirmation"
                            type="password"
                            required
                            class="w-full border border-gray-300 bg-white px-4 py-2.5 text-black transition-colors focus:border-black focus:ring-1 focus:ring-black focus:outline-none"
                            placeholder="Enter password"
                        />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-black">Role</label>
                        <select
                            v-model="form.role"
                            class="w-full border border-gray-300 bg-white px-4 py-2.5 text-black transition-colors focus:border-black focus:ring-1 focus:ring-black focus:outline-none"
                        >
                            <option value="user">User</option>
                            <option value="community">Community</option>
                            <option value="moderator">Moderator</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button
                            type="submit"
                            class="flex-1 bg-black px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-gray-800 focus:ring-2 focus:ring-black focus:ring-offset-2 focus:outline-none"
                        >
                            Add User
                        </button>
                        <button
                            type="button"
                            @click="showAddModal = false"
                            class="flex-1 border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-black transition-colors hover:bg-gray-50 focus:ring-2 focus:ring-gray-300 focus:ring-offset-2 focus:outline-none"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
