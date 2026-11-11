<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BaseBadge from '@/Components/UI/BaseBadge.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BasePagination from '@/Components/UI/BasePagination.vue';
import BaseTable from '@/Components/UI/BaseTable.vue';
import SearchInput from '@/Components/Forms/SearchInput.vue';
import EmptyState from '@/Components/Feedback/EmptyState.vue';
import StatCard from '@/Pages/Dashboard/Components/StatCard.vue';
import SalesChart from '@/Pages/Dashboard/Components/SalesChart.vue';
import ReportTabs from '@/Pages/Laporan/Components/ReportTabs.vue';
import ReportFilter from '@/Pages/Laporan/Components/ReportFilter.vue';
import ReportPrintHeader from '@/Pages/Laporan/Components/ReportPrintHeader.vue';
import { useCurrency } from '@/Composables/useCurrency';
import { useDate } from '@/Composables/useDate';

const props = defineProps({
    aggregates: {
        type: Object,
        required: true,
    },
    salesChart: {
        type: Object,
        default: () => ({ daily: [], monthly: [] }),
    },
    chartSeries: {
        type: Array,
        default: () => [],
    },
    dailyRecap: {
        type: Object,
        required: true,
    },
    paymentMethodRecap: {
        type: Array,
        default: () => [],
    },
    branchRecap: {
        type: Array,
        default: () => [],
    },
    cashierRecap: {
        type: Array,
        default: () => [],
    },
    productSales: {
        type: Object,
        required: true,
    },
    branches: {
        type: Array,
        default: () => [],
    },
    customers: {
        type: Array,
        default: () => [],
    },
    cashiers: {
        type: Array,
        default: () => [],
    },
    storeInfo: {
        type: Object,
        default: () => ({ name: 'TokoKita' }),
    },
    filters: {
        type: Object,
        default: () => ({ start_date: '', end_date: '', cabang: '', pelanggan: '', kasir: '', search: '' }),
    },
});

const { formatCurrency } = useCurrency();
const { formatDate } = useDate();

const startDate = ref(props.filters.start_date ?? '');
const endDate = ref(props.filters.end_date ?? '');
const cabang = ref(props.filters.cabang ?? '');
const pelanggan = ref(props.filters.pelanggan ?? '');
const kasir = ref(props.filters.kasir ?? '');
const search = ref(props.filters.search ?? '');
const loading = ref(false);

const dailyHeaders = [
    { key: 'tanggal', label: 'Tanggal' },
    { key: 'jumlah_transaksi', label: 'Jumlah Transaksi', align: 'right' },
    { key: 'rata_rata', label: 'Rata-Rata', align: 'right' },
    { key: 'total_pendapatan', label: 'Pendapatan', align: 'right' },
];

const productHeaders = [
    { key: 'produk', label: 'Nama Produk' },
    { key: 'total_terjual', label: 'Qty Terjual', align: 'right' },
    { key: 'jumlah_transaksi', label: 'Transaksi', align: 'right' },
    { key: 'total_pendapatan', label: 'Pendapatan', align: 'right' },
];

const methodLabel = (metode) => {
    if (metode === 'tidak_tercatat') return 'Tidak Tercatat (Legacy)';
    return String(metode).toUpperCase();
};

const activeQuery = computed(() => {
    const query = {};
    if (startDate.value) query.start_date = startDate.value;
    if (endDate.value) query.end_date = endDate.value;
    if (cabang.value) query.cabang = cabang.value;
    if (pelanggan.value) query.pelanggan = pelanggan.value;
    if (kasir.value) query.kasir = kasir.value;
    if (search.value) query.search = search.value;
    return query;
});

const exportUrl = computed(() =>
    route('laporan.export', {
        report: 'sales',
        ...activeQuery.value,
    }),
);

const printFilterSummary = computed(() => {
    const parts = [];
    if (cabang.value) {
        const found = props.branches.find((b) => String(b.id_cabang) === String(cabang.value));
        if (found) parts.push(`Cabang: ${found.nama_cabang}`);
    }
    if (pelanggan.value) {
        const found = props.customers.find((c) => String(c.id_pelanggan) === String(pelanggan.value));
        if (found) parts.push(`Pelanggan: ${found.nama_pelanggan}`);
    }
    if (kasir.value) {
        const found = props.cashiers.find((u) => String(u.id) === String(kasir.value));
        if (found) parts.push(`Kasir: ${found.name}`);
    }
    if (search.value) {
        parts.push(`Cari Produk: "${search.value}"`);
    }
    return parts;
});

const applyFilters = () => {
    loading.value = true;
    router.get(route('laporan.index'), activeQuery.value, {
        preserveState: true,
        replace: true,
        onFinish: () => {
            loading.value = false;
        },
    });
};

const resetFilters = () => {
    startDate.value = '';
    endDate.value = '';
    cabang.value = '';
    pelanggan.value = '';
    kasir.value = '';
    search.value = '';
    applyFilters();
};

