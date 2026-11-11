<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { usePermissions } from '@/Composables/usePermissions';
import BaseBadge from '@/Components/UI/BaseBadge.vue';

defineProps({
    mobileOpen: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);
const { user, can, isAdmin } = usePermissions();

const cashierLinks = computed(() => [
    {
        label: 'Dashboard',
        href: route('dashboard'),
        active: route().current('dashboard'),
        visible: can.value.viewDashboard,
        icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    },
    {
        label: 'Produk',
        href: route('produk.index'),
        active: route().current('produk.index') || route().current('produk.show'),
        visible: can.value.viewProducts,
        icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
    },
    {
        label: 'Tambah Produk',
        href: route('produk.create'),
        active: route().current('produk.create'),
        visible: can.value.createProducts,
        icon: 'M12 4v16m8-8H4',
    },
    {
        label: 'Pelanggan',
        href: route('pelanggan.index'),
        active: route().current('pelanggan.index') || route().current('pelanggan.show'),
        visible: can.value.viewCustomers,
        icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
    },
    {
        label: 'Tambah Pelanggan',
        href: route('pelanggan.create'),
        active: route().current('pelanggan.create'),
        visible: can.value.createCustomers,
        icon: 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
    },
    {
        label: 'Transaksi',
        href: route('transaksi.index'),
        active: route().current('transaksi.index') || route().current('transaksi.show'),
        visible: can.value.viewTransactions,
        icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
    },
    {
        label: 'Transaksi Baru',
        href: route('transaksi.create'),
        active: route().current('transaksi.create'),
        visible: can.value.createTransactions,
        icon: 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
    },
    {
        label: 'Riwayat Transaksi',
        href: route('transaksi.riwayat'),
        active: route().current('transaksi.riwayat'),
        visible: can.value.viewTransactionHistory,
        icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    },
]);

const operationalLinks = computed(() => [
    {
        label: 'Dashboard',
        href: route('dashboard'),
        active: route().current('dashboard'),
        visible: can.value.viewDashboard,
        icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    },
    {
        label: 'Produk',
        href: route('produk.index'),
        active: route().current('produk.*'),
        visible: can.value.viewProducts,
        icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
    },
    {
        label: 'Kategori',
        href: route('kategori.index'),
        active: route().current('kategori.*'),
        visible: can.value.manageCategories,
        icon: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
    },
    {
        label: 'Pelanggan',
        href: route('pelanggan.index'),
        active: route().current('pelanggan.*'),
        visible: can.value.viewCustomers,
        icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
    },
    {
        label: 'Transaksi',
        href: route('transaksi.index'),
        active:
            route().current('transaksi.index') ||
            route().current('transaksi.riwayat') ||
            route().current('transaksi.show'),
        visible: can.value.viewTransactions,
        icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
    },
    {
        label: 'Kasir / POS',
        href: route('transaksi.create'),
        active: route().current('transaksi.create'),
        visible: can.value.createTransactions,
        icon: 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
    },
    {
        label: 'Cabang',
        href: route('cabang.index'),
        active: route().current('cabang.*'),
        visible: can.value.manageBranches,
        icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
    },
]);

const adminLinks = computed(() => [
    {
        label: 'Laporan',
        href: route('laporan.index'),
        active: route().current('laporan.*'),
        visible: can.value.viewReports,
        icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    },
    {
        label: 'Pengguna',
        href: route('pengguna.index'),
        active: route().current('pengguna.*'),
        visible: can.value.manageUsers,
        icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    },
    {
        label: 'Pengaturan',
        href: route('pengaturan.index'),
        active: route().current('pengaturan.*'),
        visible: can.value.manageSettings,
        icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
    },
]);
</script>

<template>
    <!-- Mobile backdrop -->
    <div
        v-if="mobileOpen"
        class="fixed inset-0 z-40 bg-neutral-900/50 backdrop-blur-sm lg:hidden"
        @click="emit('close')"
    />

    <aside
        class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-neutral-200 bg-white transition-transform duration-200 print:hidden lg:sticky lg:top-0 lg:h-screen lg:shrink-0 lg:translate-x-0"
        :class="mobileOpen ? 'translate-x-0' : '-translate-x-full'"
    >
        <!-- Brand -->
        <div class="flex h-16 items-center justify-between border-b border-neutral-200 px-5">
            <Link :href="route('dashboard')" class="flex items-center gap-2.5">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-neutral-900 text-xs font-bold tracking-wider text-white">
                    TK
                </span>
                <div>
                    <span class="block text-sm font-semibold tracking-tight text-neutral-900">App Toko</span>
                    <span class="block text-[11px] text-neutral-500">
                        {{ isAdmin ? 'Panel Administrator' : 'Panel Kasir' }}
                    </span>
                </div>
            </Link>
            <button
                type="button"
                class="rounded-lg p-1 text-neutral-500 hover:bg-neutral-100 lg:hidden"
                aria-label="Tutup menu"
                @click="emit('close')"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Nav Links -->
        <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4" aria-label="Menu utama">
            <template v-if="isAdmin">
                <div>
                    <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-wider text-neutral-400">
                        Menu Utama
                    </p>
                    <div class="space-y-1">
                        <template v-for="item in operationalLinks" :key="item.label">
                            <Link
                                v-if="item.visible"
                                :href="item.href"
                                class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition"
                                :class="
                                    item.active
                                        ? 'bg-neutral-900 text-white'
                                        : 'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900'
                                "
                                @click="emit('close')"
                            >
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="item.icon" />
                                </svg>
                                <span>{{ item.label }}</span>
                            </Link>
                        </template>
                    </div>
                </div>

                <div>
                    <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-wider text-neutral-400">
                        Administrasi
                    </p>
                    <div class="space-y-1">
                        <template v-for="item in adminLinks" :key="item.label">
                            <Link
                                v-if="item.visible"
                                :href="item.href"
                                class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition"
                                :class="
                                    item.active
                                        ? 'bg-neutral-900 text-white'
                                        : 'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900'
                                "
                                @click="emit('close')"
                            >
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="item.icon" />
                                </svg>
                                <span>{{ item.label }}</span>
                            </Link>
                        </template>
                    </div>
                </div>
            </template>

            <div v-else>
                <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-wider text-neutral-400">
                    Menu Kasir
                </p>
                <div class="space-y-1">
                    <template v-for="item in cashierLinks" :key="item.label">
                        <Link
                            v-if="item.visible"
                            :href="item.href"
                            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition"
                            :class="
                                item.active
                                    ? 'bg-neutral-900 text-white'
                                    : 'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900'
                            "
                            @click="emit('close')"
                        >
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="item.icon" />
                            </svg>
                            <span>{{ item.label }}</span>
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <!-- Footer Info, Storefront & Logout -->
        <div class="space-y-2 border-t border-neutral-200 p-3">
            <Link
                :href="route('home')"
                class="flex items-center justify-between rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-2 text-xs font-medium text-neutral-700 transition hover:bg-neutral-100"
            >
                <span>Buka Storefront</span>
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
            </Link>

            <div v-if="user" class="flex items-center justify-between rounded-lg px-2 py-1.5">
                <div class="min-w-0">
                    <p class="truncate text-xs font-semibold text-neutral-900">{{ user.name }}</p>
                    <p class="truncate text-[11px] text-neutral-500">
                        {{ user.cabang?.nama_cabang ?? 'Semua Cabang' }}
                    </p>
                </div>
                <BaseBadge :variant="isAdmin ? 'dark' : 'success'">
                    {{ isAdmin ? 'Admin' : 'Kasir' }}
                </BaseBadge>
            </div>

            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-600"
                @click="emit('close')"
            >
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span>Logout</span>
            </Link>
        </div>
    </aside>
</template>
