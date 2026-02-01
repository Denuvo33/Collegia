<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import MobileBottomNav from '@/components/MobileBottomNav.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <!-- Add padding bottom for mobile to account for bottom nav -->
            <div class="pb-20 md:pb-0">
                <slot />
            </div>
        </AppContent>

        <!-- Mobile Bottom Navigation -->
        <MobileBottomNav />
    </AppShell>
</template>
