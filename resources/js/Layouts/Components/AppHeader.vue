<script setup>
import { Link } from '@inertiajs/vue3';
import BaseBadge from '@/Components/UI/BaseBadge.vue';
import BaseDropdown from '@/Components/UI/BaseDropdown.vue';
import { usePermissions } from '@/Composables/usePermissions';

defineProps({
    title: {
        type: String,
        default: '',
    },
    animateChrome: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['toggleSidebar']);
const { user, isAdmin } = usePermissions();
</script>

<template>
    <header
        class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-neutral-200 bg-white/95 px-4 backdrop-blur print:hidden sm:px-6"
        :class="{ 'animate-enter-chrome': animateChrome }"
    >
        <div class="flex min-w-0 items-center gap-3">
            <button
                type="button"
                class="rounded-lg border border-neutral-200 p-2 text-neutral-600 transition hover:bg-neutral-50 lg:hidden"
                aria-label="Buka navigasi"
                @click="emit('toggleSidebar')"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <div class="min-w-0">
                <slot name="title">
                    <h1 v-if="title" class="truncate text-base font-semibold text-neutral-900 sm:text-lg">
                        {{ title }}
                    </h1>
                </slot>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-3">
            <BaseBadge :variant="isAdmin ? 'dark' : 'success'" dot class="hidden sm:inline-flex">
                Role: {{ isAdmin ? 'Admin' : 'Kasir' }}
            </BaseBadge>

            <BaseDropdown v-if="user" align="right" width="48">
                <template #trigger>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-neutral-200 bg-white px-3 py-1.5 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-neutral-900"
                    >
                        <span class="hidden max-w-[140px] truncate sm:inline">{{ user.name }}</span>
                        <span class="sm:hidden">{{ user.name?.charAt(0)?.toUpperCase() }}</span>
                        <svg class="h-4 w-4 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </template>

                <template #content>
                    <div class="border-b border-neutral-100 px-4 py-2.5">
                        <p class="truncate text-xs font-semibold text-neutral-900">{{ user.name }}</p>
                        <p class="truncate text-xs text-neutral-500">{{ user.email }}</p>
                    </div>
                    <Link
                        :href="route('profile.edit')"
                        class="block w-full px-4 py-2 text-left text-sm text-neutral-700 transition hover:bg-neutral-50 hover:text-neutral-900"
                    >
                        Profil Akun
                    </Link>
                    <Link
                        :href="route('home')"
                        class="block w-full px-4 py-2 text-left text-sm text-neutral-700 transition hover:bg-neutral-50 hover:text-neutral-900"
                    >
                        Storefront
                    </Link>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="block w-full border-t border-neutral-100 px-4 py-2 text-left text-sm text-red-600 transition hover:bg-red-50"
                    >
                        Keluar (Logout)
                    </Link>
                </template>
            </BaseDropdown>
        </div>
    </header>
</template>
