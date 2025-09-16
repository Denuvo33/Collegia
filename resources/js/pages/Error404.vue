<template>
    <div class="flex min-h-screen items-center justify-center bg-gray-50">
        <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-10 text-center shadow-sm">
            <div class="mb-4 flex items-center justify-center gap-2 text-gray-900">
                <SearchX class="h-8 w-8 text-gray-500" />
                <h1 class="text-2xl font-bold tracking-tight">404 - Tidak Ditemukan</h1>
            </div>

            <p class="mb-6 text-sm leading-relaxed text-gray-600">Halaman yang kamu cari tidak tersedia atau sudah dipindahkan.</p>

            <Button @click="$inertia.visit('/user/home')" class="inline-flex items-center gap-2">
                <Home class="h-4 w-4" />
                Kembali ke Beranda
            </Button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Home, SearchX } from 'lucide-vue-next';
</script>
