<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';




const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Notification',
        href: '/user/notification',
    },
];

const props = defineProps<{
    notification: Array<{
        id:number,
        message:string,
        post_id:number,
        created_at: string,
    }>
}>()

function splitName(text: string) {
  const parts = text.split(" commented");

  const name = parts[0].trim();

  const rest = "commented" + (parts[1] ?? "");

  return { name, rest };
}


const sortNotif = computed(()=>{
    return props.notification.sort((a, b) => {
        const dateA = new Date (a.created_at)
        const dateB = new Date (b.created_at)
        return dateB.getTime() - dateA.getTime();
    })
})

</script>

<template>
    <Head title="Notification" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 w-full">
            <div>
                <p class="font-bold text-xl">Notifikasi</p>
                <hr>
            </div>
            <div class="flex justify-center items-center h-64" v-if="notification.length === 0">
                <p>Kamu tidak punya Notifikasi apapun</p>

            </div>

            <div v-for="notif in sortNotif" class="p-2" :key="notif.id">
                <div class="cursor-pointer" @click="$inertia.visit(route('user.post.details',notif.post_id))">
                    <h1 class="font-bold">{{ splitName(notif.message).name }}</h1>
                    <p>{{ splitName(notif.message).rest }}</p>
                </div>
            </div>
        </div>
        
    </AppLayout>
</template>
