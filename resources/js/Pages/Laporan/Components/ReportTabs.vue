<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-neutral-200 pb-4 print:hidden">
        <nav class="flex flex-wrap items-center gap-2" aria-label="Navigasi modul laporan">
            <Link
                :href="route('laporan.index')"
                class="rounded-lg border px-3 py-2 text-xs font-medium transition"
                :class="
                    route().current('laporan.index')
                        ? 'border-neutral-900 bg-neutral-900 text-white'
                        : 'border-neutral-200 bg-white text-neutral-700 hover:bg-neutral-50'
                "
            >
                Penjualan &amp; Pendapatan
            </Link>
            <Link
                :href="route('laporan.transaksi')"
                class="rounded-lg border px-3 py-2 text-xs font-medium transition"
                :class="
                    route().current('laporan.transaksi')
                        ? 'border-neutral-900 bg-neutral-900 text-white'
                        : 'border-neutral-200 bg-white text-neutral-700 hover:bg-neutral-50'
                "
            >
                Laporan Transaksi
            </Link>
            <Link
                :href="route('laporan.produk')"
                class="rounded-lg border px-3 py-2 text-xs font-medium transition"
                :class="
                    route().current('laporan.produk')
                        ? 'border-neutral-900 bg-neutral-900 text-white'
                        : 'border-neutral-200 bg-white text-neutral-700 hover:bg-neutral-50'
                "
            >
                Produk Terlaris
            </Link>
            <Link
                :href="route('laporan.pelanggan')"
                class="rounded-lg border px-3 py-2 text-xs font-medium transition"
                :class="
                    route().current('laporan.pelanggan')
                        ? 'border-neutral-900 bg-neutral-900 text-white'
                        : 'border-neutral-200 bg-white text-neutral-700 hover:bg-neutral-50'
                "
            >
                Laporan Pelanggan
            </Link>
            <Link
                :href="route('transaksi.riwayat')"
                class="rounded-lg border px-3 py-2 text-xs font-medium transition"
                :class="
                    route().current('transaksi.riwayat')
                        ? 'border-neutral-900 bg-neutral-900 text-white'
                        : 'border-neutral-200 bg-white text-neutral-700 hover:bg-neutral-50'
                "
            >
                Riwayat Transaksi
            </Link>
        </nav>

        <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
