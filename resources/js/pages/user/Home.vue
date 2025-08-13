<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Label } from "@/components/ui/label"
import { Heart, MessageCircle } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Home',
        href: '/user/home',
    },
];

defineProps<{
    data: Array<{
        id: number;
        user_id: number;
        content: string;
        image?: string;
        created_at: string;
        user: {
            id: number;
            name: string;
            email: string;
        };
    }>
}>()
</script>

<template>
    <Head title="Home" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 space-y-4">
            <div class="p-4 bg-black rounded space-y-4" v-for="posts in data" :key="posts.id">
                <h1 class="text-2xl font-bold text-white">{{ posts.user.name }}</h1>
                <hr>
                <p>{{ posts.content }}</p>
                <img v-if="posts.image" :src="`/storage/${posts.image}`" alt="Post Image" class="w-80 h-80 object-contain" />
                <div class="flex flex-row gap-6">
                    <input type="hidden" name="like" id="like" />
                    <Label class="cursor-pointer">
                         <Heart class="" for="like" />
                    </Label>
                    <input type="hidden" name="comment" id="comment" />
                    <Label class="cursor-pointer">
                         <MessageCircle class="" for="comment" />
                    </Label>
                     
                </div>
                <p class="text-gray-400">Dibuat pada {{ posts.created_at }}</p>
            </div>
        </div>
        
    </AppLayout>
</template>
