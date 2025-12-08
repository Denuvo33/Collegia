<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Users } from 'lucide-vue-next';
</script>

<template>
    <div class="flex min-h-screen w-full items-center justify-center bg-gradient-to-b from-white to-neutral-100 p-6">
        <Card class="w-full max-w-md rounded-3xl border border-neutral-200 bg-white/90 shadow-lg backdrop-blur-sm">
            <CardHeader class="space-y-2 py-8 text-center">
                <div class="mb-4 flex justify-center">
                    <Users class="h-12 w-12 text-neutral-800" />
                </div>
                <CardTitle class="text-2xl font-semibold tracking-tight text-neutral-900"> Masuk Sebagai Perwakilan Komunitas </CardTitle>
                <!-- <CardDescription class="text-sm text-neutral-600"> </CardDescription> -->
            </CardHeader>

            <CardContent class="space-y-5 pb-8">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-neutral-700">Email</label>
                    <Input
                        type="email"
                        placeholder="email@example"
                        autocomplete="email"
                        class="h-12 rounded-xl border-neutral-300 focus:border-neutral-700 focus:ring-0"
                    />
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-neutral-700">Password</label>
                    <Input type="password" placeholder="••••••••" class="h-12 rounded-xl border-neutral-300 focus:border-neutral-700 focus:ring-0" />
                </div>

                <Button class="h-12 w-full rounded-xl bg-neutral-900 text-white transition hover:bg-neutral-800"> Masuk sebagai Komunitas </Button>

                <p class="pt-2 text-center text-xs text-neutral-500">
                    Jika tidak punya akun komunitas,
                    <a href="mailto:neutralteamssupport@gmail.com" class="underline hover:text-neutral-700">hubungi kami via email</a>
                    untuk pembuatan akun.
                </p>

                <p class="pt-2 text-center text-xs text-neutral-500 italic">Dikembangkan oleh Neutral Teams © {{ new Date().getFullYear() }}</p>
            </CardContent>
        </Card>
    </div>
</template>
