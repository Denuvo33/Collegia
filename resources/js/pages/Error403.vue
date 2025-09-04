<template>
    <div class="flex min-h-screen items-center justify-center bg-gray-50">
        <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-10 text-center shadow-sm">
            <div class="mb-4 flex items-center justify-center gap-2 text-gray-900">
                <ShieldX class="h-8 w-8 text-red-500" />
                <h1 class="text-2xl font-bold tracking-tight">Akses Ditolak</h1>
            </div>

            <p class="mb-6 text-sm leading-relaxed text-gray-600">
                Login hanya diperbolehkan menggunakan email
                <span class="font-medium text-gray-900">@student.unri.ac.id</span>
            </p>

            <Button @click="$inertia.visit(route('login'))" class="inline-flex items-center gap-2">
                <LogIn class="h-4 w-4" />
                Kembali ke Login
            </Button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { LogIn, ShieldX } from 'lucide-vue-next';
</script>