const printReport = () => {
    window.print();
};
</script>

<template>
    <Head title="Laporan Penjualan & Rekap Pendapatan" />

    <AdminLayout title="Laporan Penjualan & Rekap Pendapatan">
        <div class="space-y-6">
            <ReportPrintHeader
                title="Laporan Penjualan & Rekap Pendapatan"
                :store-name="storeInfo?.name || 'TokoKita'"
                :start-date="startDate"
                :end-date="endDate"
                :summary-parts="printFilterSummary"
            />

            <ReportTabs>
                <template #actions>
                    <BaseButton :href="exportUrl" external variant="secondary" size="sm">
                        Export CSV
                    </BaseButton>
                    <BaseButton variant="secondary" size="sm" @click="printReport">
                        Cetak Laporan
                    </BaseButton>
                </template>
            </ReportTabs>

            <ReportFilter
                v-model:start-date="startDate"
                v-model:end-date="endDate"
                v-model:cabang="cabang"
                v-model:pelanggan="pelanggan"
                v-model:kasir="kasir"
                :branches="branches"
                :customers="customers"
                :cashiers="cashiers"
                show-cashier
                @apply="applyFilters"
                @reset="resetFilters"
            />

            <!-- Rekap Pendapatan KPIs -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    label="Total Pendapatan"
                    :value="formatCurrency(aggregates.total_pendapatan)"
                    :description="aggregates.total_dibatalkan ? `${aggregates.total_dibatalkan} transaksi batal dikecualikan` : 'Seluruh transaksi non-batal'"
                    tone="emerald"
                />
                <StatCard
                    label="Transaksi Selesai"
                    :value="aggregates.total_transaksi ?? 0"
                    description="Sesuai filter aktif"
                    tone="sky"
                />
                <StatCard
                    label="Rata-Rata Transaksi"
                    :value="formatCurrency(aggregates.rata_rata)"
                    description="Nilai belanja rata-rata"
                    tone="amber"
                />
                <StatCard
                    label="Total Item Terjual"
                    :value="`${aggregates.total_item_terjual ?? 0} unit`"
                    description="Dari detail transaksi"
                    tone="neutral"
                />
            </div>

            <!-- Revenue Trend Chart (Harian / Bulanan toggle) -->
            <SalesChart
                :sales-chart="salesChart"
                title="Grafik Penjualan & Tren Pendapatan"
                subtitle="Pendapatan harian (hingga 30 tanggal) dan bulanan (hingga 12 bulan) sesuai filter."
            />

            <!-- Breakdown: Payment Methods, Branches & Cashiers -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <section class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
                    <h3 class="text-base font-semibold text-neutral-900">Rekap Metode Pembayaran</h3>
                    <p class="mt-0.5 text-xs text-neutral-500">Distribusi omzet berdasarkan metode bayar.</p>

                    <div v-if="paymentMethodRecap.length === 0" class="mt-4">
                        <EmptyState title="Belum ada data metode bayar" description="Tidak ada transaksi pada filter ini." />
                    </div>
                    <ul v-else class="mt-4 divide-y divide-neutral-100">
                        <li
                            v-for="row in paymentMethodRecap"
                            :key="row.metode"
                            class="flex items-center justify-between gap-2 py-3 text-sm"
                        >
                            <div class="min-w-0">
                                <p class="truncate font-medium text-neutral-900">{{ methodLabel(row.metode) }}</p>
                                <p class="text-xs text-neutral-500">{{ row.jumlah_transaksi }} transaksi</p>
                            </div>
                            <p class="shrink-0 font-semibold tabular-nums text-neutral-900">
                                {{ formatCurrency(row.total_pendapatan) }}
                            </p>
                        </li>
                    </ul>
                </section>

                <section class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
                    <h3 class="text-base font-semibold text-neutral-900">Rekap per Cabang</h3>
                    <p class="mt-0.5 text-xs text-neutral-500">Kontribusi pendapatan dari setiap cabang.</p>

                    <div v-if="branchRecap.length === 0" class="mt-4">
                        <EmptyState title="Belum ada data cabang" description="Tidak ada transaksi pada filter ini." />
                    </div>
                    <ul v-else class="mt-4 divide-y divide-neutral-100">
                        <li
                            v-for="row in branchRecap"
                            :key="`${row.nama_cabang}-${row.kota}`"
                            class="flex items-center justify-between gap-2 py-3 text-sm"
                        >
                            <div class="min-w-0">
                                <p class="truncate font-medium text-neutral-900">{{ row.nama_cabang }}</p>
                                <p class="truncate text-xs text-neutral-500">{{ row.kota }} &bull; {{ row.jumlah_transaksi }} transaksi</p>
                            </div>
                            <p class="shrink-0 font-semibold tabular-nums text-neutral-900">
                                {{ formatCurrency(row.total_pendapatan) }}
                            </p>
                        </li>
                    </ul>
                </section>

                <section class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
                    <h3 class="text-base font-semibold text-neutral-900">Rekap per Kasir / Staff</h3>
                    <p class="mt-0.5 text-xs text-neutral-500">Performa transaksi dan pendapatan per akun.</p>

                    <div v-if="cashierRecap.length === 0" class="mt-4">
                        <EmptyState title="Belum ada data kasir" description="Tidak ada transaksi pada filter ini." />
                    </div>
                    <ul v-else class="mt-4 divide-y divide-neutral-100">
                        <li
                            v-for="row in cashierRecap"
                            :key="`${row.user_id ?? 'legacy'}-${row.nama_kasir}`"
                            class="flex items-center justify-between gap-2 py-3 text-sm"
                        >
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <p class="truncate font-medium text-neutral-900">{{ row.nama_kasir }}</p>
                                    <BaseBadge v-if="row.role !== 'legacy'" :variant="row.role === 'admin' ? 'dark' : 'success'">
                                        {{ row.role === 'admin' ? 'Admin' : 'Kasir' }}
                                    </BaseBadge>
                                </div>
                                <p class="text-xs text-neutral-500">{{ row.jumlah_transaksi }} transaksi</p>
                            </div>
                            <p class="shrink-0 font-semibold tabular-nums text-neutral-900">
                                {{ formatCurrency(row.total_pendapatan) }}
                            </p>
                        </li>
                    </ul>
                </section>
            </div>

            <!-- Rekap Pendapatan Harian -->
            <div class="space-y-3">
                <div>
                    <h3 class="text-base font-semibold text-neutral-900">Rekap Pendapatan Harian</h3>
                    <p class="text-xs text-neutral-500">Rincian akumulasi transaksi dan omzet per tanggal.</p>
                </div>

                <BaseTable :headers="dailyHeaders" :loading="loading" :empty="(dailyRecap.data ?? []).length === 0">
                    <template #empty>
                        <EmptyState
                            title="Rekap harian kosong"
                            description="Sesuaikan filter tanggal, cabang, pelanggan, atau kasir untuk melihat rekap."
                        />
                    </template>
                    <tr v-for="row in dailyRecap.data" :key="row.tanggal" class="hover:bg-neutral-50/70">
                        <td class="whitespace-nowrap px-4 py-3 font-medium text-neutral-900">
                            {{ formatDate(row.tanggal) }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-neutral-700">
                            {{ row.jumlah_transaksi }} transaksi
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-neutral-600">
                            {{ formatCurrency(row.rata_rata) }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-neutral-900">
                            {{ formatCurrency(row.total_pendapatan) }}
                        </td>
                    </tr>
                    <template #footer>
                        <BasePagination :meta="dailyRecap" :links="dailyRecap.links" class="print:hidden" />
                    </template>
                </BaseTable>
            </div>

            <!-- Rekap Penjualan per Produk -->
            <div class="space-y-3">
                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                    <div>
                        <h3 class="text-base font-semibold text-neutral-900">Rekap Penjualan per Produk</h3>
                        <p class="text-xs text-neutral-500">
                            Produk dengan kontribusi pendapatan tertinggi pada filter aktif.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 print:hidden">
                        <form class="flex items-center gap-2" @submit.prevent="applyFilters">
                            <SearchInput
                                v-model="search"
                                placeholder="Cari produk..."
                                class="w-52 sm:w-64"
                                @search="applyFilters"
                            />
                            <BaseButton type="submit" variant="secondary" size="sm">Cari</BaseButton>
                        </form>
                        <BaseButton :href="route('laporan.produk', activeQuery)" variant="ghost" size="sm">
                            Lihat Produk Terlaris &rarr;
                        </BaseButton>
                    </div>
                </div>

                <BaseTable :headers="productHeaders" :loading="loading" :empty="(productSales.data ?? []).length === 0">
                    <template #empty>
                        <EmptyState
                            title="Belum ada detail penjualan produk"
                            description="Detail penjualan item belum tercatat pada filter atau kata kunci yang dipilih."
                        />
                    </template>
                    <tr v-for="row in productSales.data" :key="row.nama_produk" class="hover:bg-neutral-50/70">
                        <td class="px-4 py-3 font-medium text-neutral-900">
                            {{ row.nama_produk }}
                            <span v-if="row.kategori" class="block text-[11px] font-normal text-neutral-500">
                                {{ row.kategori }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-neutral-700">{{ row.total_terjual }} unit</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-neutral-600">{{ row.jumlah_transaksi }}x</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-neutral-900">
                            {{ formatCurrency(row.total_pendapatan) }}
                        </td>
                    </tr>
                    <template #footer>
                        <BasePagination :meta="productSales" :links="productSales.links" class="print:hidden" />
                    </template>
                </BaseTable>
            </div>
        </div>
    </AdminLayout>
</template>
