<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ChevronRight, Compass, Search, UserCheck, UserPlus, Users, X } from 'lucide-vue-next';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Explore',
        href: '/user/explore',
    },
];

const props = defineProps<{
    users: {
        data: Array<{ id: number; name: string; hash_id: string }>;
        current_page: number;
        last_page: number;
    };
    filters: { search?: string };
}>();

const searchUser = ref(props.filters.search ?? '');

const doSearch = () => {
    router.get('/user/explore', { search: searchUser.value }, { preserveState: true, replace: true });
};

const clearSearch = () => {
    searchUser.value = '';
    router.get('/user/explore', {}, { preserveState: true, replace: true });
};

function getInitials(name: string) {
    return name
        .split(' ')
        .map((word) => word.charAt(0))
        .join('')
        .toUpperCase()
        .slice(0, 2);
}

function navigateToProfile(user: any) {
    router.visit(
        route('user.details', {
            hashId: user.hash_id,
            name: user.name.toLowerCase().split(' ').join('-'),
        }),
    );
}
</script>

<template>
    <Head title="Explore" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen">
            <div class="mx-auto max-w-4xl px-4 py-8">
                <!-- Header Section -->
                <div class="mb-8 text-center">
                    <div class="mb-4 flex justify-center">
                        <div class="rounded-2xl bg-white p-4 shadow-lg">
                            <Compass class="h-8 w-8 text-black" />
                        </div>
                    </div>
                    <h1 class="mb-2 text-3xl font-bold">Jelajahi Pengguna</h1>
                    <p class="mx-auto max-w-md">Temukan dan terhubung dengan pengguna lain di platform ini</p>
                </div>

                <!-- Search Section -->
                <div class="mb-8">
                    <div class="rounded-2xl border border-gray-200 p-6 shadow-sm">
                        <form @submit.prevent="doSearch" class="flex items-center gap-3">
                            <div class="relative flex-1">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                    <Search class="h-5 w-5 text-gray-400" />
                                </div>
                                <Input
                                    v-model="searchUser"
                                    class="focus:ring-opacity-50 rounded-xl border-gray-200 py-3 pr-12 pl-12 text-lg focus:border-indigo-300 focus:ring focus:ring-indigo-200"
                                    placeholder="Cari nama pengguna..."
                                    aria-label="Cari pengguna"
                                />
                                <button
                                    v-if="searchUser"
                                    type="button"
                                    @click="clearSearch"
                                    class="absolute inset-y-0 right-0 flex items-center pr-4 transition-colors hover:text-gray-700"
                                >
                                    <X class="h-5 w-5 text-gray-400" />
                                </button>
                            </div>
                            <Button
                                type="submit"
                                :disabled="!searchUser.trim()"
                                class="rounded-xl px-6 py-3 font-medium text-black shadow-sm transition-all hover:bg-gray-500 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <Search class="mr-2 h-4 w-4" />
                                Cari
                            </Button>
                        </form>

                        <!-- Search Stats -->
                        <div v-if="searchUser" class="mt-4 border-t border-gray-100 pt-4">
                            <div class="flex items-center justify-between text-sm">
                                <span class="">
                                    Mencari: <span class="font-semibold">"{{ searchUser }}"</span>
                                </span>
                                <span class=""> {{ props.users.data.length }} hasil ditemukan </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Results Section -->
                <div class="space-y-6">
                    <!-- Empty State for Search -->
                    <div v-if="searchUser && props.users.data.length === 0" class="py-16 text-center">
                        <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-gray-100">
                            <Users class="h-10 w-10 text-gray-400" />
                        </div>
                        <h3 class="mb-2 text-xl font-semibold text-gray-500">Pengguna tidak ditemukan</h3>
                        <p class="mx-auto mb-6 max-w-md text-gray-500">
                            Tidak ada pengguna yang cocok dengan pencarian "{{ searchUser }}". Coba gunakan kata kunci yang berbeda.
                        </p>
                        <Button @click="clearSearch" variant="outline" class="rounded-xl">
                            <X class="mr-2 h-4 w-4" />
                            Hapus Pencarian
                        </Button>
                    </div>

                    <!-- Empty State for No Search -->
                    <div v-else-if="!searchUser && props.users.data.length === 0" class="py-16 text-center">
                        <div
                            class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-blue-100 to-indigo-100"
                        >
                            <Compass class="h-10 w-10 text-indigo-600" />
                        </div>
                        <h3 class="mb-2 text-xl font-semibold text-gray-500">Mulai Jelajahi</h3>
                        <p class="mx-auto max-w-md text-gray-500">Gunakan kolom pencarian di atas untuk menemukan pengguna yang ingin Anda ikuti</p>
                    </div>

                    <!-- Users Grid -->
                    <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="user in props.users.data"
                            :key="user.id"
                            @click="navigateToProfile(user)"
                            class="group cursor-pointer overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-indigo-200 hover:shadow-md dark:bg-gray-800"
                        >
                            <!-- User Card -->
                            <div class="p-6">
                                <div class="flex items-center gap-4">
                                    <!-- Avatar -->
                                    <div class="flex-shrink-0">
                                        <div
                                            class="flex h-14 w-14 items-center justify-center rounded-xl bg-black text-lg font-semibold text-white shadow-lg"
                                        >
                                            {{ getInitials(user.name) }}
                                        </div>
                                    </div>

                                    <!-- User Info -->
                                    <div class="min-w-0 flex-1">
                                        <h3 class="mb-1 truncate text-lg font-semibold text-gray-500 transition-colors group-hover:text-indigo-600">
                                            {{ user.name }}
                                        </h3>
                                        <div class="flex items-center gap-2 text-sm text-gray-500">
                                            <UserCheck class="h-4 w-4" />
                                            <span>Pengguna Aktif</span>
                                        </div>
                                    </div>

                                    <!-- Arrow Icon -->
                                    <div class="flex-shrink-0">
                                        <ChevronRight
                                            class="h-5 w-5 text-gray-400 transition-all duration-200 group-hover:translate-x-1 group-hover:text-indigo-500"
                                        />
                                    </div>
                                </div>

                                <!-- Action Hint -->
                                <div class="mt-4 border-t border-gray-100 pt-4">
                                    <div class="flex items-center gap-2 text-sm text-gray-600 transition-colors group-hover:text-indigo-600">
                                        <UserPlus class="h-4 w-4" />
                                        <span>Klik untuk melihat profil</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination Info -->
                    <div v-if="props.users.data.length > 0" class="py-6 text-center">
                        <div class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm text-gray-600">
                            <Users class="h-4 w-4" />
                            <span>Halaman {{ props.users.current_page }} dari {{ props.users.last_page }}</span>
                        </div>
                    </div>
                </div>

                <!-- Bottom Spacing -->
                <div class="h-8"></div>
            </div>
        </div>
    </AppLayout>
</template>
