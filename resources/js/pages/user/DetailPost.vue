<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Input } from "@/components/ui/input"
import { Button } from "@/components/ui/button"
import { EllipsisVertical } from 'lucide-vue-next';
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



const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Home',
        href: '/user/home',
    },
    {
        title: 'Detail Post',
        href: '/user/post/details',
    }
];

const props =defineProps<{
    data: {
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
        comments:Array<{
            id:number,
            content:string,
            user_id:number,
            post_id:number
            user: {
                id:number,
                name:string
            }
        }>
    }
}>()

const form = useForm({
    'post_id': props.data.id,
    'user_id': usePage().props.auth.user.id,
    'content': '',
})

function submit(){
    form.post(route('post.store.comment'),{
        onError: (Errors)=>{
            console.log('erros',Errors)
        },onSuccess: (Success)=>{
            console.log('succes',Success)
            form.content = '';
        }
    })
}

</script>

<template>
    <Head title="Detail Post" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="">
            <div class="p-4 bg-black rounded space-y-4">
                <h1 class="text-2xl font-bold text-white">{{ data.user.name }}</h1>
                <hr>
                <p>{{ data.content }}</p>
                <img v-if="data.image" :src="`/storage/${data.image}`" alt="Post Image" class="w-80 h-80 object-contain" />
                <p class="text-gray-400">Dibuat pada {{ data.created_at }}</p>
            </div>
            <div class="p-4 ">
                <h2 class="text-xl font-bold text-white mt-4">Komentar</h2>
                <div v-if="data.comments.length > 0" class="space-y-4 p-4 max-h-96 overflow-y-auto">
                    <div class=" space-y-1 p-2" v-for="comment in data.comments" :key="comment.id">
                        <div class="flex flex-row">
                            <div clas="flex flex-col">
                            <p class="font-bold">{{ comment.user.name }}</p>
                        <p>{{ comment.content }}</p>
                            </div>
                            <div class="">
                                <DropdownMenu>
                                  <DropdownMenuTrigger><EllipsisVertical class="cursor-pointer"></EllipsisVertical> </DropdownMenuTrigger>
                                  <DropdownMenuContent>
                                    <DropdownMenuItem>Laporkan</DropdownMenuItem>
                                    <DropdownMenuItem v-if="comment.user.id === usePage().props.auth.user.id">Hapus</DropdownMenuItem>
                                  </DropdownMenuContent>
                                </DropdownMenu>
                                
                            </div>
                        </div>
                      
                    </div>
                </div>
                <Input class="w-8/12" v-model="form.content" placeholder="Kirim Komentar" />
                <div v-if="form.errors.content" class="text-red-500"> {{ form.errors.content }}</div>
            </div>
            <div class="p-4">
                 <Button @click="submit()">Kirim</Button>
            </div>
           

        </div>
       
    </AppLayout>
</template>
