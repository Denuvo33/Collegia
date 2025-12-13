<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Calendar, FileText, Image, MapPin, Pen, Users, Wallet } from 'lucide-vue-next';

import { ref } from 'vue';
const preview = ref<string | null>(null);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Buat Event',
        href: '/user/create-event',
    },
];

const form = useForm({
    title: '',
    content: '',
    price: 0,
    location: '',
    organizer: '',
    date_event: '',
    status: 'upcoming',
    image: null as File | null,
    user_id: usePage().props.auth.user.id as number,
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
    form.post(route('user.event.store'), {
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
    <Head title="Buat Event" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <Card class="m-2">
            <CardHeader>
                <CardTitle>Buat Event Baru</CardTitle>
                <CardDescription>Isi detail acara agar mahasiswa lain dapat mengetahui dan mengikuti event ini.</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="space-y-2">
                    <div class="flex flex-row gap-2">
                        <Pen></Pen>
                        <Input v-model="form.title" placeholder="Nama Event" type="text" class="w-8/12"></Input>
                    </div>

                    <div v-if="form.errors.title" class="text-red-500">{{ form.errors.title }}</div>

                    <div class="flex flex-row gap-2">
                        <FileText></FileText>
                        <Textarea v-model="form.content" placeholder="Deskripsi" class="h-60 min-h-8/12 w-8/12" />
                    </div>

                    <div v-if="form.errors.content" class="text-red-500">{{ form.errors.content }}</div>

                    <div class="flex flex-row gap-2">
                        <MapPin></MapPin>
                        <Input v-model="form.location" placeholder="Lokasi" class="w-8/12" />
                    </div>

                    <div v-if="form.errors.location" class="text-red-500">{{ form.errors.location }}</div>

                    <div class="flex flex-row gap-2">
                        <Users />
                        <Input v-model="form.organizer" placeholder="Organizer" class="w-8/12" />
                    </div>

                    <div v-if="form.errors.organizer" class="text-red-500">{{ form.errors.organizer }}</div>

                    <div class="flex w-8/12 flex-col gap-1">
                        <div class="flex flex-row items-center gap-2">
                            <Wallet class="text-gray-500" />
                            <div class="relative w-full">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">Rp.</span>
                                <Input v-model="form.price" placeholder="Harga" type="number" min="0" class="w-full pl-12" />
                            </div>
                        </div>
                        <small class="ml-8 text-sm text-gray-500">(Isi 0 jika gratis)</small>
                    </div>

                    <div class="flex flex-col gap-2">
                        <small class="px-8 text-gray-500">(Tanggal Event)</small>
                        <div class="flex flex-row gap-2">
                            <Calendar></Calendar>
                            <Input v-model="form.date_event" placeholder="Tanggal Event" type="date" class="w-4/12" />
                        </div>
                    </div>
                    <div v-if="form.errors.date_event" class="text-red-500">{{ form.errors.date_event }}</div>

                    <Input accept="image/jpeg, image/png,image/jpg" type="file" class="mt-4 hidden" id="uploadImage" @change="handleFile"></Input>
                    <div v-if="preview">
                        <img :src="preview" alt="gambar post" class="mt-5 h-80 w-80 rounded object-contain" />
                        <button type="button" @click="preview = null">Remove</button>
                    </div>

                    <Label for="uploadImage" class="inline-flex w-fit cursor-pointer">
                        <Image class="mt-5 h-10 w-10"></Image>
                        <div v-if="form.errors.image" class="text-red-500">{{ form.errors.image }}</div>
                    </Label>
                    <div>
                        <Button class="mt-5" @click="submit">Buat Event</Button>
                    </div>
                </div>
            </CardContent>
            <CardFooter> </CardFooter>
        </Card>
    </AppLayout>
</template>
