<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';

import { CircleUser } from 'lucide-vue-next';
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
    return (searchUser.value = '');
};
</script>

<template>
    <Head title="Explore" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex justify-center p-4">
            <form @submit.prevent="doSearch" class="flex w-full max-w-3xl items-center gap-2">
                <Input v-model="searchUser" class="flex-1" placeholder="🔎 Cari Pengguna" aria-label="Cari pengguna" />
                <Button type="submit" :disabled="!searchUser.trim()">Cari</Button>

                <Button v-if="searchUser" type="button" @click="clearSearch">Clear</Button>
            </form>
        </div>
        <div class="flex flex-col items-center">
            <div v-if="searchUser && props.users.data.length == 0">User Tidak ditemukan</div>
            <div
                v-for="user in props.users.data"
                :key="user.id"
                class="w-full max-w-md p-1"
                @click="
                    $inertia.visit(
                        route('user.details', {
                            hashId: user.hash_id,
                            name: user.name.toLowerCase().split(' ').join('-'),
                        }),
                    )
                "
            >
                <div class="flex gap-2 rounded bg-white p-4 shadow dark:bg-black">
                    <CircleUser></CircleUser>
                    <p
                        class="cursor-pointer text-left"
                        @click="
                            $inertia.visit(
                                route('user.details', {
                                    hashId: user.hash_id,
                                    name: user.name.toLowerCase().split(' ').join('-'),
                                }),
                            )
                        "
                    >
                        {{ user.name }}
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
