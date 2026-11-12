<script setup>
import { onUnmounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppSidebar from '@/Layouts/Components/AppSidebar.vue';
import AppHeader from '@/Layouts/Components/AppHeader.vue';
import AppFooter from '@/Layouts/Components/AppFooter.vue';
import ToastNotification from '@/Components/Feedback/ToastNotification.vue';
import { usePageEntrance } from '@/Composables/usePageEntrance';

defineProps({
    title: {
        type: String,
        default: '',
    },
});

const { animateChrome } = usePageEntrance('internal');
const mobileOpen = ref(false);
const navigating = ref(false);

const removeStart = router.on('start', () => {
    navigating.value = true;
});
const removeFinish = router.on('finish', () => {
    navigating.value = false;
});

onUnmounted(() => {
    removeStart();
    removeFinish();
});
</script>

<template>
    <div class="flex min-h-screen bg-neutral-50 text-neutral-900 print:block print:min-h-0 print:bg-white">
        <div
            v-if="navigating"
            class="fixed inset-x-0 top-0 z-50 h-0.5 animate-pulse bg-neutral-900 print:hidden"
            aria-hidden="true"
        />

        <AppSidebar :mobile-open="mobileOpen" :animate-chrome="animateChrome" @close="mobileOpen = false" />

        <div class="flex min-w-0 flex-1 flex-col">
            <AppHeader :title="title" :animate-chrome="animateChrome" @toggle-sidebar="mobileOpen = !mobileOpen">
                <template v-if="$slots.header" #title>
                    <slot name="header" />
                </template>
            </AppHeader>

            <main class="flex-1 px-4 py-6 print:p-0 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl print:max-w-none">
                    <ToastNotification />
                    <div class="tk-page-stage">
                        <slot />
                    </div>
                </div>
            </main>

            <AppFooter minimal />
        </div>
    </div>
</template>
