<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';

type User = {
    id: number;
    name: string;
    email: string;
    created_at: string;
    role: string;
};

const props = defineProps<{
    users: User[];
}>();
</script>

<template>
    <Head title="Home" />
    <AdminLayout>
        <div>
            <h1 class="text-3xl font-bold">Admin Dashboard</h1>
        </div>
    </AdminLayout>
</template>
