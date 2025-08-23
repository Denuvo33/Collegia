<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useRemember } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import 'dayjs/locale/id'
import relativeTime from 'dayjs/plugin/relativeTime';
import { MessageCircle } from 'lucide-vue-next';
import { ref } from 'vue';


dayjs.locale('id')

dayjs.extend(relativeTime)

const activatedTabs = useRemember(ref<'post' | 'comment'| 'inkrip'>('post'),('activeTabs'))

const formatTime = (date:string)=>{
    return dayjs(date).fromNow()
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile',
        href: '/user/profile',
    },
];

const props = defineProps<{
    data: {
        name: string,
        created_at:string,

        comments:Array<{
        id:number,
        content:string,
        created_at:string
        hash_id: string
        }>;

        posts:Array<{
        content:string
        image:string | null
        id:number
        hash_id: string
        created_at:string
        }>;
    }
}>()

const sortList = <T extends {created_at: string}>(list:Array<T>)=>{
    return list.sort((a,b)=>{
        const dateA = new Date(a.created_at)
        const dateB = new Date(b.created_at)

        return dateB.getTime() - dateA.getTime()
    })
}

</script>

<template>
    <Head title="Profile" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col items-center gap-4 ">
            <h1 class="font-bold text-xl">{{ data.name }}</h1>
            <div class="flex flex-wrap gap-5">
                <div class="flex flex-col border  shadow rounded p-2">
                    <p>{{ data.posts.length }}</p>
                    <p>Postingan</p>
                </div>
                <div class="flex flex-col border  rounded p-2">
                    <p>0</p>
                    <p>Inkrip</p>
                </div>
            </div>
            <p class="text-gray-400">Bergabung {{ formatTime(data.created_at) }}</p>
        </div>
        <hr>
        <!-- Selection -->
        <div class="flex flex-row justify-center gap-8">
            <button class="text-xl" @click="activatedTabs = 'post'" :class="activatedTabs == 'post' ? 'border-b-2 border-white' : ''">Post</button>
            <button class="text-xl" :class="activatedTabs == 'comment' ? 'border-b-2 border-white' : ''" @click="activatedTabs = 'comment'">Komentar</button>
            <button class="text-xl" :class="activatedTabs == 'inkrip' ? 'border-b-2 border-white' : ''" @click="activatedTabs = 'inkrip'">Inkrip</button>
        </div>
        <div class="flex flex-col p-2" v-if="activatedTabs == 'post'">
            <div v-for="post in sortList(data.posts)" :key="post.id" class="p-2" @click="$inertia.visit(route('user.post.details',post.hash_id))">
                <div class="bg-white dark:bg-black p-4 rounded shadow space-y-2">
                    <h1 class="font-bold">
                       {{ data.name }} 
                    </h1>
                    <h1>
                       {{ post.content }} 
                    </h1>
                    <img :src="`/storage/${post.image}`" v-if="post.image" alt="post image" class="w-80 h-80 object-contain">
                    <p class="text-gray-400">dibuat {{ formatTime(post.created_at) }}</p>
                    <MessageCircle class="cursor-pointer" @click="$inertia.visit(route('user.post.details',post.hash_id))"></MessageCircle>
                </div>
            </div>
        </div>
        <div class="flex flex-col p-2" v-if="activatedTabs == 'comment'">
            <div v-for="comment in sortList(data.comments)" :key="comment.id" class="p-2 "  @click="$inertia.visit(route('user.post.details',comment.hash_id))">
                <div class="bg-white dark:bg-black p-4 rounded shadow">
                    <h1 class="font-bold">
                       {{ data.name }} 
                    </h1>
                    <h1>
                       {{ comment.content }} 
                    </h1>
                
                    <p class="text-gray-400">dibuat {{ formatTime(comment.created_at) }}</p>
                    
                </div>
            </div>
        </div>
        
    </AppLayout>
</template>
