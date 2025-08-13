<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Create Post',
        href: '/user/create-post',
    },
];
</script>

<template>
    <Head title="Create Post" />

    <AppLayout :breadcrumbs="breadcrumbs">
        
    </AppLayout>
</template>
