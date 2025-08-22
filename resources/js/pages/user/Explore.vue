<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Input } from "@/components/ui/input"
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Explore',
        href: '/user/explore',
    },
];

const searchUser = ref('')

const props = defineProps<{
    users: Array<{
        id:number,
        name:string
    }>
}>()

const filteredUsers = computed(() => {
  if (!searchUser.value) return []
  return props.users.filter(user =>
    user.name.toLowerCase().includes(searchUser.value.toLowerCase())
  )
})
</script>

<template>
    <Head title="Explore" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex justify-center p-4">
            <Input v-model="searchUser" class="w-8/12" placeholder="🔎 Cari Pengguna"/>
        </div>
    <div class="flex flex-col items-center">
        <div v-if="searchUser && filteredUsers.length == 0">User Tidak ditemukan</div>
        <div v-for="user in filteredUsers" :key="user.id" class="w-full max-w-md">
            <p class="text-left">{{ user.name }}</p>
        </div>
    </div>

        
    </AppLayout>
</template>
