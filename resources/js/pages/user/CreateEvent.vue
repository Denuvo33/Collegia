<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { FileText, Image, Info, MapPin, Pen, Users, Wallet } from 'lucide-vue-next';

import { ref } from 'vue';
const preview = ref<string | null>(null);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Create Post',
        href: '/user/create-event',
    },
];

const form = useForm({
    content: '',
    image: null as File | null,
    user_id: 0,
});

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

function submit() {
    form.post(route('user.post.store'), {
        onSuccess: () => {
            form.reset();
            preview.value = null;
        },
        onError: (errors) => {
            console.error('Failed to create post', errors);
        },
    });
}
</script>

<template>
    <Head title="Create Post" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-2 p-4">
            <div class="flex flex-row gap-2">
                <Pen></Pen>
                <Input placeholder="Nama Event" v-model="form.content" type="text" class="w-8/12"></Input>
            </div>

            <div class="flex h-8/12 flex-row gap-2">
                <FileText></FileText>
                <Textarea placeholder="Deskripsi" class="w-8/12" />
            </div>

            <div class="flex flex-row gap-2">
                <MapPin></MapPin>
                <Input placeholder="Lokasi" class="w-8/12" />
            </div>

            <div class="flex flex-row gap-2">
                <Users />
                <Input placeholder="Organizer" class="w-8/12" />
            </div>

            <div class="flex w-8/12 flex-col gap-1">
                <div class="flex flex-row items-center gap-2">
                    <Wallet class="text-gray-500" />
                    <div class="relative w-full">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">Rp.</span>
                        <Input placeholder="Harga" type="number" min="0" class="w-full pl-12" />
                    </div>
                </div>
                <small class="ml-8 text-sm text-gray-500">(Isi 0 jika gratis)</small>
            </div>

            <div class="flex flex-row gap-2">
                <Info />
                <Select>
                    <SelectTrigger>
                        <SelectValue placeholder="Status Event" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectGroup>
                            <SelectItem value="upcoming"> Upcoming </SelectItem>
                            <SelectItem value="ongoing"> Ongoing </SelectItem>
                            <SelectItem value="finished"> Finished </SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>
            </div>

            <div v-if="form.errors.content" class="text-red-500">{{ form.errors.content }}</div>

            <Input accept="image/jpeg, image/png,image/jpg" type="file" class="mt-4 hidden w-fit" id="uploadImage" @change="handleFile"></Input>
            <div v-if="preview">
                <img :src="preview" alt="gambar post" class="mt-5 h-80 w-80 rounded object-contain" />
                <button type="button" @click="preview = null">Remove</button>
            </div>

            <div></div>

            <Label for="uploadImage" class="cursor-pointer">
                <Image class="mt-5 h-10 w-10"></Image>
                <div v-if="form.errors.image" class="text-red-500">{{ form.errors.image }}</div>
            </Label>
            <Button class="mt-5" @click="submit">Post Cerita</Button>
        </div>
    </AppLayout>
</template>
