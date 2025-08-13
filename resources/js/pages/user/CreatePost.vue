<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Input } from "@/components/ui/input"
import { Image } from "lucide-vue-next";
import { ref } from 'vue';
import { Button } from "@/components/ui/button"


const preview = ref<string | null>(null);


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Create Post',
        href: '/user/create-post',
    },
];

    const props = defineProps<{
        data: {
            id: number;
            content: string;
            image: string | File | null;
            created_at: string;
        },
        user: {
            id: number;
            name: string;
            email: string;
            profile_photo_path?: string;
        }
    }>();
    


    const form = useForm({
        content: '',
        image: null as File | null,
        user_id:  props.user.id,
    })

function handleFile(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        form.image = target.files[0];
        preview.value = URL.createObjectURL(form.image);
    } else {
        preview.value = null;
        form.image = null;
    }
}

function submit(){
    form.post(route('user.post.store'), {
        onSuccess: () => {
            form.reset();
            preview.value = null;
        },
        onError: (errors) => {
            console.error("Failed to create post", errors);
        }
    });
}
</script>

<template>
    <Head title="Create Post" />

    <AppLayout :breadcrumbs="breadcrumbs">
       
            <div class="p-4">
                <Input placeholder="Apa yg sedang kamu pikirkan?" v-model="form.content" type="text" class="w-8/12"></Input>
                <div v-if="form.errors.content" class="text-red-500">{{ form.errors.content }}</div>
                <Input accept="image/jpeg, image/png,image/jpg" type="file" class="w-fit mt-4 hidden" id="uploadImage" @change="handleFile"></Input>
                <div v-if="preview">
                    <img :src="preview" alt="gambar post" class="w-80 h-80 mt-5 object-contain rounded"  />
                    <button type="button" @click="preview = null">Remove</button>
                </div>
                
                <Label for="uploadImage" class="cursor-pointer">
                    <Image class="w-10 h-10 mt-5"></Image>
                    <div v-if="form.errors.image" class="text-red-500">{{ form.errors.image }}</div>
                </Label>
                <Button class="mt-5" @click="submit">Post Cerita</Button>

            </div>

      
        
    </AppLayout>
</template>
