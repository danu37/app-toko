<script setup>
import { Link } from '@inertiajs/vue3';
import { usePermissions } from '@/Composables/usePermissions';

defineProps({
    branches: {
        type: Array,
        default: () => [],
    },
    minimal: {
        type: Boolean,
        default: false,
    },
});

const { user } = usePermissions();
</script>

<template>
    <footer
        v-if="minimal"
        class="border-t border-neutral-200 bg-white px-6 py-4 text-xs text-neutral-500 print:hidden"
    >
        <div class="flex flex-col items-center justify-between gap-2 sm:flex-row">
            <span>&copy; {{ new Date().getFullYear() }} App Toko — Sistem Operasional &amp; POS</span>
            <span>Role &amp; Akses Terproteksi</span>
        </div>
    </footer>

    <footer id="kontak" v-else class="border-t border-neutral-200 bg-stone-50 text-neutral-600">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-10 md:grid-cols-12">
                <!-- Informasi Toko -->
                <div class="md:col-span-5">
                    <Link :href="route('home')" class="inline-flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-neutral-900 text-xs font-bold tracking-wider text-white">
                            TK
                        </span>
                        <span class="text-base font-semibold tracking-tight text-neutral-900">TokoKita</span>
                    </Link>
                    <p class="mt-4 max-w-sm text-sm leading-relaxed text-neutral-500">
                        Kebutuhan harian dan rumah tangga pilihan dengan kualitas terjaga, stok terpantau, serta harga transparan di seluruh jaringan gerai kami.
                    </p>
                    <div class="mt-5 space-y-1.5 text-xs text-neutral-500">
                        <p><span class="font-medium text-neutral-800">Jam Layanan:</span> Setiap Hari, 08.00 &ndash; 21.00 WIB</p>
                        <p><span class="font-medium text-neutral-800">Email Layanan:</span> halo@tokokita.local</p>
                    </div>
                </div>

                <!-- Navigasi -->
                <div class="md:col-span-3">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-neutral-900">Navigasi</h3>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        <li>
                            <Link :href="route('home')" class="transition hover:text-neutral-900">Home</Link>
                        </li>
                        <li>
                            <Link :href="route('store.shop')" class="transition hover:text-neutral-900">Shop</Link>
                        </li>
                        <li>
                            <Link :href="`${route('home')}#kategori`" class="transition hover:text-neutral-900">Kategori</Link>
                        </li>
                        <li>
                            <Link :href="`${route('home')}#tentang-kami`" class="transition hover:text-neutral-900">Tentang Kami</Link>
                        </li>
                        <li>
                            <Link :href="user ? route('dashboard') : route('login')" class="transition hover:text-neutral-900">
                                {{ user ? 'Dashboard Staff' : 'Login Staff' }}
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- Kontak & Lokasi Cabang -->
                <div class="md:col-span-4">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-neutral-900">Kontak &amp; Gerai Cabang</h3>
                    <ul v-if="branches.length > 0" class="mt-4 divide-y divide-neutral-200/70 rounded-xl border border-neutral-200 bg-white">
                        <li
                            v-for="branch in branches"
                            :key="branch.id_cabang"
                            class="flex items-center justify-between px-4 py-2.5 text-sm"
                        >
                            <span class="font-medium text-neutral-900">{{ branch.nama_cabang }}</span>
                            <span class="text-xs text-neutral-500">{{ branch.kota }}</span>
                        </li>
                    </ul>
                    <p v-else class="mt-4 text-sm text-neutral-500">
                        Hubungi petugas kasir di gerai TokoKita terdekat untuk informasi ketersediaan barang.
                    </p>
                </div>
            </div>

            <div class="mt-12 flex flex-col items-center justify-between gap-2 border-t border-neutral-200 pt-6 text-xs text-neutral-500 sm:flex-row">
                <p>&copy; {{ new Date().getFullYear() }} TokoKita. Seluruh hak cipta dilindungi.</p>
                <p>Storefront Modern &amp; Sistem POS Terintegrasi</p>
            </div>
        </div>
    </footer>
</template>
