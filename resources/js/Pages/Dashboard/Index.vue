<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CashierLayout from '@/Layouts/CashierLayout.vue';
import BaseBadge from '@/Components/UI/BaseBadge.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import StatCard from '@/Pages/Dashboard/Components/StatCard.vue';
import SalesChart from '@/Pages/Dashboard/Components/SalesChart.vue';
import TopProductsTable from '@/Pages/Dashboard/Components/TopProductsTable.vue';
import StockSummaryCard from '@/Pages/Dashboard/Components/StockSummaryCard.vue';
import RecentTransaction from '@/Pages/Dashboard/Components/RecentTransaction.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { useCurrency } from '@/Composables/useCurrency';

defineProps({
    summary: {
        type: Object,
        required: true,
    },
    recentTransactions: {
        type: Array,
        default: () => [],
    },
    salesChart: {
        type: Object,
        default: () => ({ daily: [], monthly: [] }),
    },
    topProducts: {
        type: Array,
        default: () => [],
    },
    stockSummary: {
        type: Object,
        default: null,
    },
});

const { user, can, isAdmin } = usePermissions();
const { formatCurrency } = useCurrency();
</script>

<template>
    <Head :title="isAdmin ? 'Dashboard Admin' : 'Dashboard Operasional'" />

    <component
        :is="isAdmin ? AdminLayout : CashierLayout"
        :title="isAdmin ? 'Dashboard Administrator' : 'Dashboard Operasional Kasir'"
    >
        <!-- ADMIN DASHBOARD -->
        <div v-if="isAdmin" class="space-y-6">
            <!-- Admin Header Banner -->
            <div class="flex flex-col justify-between gap-4 rounded-xl border border-neutral-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-semibold text-neutral-900">
                            Selamat datang, {{ user?.name }}
                        </h2>
                        <BaseBadge variant="dark">Administrator</BaseBadge>
                    </div>
                    <p class="mt-1 text-sm text-neutral-500">
                        Ringkasan kinerja toko real-time: {{ summary.total_cabang ?? 0 }} cabang, {{ summary.total_kategori ?? 0 }} kategori aktif, dan {{ summary.total_pengguna ?? 0 }} akun staff.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <BaseButton :href="route('laporan.index')" variant="secondary" size="sm">
                        Buka Laporan
                    </BaseButton>
                    <BaseButton v-if="can.createProducts" :href="route('produk.create')" variant="secondary" size="sm">
                        + Tambah Produk
                    </BaseButton>
                    <BaseButton v-if="can.createTransactions" :href="route('transaksi.create')" variant="emerald" size="sm">
                        + Transaksi POS
                    </BaseButton>
                </div>
            </div>

            <!-- Admin 4 Primary KPIs -->
            <div
                class="enter-stagger-grid grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"
                style="--stagger-base: 55ms"
            >
                <StatCard
                    label="Total Produk"
                    :value="summary.total_produk ?? 0"
                    :description="`${summary.produk_aktif ?? 0} aktif • ${summary.total_kategori ?? 0} kategori`"
                    tone="amber"
                />
                <StatCard
                    label="Total Pelanggan"
                    :value="summary.total_pelanggan ?? 0"
                    description="Pelanggan tercatat di database"
                    tone="sky"
                />
                <StatCard
                    label="Total Transaksi"
                    :value="summary.total_transaksi ?? 0"
                    :description="`Hari ini: ${summary.transaksi_hari_ini ?? 0} • Selesai: ${summary.total_transaksi_selesai ?? 0}`"
                    tone="neutral"
                />
                <StatCard
                    label="Total Pendapatan"
                    :value="formatCurrency(summary.total_omzet)"
                    :description="`Hari ini: ${formatCurrency(summary.omzet_hari_ini)} • Rata-rata: ${formatCurrency(summary.rata_rata_transaksi)}`"
                    tone="emerald"
                />
            </div>

            <!-- Sales Chart + Stock Summary -->
            <div
                class="enter-stagger-grid grid grid-cols-1 gap-6 lg:grid-cols-12"
                style="--stagger-base: 150ms"
            >
                <div class="lg:col-span-8">
                    <SalesChart :sales-chart="salesChart" />
                </div>
                <div class="lg:col-span-4">
                    <StockSummaryCard :stock-summary="stockSummary" />
                </div>
            </div>

            <!-- Top Products + Recent Transactions -->
            <div
                class="enter-stagger-grid grid grid-cols-1 gap-6 lg:grid-cols-12"
                style="--stagger-base: 210ms"
            >
                <div class="lg:col-span-5">
                    <TopProductsTable :products="topProducts" />
                </div>

                <div class="space-y-3 lg:col-span-7">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-neutral-900">Transaksi Terbaru</h3>
                            <p class="text-xs text-neutral-500">5 transaksi terakhir yang tercatat di sistem.</p>
                        </div>
                        <BaseButton :href="route('transaksi.index')" variant="ghost" size="sm">
                            Lihat Semua &rarr;
                        </BaseButton>
                    </div>

                    <RecentTransaction :transactions="recentTransactions" />
                </div>
            </div>
        </div>

        <!-- CASHIER OPERATIONAL DASHBOARD -->
        <div v-else class="space-y-6">
            <!-- Operational Action Header -->
            <div class="flex flex-col justify-between gap-4 rounded-xl border border-neutral-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-semibold text-neutral-900">
                            Halo, {{ user?.name }}
                        </h2>
                        <BaseBadge variant="success">Kasir Aktif</BaseBadge>
                    </div>
                    <p class="mt-1 text-sm text-neutral-500">
                        {{ user?.cabang?.nama_cabang ?? 'Cabang Utama' }} &bull; Akses cepat transaksi POS, produk, dan pelanggan baru.
                    </p>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <BaseButton v-if="can.createCustomers" :href="route('pelanggan.create')" variant="secondary" size="sm">
                        + Tambah Pelanggan
                    </BaseButton>
                    <BaseButton v-if="can.createProducts" :href="route('produk.create')" variant="secondary" size="sm">
                        + Tambah Produk
                    </BaseButton>
                    <BaseButton v-if="can.createTransactions" :href="route('transaksi.create')" variant="emerald">
                        + Transaksi Baru
                    </BaseButton>
                </div>
            </div>

            <!-- 3 Clean Daily Operational Metrics -->
            <div
                class="enter-stagger-grid grid grid-cols-1 gap-4 sm:grid-cols-3"
                style="--stagger-base: 55ms"
            >
                <StatCard
                    label="Transaksi Hari Ini"
                    :value="summary.transaksi_hari_ini ?? 0"
                    :description="`Diproses Anda hari ini: ${summary.transaksi_kasir_hari_ini ?? 0} transaksi`"
                    tone="sky"
                />
                <StatCard
                    label="Pelanggan Dilayani"
                    :value="summary.pelanggan_dilayani_hari_ini ?? 0"
                    :description="`Hari ini • Total ${summary.total_pelanggan ?? 0} pelanggan terdaftar`"
                    tone="amber"
                />
                <StatCard
                    v-if="can.viewDailySales"
                    label="Total Penjualan Hari Ini"
                    :value="formatCurrency(summary.omzet_hari_ini ?? 0)"
                    :description="`${summary.item_terjual_hari_ini ?? 0} barang terjual hari ini`"
                    tone="emerald"
                />
                <StatCard
                    v-else
                    label="Katalog Produk Aktif"
                    :value="summary.produk_aktif ?? 0"
                    :description="`Dari ${summary.total_produk ?? 0} total produk`"
                    tone="neutral"
                />
            </div>

            <!-- Recent Accessible Transactions -->
            <div class="space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="text-base font-semibold text-neutral-900">Transaksi Terbaru</h3>
                        <p class="text-xs text-neutral-500">5 transaksi terakhir yang tercatat dan dapat diakses kasir.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <BaseButton
                            v-if="can.viewTransactionHistory"
                            :href="route('transaksi.riwayat')"
                            variant="secondary"
                            size="sm"
                        >
                            Riwayat Transaksi
                        </BaseButton>
                        <BaseButton :href="route('transaksi.index')" variant="ghost" size="sm">
                            Semua Transaksi &rarr;
                        </BaseButton>
                    </div>
                </div>

                <RecentTransaction :transactions="recentTransactions" />
            </div>
        </div>
    </component>
</template>
