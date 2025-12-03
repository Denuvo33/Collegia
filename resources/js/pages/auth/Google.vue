<script setup lang="ts">
function loginWithGoogle() {
    window.location.href = '/auth/google';
}

import { Podcast, ShieldCheck } from 'lucide-vue-next';
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-gray-50">
        <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-10 text-center shadow-sm">
            <div class="mb-6 flex items-center justify-center gap-2 text-gray-900">
                <Podcast class="h-6 w-6" />
                <h1 class="text-2xl font-bold tracking-tight">Dev Rat</h1>
            </div>

            <p class="mb-8 text-sm leading-relaxed text-gray-600">
                Platform mahasiswa untuk berbagi informasi, membuat event kampus, dan membangun koneksi akademik secara positif.
            </p>

            <button
                @click="loginWithGoogle"
                class="flex w-full items-center justify-center gap-3 rounded-lg bg-gray-900 px-6 py-3 text-sm font-medium text-white transition hover:bg-gray-800"
            >
                <img src="https://www.svgrepo.com/show/355037/google.svg" alt="Google" class="h-5 w-5" />
                Masuk dengan Google
            </button>

            <div class="mt-8 flex items-center justify-center gap-2 text-xs text-gray-500">
                <ShieldCheck class="h-4 w-4" />
                <span>Komunitas aman dan eksklusif untuk mahasiswa</span>
            </div>
            <div>
                <p class="mt-4 text-xs text-gray-400">© 2025 Dev Rat. Dikembangkan oleh Neutral Teams</p>
            </div>
        </div>
    </div>
</template>
