<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';

import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Event',
        href: '/user/event',
    },
];
</script>

<template>
    <Head title="Event" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div>
                <Button @click="$inertia.visit(route('user.create-event'))">Buat Event</Button>
            </div>
        </div>
    </AppLayout>
</template>
