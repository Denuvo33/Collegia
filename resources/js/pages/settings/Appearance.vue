<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { type BreadcrumbItem } from '@/types';

import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { ref } from 'vue';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Appearance settings',
        href: '/settings/appearance',
    },
];

const props = defineProps<{
    user: {
        id: number;
        private_post: number;
        private_comment: number;
    };
}>();

const postPrivate = ref(!!props.user.private_post); // convert int -> boolean
const commentPrivate = ref(!!props.user.private_comment);

const updatePrivacy = (field: 'private_post' | 'private_comment', value: boolean) => {
    router.put(
        route('user.privacy.update', props.user.id),
        {
            [field]: value ? 1 : 0,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                console.log(`${field} updated to`, value);
            },
            onError: (errors) => {
                console.error(errors);
            },
        },
    );
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Appearance settings" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall title="Tema" description="Update Tema anda" />
                <AppearanceTabs />
            </div>
            <div class="mt-10 flex flex-col">
                <h1 class="text-xl font-bold">Privasi profile anda</h1>
                <p class="text-gray-500">
                    Postingan dan komentar akan tersembunyi hanya ketika user melihat dari profile anda, tetapi masih dapat dilihat dari beranda.
                </p>
                <p></p>
                <div class="mt-4 flex items-center gap-3">
                    <Switch v-model="postPrivate" id="postprivate" @update:modelValue="(val: boolean) => updatePrivacy('private_post', val)" />
                    <Label for="postprivate">Private my Post</Label>
                </div>
                <div class="mt-4 flex items-center gap-3">
                    <Switch
                        v-model="commentPrivate"
                        id="commentprivate"
                        @update:modelValue="(val: boolean) => updatePrivacy('private_comment', val)"
                    />
                    <Label for="commentprivate">Private my Comment</Label>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
