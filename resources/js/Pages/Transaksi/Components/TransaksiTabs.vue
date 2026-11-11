<script setup>
import { Link } from '@inertiajs/vue3';
import BaseButton from '@/Components/UI/BaseButton.vue';
import { usePermissions } from '@/Composables/usePermissions';

const { can } = usePermissions();
</script>

<template>
    <div class="flex flex-col justify-between gap-3 border-b border-neutral-200 pb-4 sm:flex-row sm:items-center">
        <nav class="flex flex-wrap items-center gap-2" aria-label="Navigasi modul transaksi">
            <Link
                :href="route('transaksi.index')"
                class="rounded-lg border px-3.5 py-2 text-xs font-medium transition"
                :class="
                    route().current('transaksi.index')
                        ? 'border-neutral-900 bg-neutral-900 text-white'
                        : 'border-neutral-200 bg-white text-neutral-700 hover:bg-neutral-50'
                "
            >
                Daftar Transaksi
            </Link>
            <Link
                v-if="can.viewTransactionHistory"
                :href="route('transaksi.riwayat')"
                class="rounded-lg border px-3.5 py-2 text-xs font-medium transition"
                :class="
                    route().current('transaksi.riwayat')
                        ? 'border-neutral-900 bg-neutral-900 text-white'
                        : 'border-neutral-200 bg-white text-neutral-700 hover:bg-neutral-50'
                "
            >
                Riwayat Transaksi
            </Link>
        </nav>

        <BaseButton v-if="can.createTransactions" :href="route('transaksi.create')" variant="emerald" size="sm">
            + Transaksi Baru (POS)
        </BaseButton>
    </div>
</template>

