<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useRemember } from '@inertiajs/vue3';

import dayjs from 'dayjs';
import 'dayjs/locale/id';
import relativeTime from 'dayjs/plugin/relativeTime';
import { MessageCircle } from 'lucide-vue-next';
import { ref } from 'vue';

dayjs.locale('id');
dayjs.extend(relativeTime);
defineProps<{
    user: {
        name: string;
        created_at: string;
    };
    posts: Array<{
        id: number;
        content: string;
        hash_id: string;
        image: string | null;
        created_at: string;
    }>;
    comments: Array<{
        content: string;
        created_at: string;
        hash_id: string;
        id: number;
        post_id: number;
    }>;
}>();
const breadcrumbs: BreadcrumbItem[] = [];

const activeTabs = useRemember(ref<'posts' | 'comments' | 'inkrip'>('posts'), 'activeTabs');

function formatTime(date: string) {
    return dayjs(date).fromNow();
}

function sortList<T extends { created_at: string }>(list: Array<T>) {
    return list.sort((a, b) => {
        const dateA = new Date(a.created_at);
        const dateB = new Date(b.created_at);
        return dateB.getTime() - dateA.getTime();
    });
}
</script>

<template>
    <Head :title="`${user.name} - Profile`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col p-4">
            <h1 class="text-xl font-bold">{{ user.name }}</h1>
            <p>Bergabung {{ formatTime(user.created_at) }}</p>
        </div>

        <div class="flex justify-center gap-8">
            <button class="text-lg" @click="activeTabs = 'posts'" :class="activeTabs == 'posts' ? 'border-b-2 border-black dark:border-white' : ''">
                Post
            </button>
            <button
                @click="activeTabs = 'comments'"
                class="text-lg"
                :class="activeTabs == 'comments' ? 'border-b-2 border-black dark:border-white' : ''"
            >
                Comment
            </button>
            <button @click="activeTabs = 'inkrip'" class="text-lg" :class="activeTabs == 'inkrip' ? 'border-b-2 border-black dark:border-white' : ''">
                Inkrip
            </button>
        </div>

        <hr />

        <div class="p-1">
            <div v-if="activeTabs == 'posts'">
                <div v-for="post in sortList(posts)" @click="$inertia.visit(route('user.post.details', post.hash_id))" :key="post.id" class="p-4">
                    <div class="flex flex-col gap-4 rounded bg-white p-4 shadow dark:bg-black">
                        <p class="font-bold">{{ user.name }}</p>
                        <hr />
                        <p>{{ post.content }}</p>
                        <img v-if="post.image" :src="`/storage/${post.image}`" class="h-80 w-80 object-contain" alt="post image" />
                        <p class="text-gray-400">dibuat {{ formatTime(post.created_at) }}</p>
                        <MessageCircle class="cursor-pointer" @click="$inertia.visit(route('user.post.details', post.hash_id))"></MessageCircle>
                    </div>
                </div>
            </div>

            <div v-else-if="activeTabs == 'comments'">
                <div
                    @click="$inertia.visit(route('user.post.details', comment.hash_id))"
                    v-for="comment in sortList(comments)"
                    :key="comment.id"
                    class="p-4"
                >
                    <div class="flex flex-col rounded bg-white p-4 shadow dark:bg-black">
                        <p class="font-bold">{{ user.name }}</p>
                        <hr />
                        <p>{{ comment.content }}</p>
                        <p class="text-gray-400">dibuat {{ formatTime(comment.created_at) }}</p>
                    </div>
                </div>
            </div>
            <div v-else-if="activeTabs == 'inkrip'">
                <div class="flex justify-center"><p>Tidak tersedia saat ini</p></div>
            </div>
        </div>
    </AppLayout>
</template>
