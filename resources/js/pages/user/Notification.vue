<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Notification',
        href: '/user/notification',
    },
];
</script>

<template>
    <Head title="Notification'," />

    <AppLayout :breadcrumbs="breadcrumbs">
        
    </AppLayout>
</template>
