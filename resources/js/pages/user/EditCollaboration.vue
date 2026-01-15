<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Calendar, Image, MapPin, Users } from 'lucide-vue-next';
import { ref } from 'vue';

type Collaboration = {
    id: number;
    user_id: number;
    title: string;
    description: string;
    type: 'collaboration' | 'volunteer';
    start_date: string | null;
    end_date: string | null;
    image: string | null;
    location: string | null;
    participants_needed: number | null;
    hash_id: string;
};

const props = defineProps<{
    collaboration: Collaboration;
    user: {
        id: number;
        name: string;
        email: string;
    };
}>();

const preview = ref<string | null>(props.collaboration.image ? `/storage/${props.collaboration.image}` : null);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Home', href: '/user/home' },
    { title: 'Edit Kolaborasi', href: `/user/collaboration/${props.collaboration.hash_id}/edit` },
];

const form = useForm({
    title: props.collaboration.title,
    description: props.collaboration.description,
    type: props.collaboration.type,
    start_date: props.collaboration.start_date || '',
    end_date: props.collaboration.end_date || '',
    location: props.collaboration.location || '',
    participants_needed: props.collaboration.participants_needed,
    image: null as File | null,
    _method: 'PUT',
});

function handleFile(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        form.image = target.files[0];
        preview.value = URL.createObjectURL(form.image);
    } else {
        preview.value = props.collaboration.image ? `/storage/${props.collaboration.image}` : null;
        form.image = null;
    }
}

function submit() {
    form.post(route('user.collaboration.update', props.collaboration.hash_id), {
        onSuccess: () => {},
        onError: (errors) => {
            console.error('Failed to update collaboration', errors);
        },
    });
}
</script>

<template>
    <Head title="Edit Kolaborasi" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-4xl p-8">
            <div class="space-y-6 rounded-lg bg-white p-6 shadow-md dark:bg-gray-800">
                <div>
                    <h1 class="mb-2 text-2xl font-bold">Edit Kolaborasi / Relawan</h1>
                    <p class="text-gray-600 dark:text-gray-400">Perbarui informasi kolaborasi Anda</p>
                </div>

                <!-- Type Selection -->
                <div class="space-y-2">
                    <Label class="text-base font-semibold">Tipe</Label>
                    <RadioGroup v-model="form.type" class="flex gap-4">
                        <div class="flex items-center space-x-2">
                            <RadioGroupItem id="collaboration" value="collaboration" />
                            <Label for="collaboration" class="cursor-pointer">Kolaborasi</Label>
                        </div>
                        <div class="flex items-center space-x-2">
                            <RadioGroupItem id="volunteer" value="volunteer" />
                            <Label for="volunteer" class="cursor-pointer">Relawan</Label>
                        </div>
                    </RadioGroup>
                    <div v-if="form.errors.type" class="text-sm text-red-500">{{ form.errors.type }}</div>
                </div>

                <!-- Title -->
                <div class="space-y-2">
                    <Label for="title" class="text-base font-semibold">Judul *</Label>
                    <Input id="title" v-model="form.title" type="text" placeholder="Cari Developer untuk Startup Project" class="w-full" />
                    <div v-if="form.errors.title" class="text-sm text-red-500">{{ form.errors.title }}</div>
                </div>

                <!-- Description -->
                <div class="space-y-2">
                    <Label for="description" class="text-base font-semibold">Deskripsi *</Label>
                    <Textarea
                        id="description"
                        v-model="form.description"
                        placeholder="Jelaskan detail kolaborasi atau tugas relawan yang dibutuhkan..."
                        class="min-h-32 w-full"
                    />
                    <div v-if="form.errors.description" class="text-sm text-red-500">{{ form.errors.description }}</div>
                </div>

                <!-- Dates -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="start_date" class="flex items-center gap-2 text-base font-semibold">
                            <Calendar class="h-4 w-4" />
                            Tanggal Mulai (Opsional)
                        </Label>
                        <Input id="start_date" v-model="form.start_date" type="datetime-local" class="w-full" />
                        <div v-if="form.errors.start_date" class="text-sm text-red-500">{{ form.errors.start_date }}</div>
                    </div>

                    <div class="space-y-2">
                        <Label for="end_date" class="flex items-center gap-2 text-base font-semibold">
                            <Calendar class="h-4 w-4" />
                            Tanggal Berakhir (Opsional)
                        </Label>
                        <Input id="end_date" v-model="form.end_date" type="datetime-local" class="w-full" />
                        <div v-if="form.errors.end_date" class="text-sm text-red-500">{{ form.errors.end_date }}</div>
                    </div>
                </div>

                <!-- Location -->
                <div class="space-y-2">
                    <Label for="location" class="flex items-center gap-2 text-base font-semibold">
                        <MapPin class="h-4 w-4" />
                        Lokasi (Opsional)
                    </Label>
                    <Input id="location" v-model="form.location" type="text" placeholder="Pekanbaru / Online / Hybrid" class="w-full" />
                    <div v-if="form.errors.location" class="text-sm text-red-500">{{ form.errors.location }}</div>
                </div>

                <!-- Participants Needed -->
                <div class="space-y-2">
                    <Label for="participants" class="flex items-center gap-2 text-base font-semibold">
                        <Users class="h-4 w-4" />
                        Jumlah Orang yang Dibutuhkan (Opsional)
                    </Label>
                    <Input id="participants" v-model.number="form.participants_needed" type="number" min="1" placeholder="Contoh: 5" class="w-full" />
                    <div v-if="form.errors.participants_needed" class="text-sm text-red-500">{{ form.errors.participants_needed }}</div>
                </div>

                <!-- Image Upload -->
                <div class="space-y-2">
                    <Label class="flex items-center gap-2 text-base font-semibold">
                        <Image class="h-4 w-4" />
                        Gambar (Opsional)
                    </Label>
                    <Input accept="image/jpeg, image/png, image/jpg" type="file" id="uploadImage" @change="handleFile" class="w-full" />
                    <div v-if="form.errors.image" class="text-sm text-red-500">{{ form.errors.image }}</div>

                    <div v-if="preview" class="mt-4">
                        <img :src="preview" alt="Preview" class="max-h-64 rounded-lg object-cover" />
                        <Button
                            type="button"
                            variant="outline"
                            @click="
                                () => {
                                    preview = null;
                                    form.image = null;
                                }
                            "
                            class="mt-2"
                        >
                            Hapus Gambar
                        </Button>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex gap-3 pt-4">
                    <Button @click="submit" :disabled="form.processing" class="w-full md:w-auto">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        @click="$inertia.visit(route('user.collaboration.details', collaboration.hash_id))"
                        class="w-full md:w-auto"
                    >
                        Batal
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
