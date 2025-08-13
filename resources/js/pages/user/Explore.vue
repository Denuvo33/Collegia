<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Explore',
        href: '/user/explore',
    },
];
</script>

<template>
    <Head title="Explore" />

    <AppLayout :breadcrumbs="breadcrumbs">
        
    </AppLayout>
</template>
