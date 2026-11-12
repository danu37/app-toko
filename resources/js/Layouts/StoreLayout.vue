<script setup>
import { nextTick, onUnmounted, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import BaseDropdown from '@/Components/UI/BaseDropdown.vue';
import AppFooter from '@/Layouts/Components/AppFooter.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { usePageEntrance } from '@/Composables/usePageEntrance';

defineProps({
    branches: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
});

const { user } = usePermissions();
const { animateChrome } = usePageEntrance('store');
const mobileMenuOpen = ref(false);
const searchOpen = ref(false);
const searchInputRef = ref(null);
const searchTerm = ref('');
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

const toggleSearch = async () => {
    searchOpen.value = !searchOpen.value;
    if (searchOpen.value) {
        await nextTick();
        searchInputRef.value?.focus();
    }
};

const submitSearch = () => {
    router.get(route('store.shop'), searchTerm.value ? { search: searchTerm.value } : {}, {
        preserveState: true,
    });
    mobileMenuOpen.value = false;
    searchOpen.value = false;
};
</script>

<template>
    <div class="flex min-h-screen flex-col bg-white text-neutral-900">
        <div
            v-if="navigating"
            class="fixed inset-x-0 top-0 z-50 h-0.5 animate-pulse bg-neutral-900"
            aria-hidden="true"
        />

        <header
            class="sticky top-0 z-40 border-b border-neutral-200 bg-white/95 backdrop-blur-sm"
            :class="{ 'animate-enter-chrome': animateChrome }"
        >
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <!-- Left: Logo -->
                <Link :href="route('home')" class="flex shrink-0 items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-neutral-900 text-xs font-bold tracking-wider text-white">
                        TK
                    </span>
                    <span class="text-base font-semibold tracking-tight text-neutral-900">TokoKita</span>
                </Link>

                <!-- Center: Primary Navigation -->
                <nav class="hidden items-center gap-7 md:flex" aria-label="Navigasi utama storefront">
                    <Link
                        :href="route('home')"
                        class="text-sm font-medium transition"
                        :class="route().current('home') ? 'text-neutral-900' : 'text-neutral-500 hover:text-neutral-900'"
                    >
                        Home
                    </Link>

                    <Link
                        :href="route('store.shop')"
                        class="text-sm font-medium transition"
                        :class="route().current('store.shop') || route().current('store.product') ? 'text-neutral-900' : 'text-neutral-500 hover:text-neutral-900'"
                    >
                        Shop
                    </Link>

                    <BaseDropdown v-if="categories.length > 0" align="left" width="56">
                        <template #trigger="{ open }">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 text-sm font-medium text-neutral-500 transition hover:text-neutral-900 focus:outline-none"
                                :aria-expanded="open"
                            >
                                <span>Kategori</span>
                                <svg
                                    class="h-3.5 w-3.5 transition-transform duration-150"
                                    :class="{ 'rotate-180': open }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </template>

                        <template #content>
                            <div class="border-b border-neutral-100 px-4 py-2">
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-neutral-400">Kategori Produk</p>
                            </div>
                            <Link
                                v-for="cat in categories"
                                :key="cat.id_kategori"
                                :href="route('store.shop', { kategori: cat.slug })"
                                class="block px-4 py-2 text-sm text-neutral-700 transition hover:bg-neutral-50 hover:text-neutral-900"
                            >
                                {{ cat.nama_kategori }}
                            </Link>
                            <Link
                                :href="route('store.shop')"
                                class="block border-t border-neutral-100 px-4 py-2 text-xs font-semibold text-neutral-900 transition hover:bg-neutral-50"
                            >
                                Semua Kategori &rarr;
                            </Link>
                        </template>
                    </BaseDropdown>
                    <Link
                        v-else
                        :href="`${route('home')}#kategori`"
                        class="text-sm font-medium text-neutral-500 transition hover:text-neutral-900"
                    >
                        Kategori
                    </Link>

                    <Link
                        :href="`${route('home')}#tentang-kami`"
                        class="text-sm font-medium text-neutral-500 transition hover:text-neutral-900"
                    >
                        Tentang Kami
                    </Link>

                    <a
                        href="#kontak"
                        class="text-sm font-medium text-neutral-500 transition hover:text-neutral-900"
                    >
                        Kontak
                    </a>
                </nav>

                <!-- Right: Search Icon & Login -->
                <div class="hidden items-center gap-2.5 md:flex">
                    <button
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-neutral-200 bg-white text-neutral-600 transition hover:border-neutral-300 hover:bg-neutral-50 hover:text-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-900"
                        :aria-expanded="searchOpen"
                        aria-label="Cari produk"
                        @click="toggleSearch"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>

                    <Link
                        v-if="user"
                        :href="route('dashboard')"
                        class="inline-flex items-center rounded-full bg-neutral-900 px-4 py-2 text-xs font-semibold text-white transition hover:bg-neutral-800"
                    >
                        Dashboard
                    </Link>
                    <Link
                        v-else
                        :href="route('login')"
                        class="inline-flex items-center rounded-full border border-neutral-900 bg-neutral-900 px-5 py-2 text-xs font-semibold text-white transition hover:bg-neutral-800"
                    >
                        Login
                    </Link>
                </div>

                <!-- Mobile actions -->
                <div class="flex items-center gap-2 md:hidden">
                    <button
                        type="button"
                        class="rounded-lg border border-neutral-200 p-2 text-neutral-700"
                        aria-label="Cari produk"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border border-neutral-200 p-2 text-neutral-700"
                        :aria-expanded="mobileMenuOpen"
                        aria-label="Buka menu toko"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                v-if="!mobileMenuOpen"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                            <path
                                v-else
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Expandable Desktop Search Bar -->
            <div v-if="searchOpen" class="hidden border-t border-neutral-100 bg-stone-50/90 md:block">
                <form class="mx-auto flex max-w-3xl items-center gap-2 px-4 py-3 sm:px-6" @submit.prevent="submitSearch">
                    <input
                        ref="searchInputRef"
                        v-model="searchTerm"
                        type="search"
                        placeholder="Ketik nama produk atau kode barang..."
                        class="block w-full rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-900 placeholder:text-neutral-400 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900"
                        @keydown.esc="searchOpen = false"
                    />
                    <button
                        type="submit"
                        class="shrink-0 rounded-lg bg-neutral-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-neutral-800"
                    >
                        Cari
                    </button>
                    <button
                        type="button"
                        class="shrink-0 rounded-lg border border-neutral-200 bg-white px-3 py-2 text-sm text-neutral-500 transition hover:text-neutral-900"
                        aria-label="Tutup pencarian"
                        @click="searchOpen = false"
                    >
                        Tutup
                    </button>
                </form>
            </div>

            <!-- Mobile Drawer Menu -->
            <div v-if="mobileMenuOpen" class="border-t border-neutral-200 bg-white px-4 py-4 md:hidden">
                <form class="mb-4 flex gap-2" @submit.prevent="submitSearch">
                    <input
                        v-model="searchTerm"
                        type="search"
                        placeholder="Cari produk..."
                        class="block w-full rounded-lg border border-neutral-300 bg-neutral-50 px-3 py-2 text-sm text-neutral-900 focus:border-neutral-900 focus:outline-none"
                    />
                    <button
                        type="submit"
                        class="shrink-0 rounded-lg bg-neutral-900 px-3.5 py-2 text-xs font-semibold text-white"
                    >
                        Cari
                    </button>
                </form>

                <div class="flex flex-col space-y-1">
                    <Link :href="route('home')" class="rounded-lg px-3 py-2 text-sm font-medium text-neutral-800 hover:bg-neutral-50" @click="mobileMenuOpen = false">
                        Home
                    </Link>
                    <Link :href="route('store.shop')" class="rounded-lg px-3 py-2 text-sm font-medium text-neutral-800 hover:bg-neutral-50" @click="mobileMenuOpen = false">
                        Shop
                    </Link>
                    <Link :href="`${route('home')}#kategori`" class="rounded-lg px-3 py-2 text-sm font-medium text-neutral-800 hover:bg-neutral-50" @click="mobileMenuOpen = false">
                        Kategori
                    </Link>
                    <Link :href="`${route('home')}#tentang-kami`" class="rounded-lg px-3 py-2 text-sm font-medium text-neutral-800 hover:bg-neutral-50" @click="mobileMenuOpen = false">
                        Tentang Kami
                    </Link>
                    <a href="#kontak" class="rounded-lg px-3 py-2 text-sm font-medium text-neutral-800 hover:bg-neutral-50" @click="mobileMenuOpen = false">
                        Kontak
                    </a>
                </div>

                <div v-if="categories.length > 0" class="mt-3 flex flex-wrap gap-1.5 border-t border-neutral-100 pt-3">
                    <Link
                        v-for="cat in categories"
                        :key="cat.id_kategori"
                        :href="route('store.shop', { kategori: cat.slug })"
                        class="rounded-full border border-neutral-200 bg-neutral-50 px-3 py-1 text-xs font-medium text-neutral-700"
                        @click="mobileMenuOpen = false"
                    >
                        {{ cat.nama_kategori }}
                    </Link>
                </div>

                <div class="mt-4 border-t border-neutral-100 pt-4">
                    <Link
                        :href="user ? route('dashboard') : route('login')"
                        class="block w-full rounded-lg bg-neutral-900 px-4 py-2.5 text-center text-sm font-semibold text-white"
                        @click="mobileMenuOpen = false"
                    >
                        {{ user ? 'Buka Dashboard' : 'Login' }}
                    </Link>
                </div>
            </div>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <AppFooter :branches="branches" />
    </div>
</template>
