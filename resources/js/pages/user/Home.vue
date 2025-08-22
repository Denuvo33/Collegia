<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Label } from "@/components/ui/label"
import { Heart, MessageCircle,EllipsisVertical } from 'lucide-vue-next';
import { computed } from 'vue';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuPortal,
  DropdownMenuSeparator,
  DropdownMenuShortcut,
  DropdownMenuSub,
  DropdownMenuSubContent,
  DropdownMenuSubTrigger,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import dayjs from "dayjs";
import 'dayjs/locale/id'
import relativeTime from 'dayjs/plugin/relativeTime'

dayjs.extend(relativeTime)
dayjs.locale('id')


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Home',
        href: '/user/home',
    },
];

const props = defineProps<{
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
            role: string;
        };
    }>
}>()

const sortedPosts = computed(() => {
  return props.data.slice().sort((a, b) => {
    const dateA = new Date(a.created_at);
    const dateB = new Date(b.created_at);
    return dateB.getTime() - dateA.getTime();
  });
});

function formatDate(date:string){
    return dayjs(date).fromNow();
}

</script>

<template>
    <Head title="Home" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 space-y-4">
            <div class="p-4 bg-white dark:bg-black shadow rounded space-y-4" v-for="posts in sortedPosts" :key="posts.id">
                <div class="flex flex-wrap gap-2">
                     <h1 class="text-2xl font-bold font-cursor-pointer" @click="$inertia.visit(`/user/post/${posts.id}`)">{{ posts.user.name }}</h1>
                     <p v-if="posts.user.role === 'moderator'" class="px-1 py-1 bg-blue-600 rounded text-white dark:text-black ">Moderator</p>
                     <div class="ml-auto">
                        <DropdownMenu>
                        <DropdownMenuTrigger><EllipsisVertical></EllipsisVertical></DropdownMenuTrigger>
                        <DropdownMenuContent>
                        <DropdownMenuItem>Profile</DropdownMenuItem>
                        <DropdownMenuItem>Laporkan Postingan</DropdownMenuItem>
                        </DropdownMenuContent>
                         </DropdownMenu>
                     </div>
                      
                </div>
                <hr>
                <p>{{ posts.content }}</p>
                <img v-if="posts.image" :src="`/storage/${posts.image}`" alt="Post Image" class="w-80 h-80 object-contain" />
                <div class="flex flex-row gap-6">
                    <input type="hidden" name="like" id="like" />
                    <Label class="cursor-pointer">
                         <Heart for="like" />
                    </Label>
                     <Label class="cursor-pointer" @click="$inertia.visit(`/user/post/${posts.id}`)">
                        <MessageCircle />
                    </Label>

                     
                </div>
                <p class="text-gray-400">Dibuat {{ formatDate(posts.created_at) }}</p>
            </div>
        </div>
        
    </AppLayout>
</template>
