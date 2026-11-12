<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BaseBadge from '@/Components/UI/BaseBadge.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BasePagination from '@/Components/UI/BasePagination.vue';
import BaseTable from '@/Components/UI/BaseTable.vue';
import EmptyState from '@/Components/Feedback/EmptyState.vue';
import StatCard from '@/Pages/Dashboard/Components/StatCard.vue';
import ReportTabs from '@/Pages/Laporan/Components/ReportTabs.vue';
import ReportFilter from '@/Pages/Laporan/Components/ReportFilter.vue';
import ReportPrintHeader from '@/Pages/Laporan/Components/ReportPrintHeader.vue';
import TopProductsChart from '@/Pages/Laporan/Components/TopProductsChart.vue';
import { useCurrency } from '@/Composables/useCurrency';

const props = defineProps({
    aggregates: {
        type: Object,
        required: true,
    },
    chartTopProducts: {
        type: Array,
        default: () => [],
    },
    products: {
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
    categories: {
        type: Array,
        default: () => [],
    },
    storeInfo: {
        type: Object,
        default: () => ({ name: 'TokoKita' }),
    },
    filters: {
        type: Object,
        default: () => ({
            start_date: '',
            end_date: '',
            cabang: '',
            pelanggan: '',
            kasir: '',
            kategori: '',
            search: '',
            sort: 'qty',
        }),
    },
});

const { formatCurrency } = useCurrency();

const startDate = ref(props.filters.start_date ?? '');
const endDate = ref(props.filters.end_date ?? '');
const cabang = ref(props.filters.cabang ?? '');
const pelanggan = ref(props.filters.pelanggan ?? '');
const kasir = ref(props.filters.kasir ?? '');
const kategori = ref(props.filters.kategori ?? '');
const search = ref(props.filters.search ?? '');
const sort = ref(props.filters.sort || 'qty');
const loading = ref(false);

const headers = [
    { key: 'rank', label: 'Peringkat' },
    { key: 'produk', label: 'Produk' },
    { key: 'kategori', label: 'Kategori' },
    { key: 'harga', label: 'Harga Rata-Rata', align: 'right' },
    { key: 'transaksi', label: 'Transaksi', align: 'right' },
    { key: 'terjual', label: 'Qty Terjual', align: 'right' },
    { key: 'pendapatan', label: 'Total Omzet', align: 'right' },
    { key: 'stok', label: 'Sisa Stok', align: 'right' },
];

const rankOffset = computed(() => {
    const currentPage = Number(props.products.current_page ?? 1);
    const perPage = Number(props.products.per_page ?? 15);
    return (currentPage - 1) * perPage;
});

const activeQuery = computed(() => {
    const query = {};
    if (search.value) query.search = search.value;
    if (startDate.value) query.start_date = startDate.value;
    if (endDate.value) query.end_date = endDate.value;
    if (cabang.value) query.cabang = cabang.value;
    if (pelanggan.value) query.pelanggan = pelanggan.value;
    if (kasir.value) query.kasir = kasir.value;
    if (kategori.value) query.kategori = kategori.value;
    if (sort.value && sort.value !== 'qty') query.sort = sort.value;
    return query;
});

const exportUrl = computed(() =>
    route('laporan.export', {
        report: 'products',
        ...activeQuery.value,
        sort: sort.value || 'qty',
    }),
);

const printFilterSummary = computed(() => {
    const parts = [];
    if (search.value) parts.push(`Cari Produk: "${search.value}"`);
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
    if (kategori.value) {
        const found = props.categories.find((cat) => String(cat.id_kategori) === String(kategori.value));
        if (found) parts.push(`Kategori: ${found.nama_kategori}`);
    }
    parts.push(`Urutan: ${sort.value === 'revenue' ? 'Omzet Tertinggi' : 'Qty Terjual Terbanyak'}`);
    return parts;
});

const applyFilters = () => {
    loading.value = true;
    router.get(route('laporan.produk'), activeQuery.value, {
        preserveState: true,
        replace: true,
        onFinish: () => {
            loading.value = false;
        },
    });
};

const resetFilters = () => {
    search.value = '';
    startDate.value = '';
    endDate.value = '';
    cabang.value = '';
    pelanggan.value = '';
    kasir.value = '';
    kategori.value = '';
    sort.value = 'qty';
    applyFilters();
};

const printReport = () => {
    window.print();
};

const stockBadge = (stok) => {
    if (stok === null || stok === undefined) {
        return { variant: 'neutral', text: 'Legacy' };
    }
    if (Number(stok) <= 0) {
        return { variant: 'danger', text: 'Habis (0)' };
    }
    if (Number(stok) <= 5) {
        return { variant: 'warning', text: `Menipis (${stok})` };
    }
    return { variant: 'success', text: `Tersedia (${stok})` };
};
</script>

<template>
    <Head title="Laporan Produk Terlaris" />

    <AdminLayout title="Laporan Produk Terlaris">
        <div class="space-y-6">
            <ReportPrintHeader
                title="Laporan Produk Terlaris"
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
                v-model:search="search"
                v-model:start-date="startDate"
                v-model:end-date="endDate"
                v-model:cabang="cabang"
                v-model:pelanggan="pelanggan"
                v-model:kasir="kasir"
                v-model:kategori="kategori"
                v-model:sort="sort"
                :branches="branches"
                :customers="customers"
                :cashiers="cashiers"
                :categories="categories"
                show-search
                search-placeholder="Nama produk, kode SKU, atau kategori..."
                show-cashier
                show-category
                show-sort
                @apply="applyFilters"
                @reset="resetFilters"
            />

            <!-- KPIs -->
            <div
                class="enter-stagger-grid grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"
                style="--stagger-base: 120ms"
            >
                <StatCard
                    label="Produk Peringkat #1"
                    :value="aggregates.top_product_name || '-'"
                    :description="aggregates.top_product_name ? `${aggregates.top_product_qty} unit • ${formatCurrency(aggregates.top_product_revenue)}` : 'Belum ada data terjual'"
                    tone="amber"
                />
                <StatCard
                    label="Variasi Produk Terjual"
                    :value="aggregates.total_sku_terjual ?? 0"
                    description="Jumlah jenis barang terjual"
                    tone="sky"
                />
                <StatCard
                    label="Total Unit Terjual"
                    :value="`${aggregates.total_unit_terjual ?? 0} unit`"
                    description="Akumulasi qty detail transaksi"
                    tone="neutral"
                />
                <StatCard
                    label="Total Omzet Item"
                    :value="formatCurrency(aggregates.total_pendapatan)"
                    description="Dari detail transaksi non-batal"
                    tone="emerald"
                />
            </div>

            <!-- Horizontal Comparison Chart -->
            <TopProductsChart
                :products="chartTopProducts"
                :metric="sort === 'revenue' ? 'revenue' : 'qty'"
            />

            <!-- Paginated Table -->
            <BaseTable :headers="headers" :loading="loading" :empty="(products.data ?? []).length === 0">
                <template #empty>
                    <EmptyState
                        title="Tidak ada penjualan produk ditemukan"
                        description="Sesuaikan rentang tanggal, cabang, pelanggan, kasir, kategori, atau pencarian."
                    />
                </template>
                <tr v-for="(item, index) in products.data" :key="`${item.nama_produk}-${index}`" class="hover:bg-neutral-50/70">
                    <td class="whitespace-nowrap px-4 py-3">
                        <span
                            class="inline-flex h-6 min-w-[28px] items-center justify-center rounded-md px-1.5 text-xs font-semibold"
                            :class="rankOffset + index === 0 ? 'bg-neutral-900 text-white' : 'bg-neutral-100 text-neutral-700'"
                        >
                            #{{ rankOffset + index + 1 }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <Link
                            v-if="item.id_produk"
                            :href="route('produk.show', item.id_produk)"
                            class="font-medium text-neutral-900 hover:underline"
                        >
                            {{ item.nama_produk }}
                        </Link>
                        <span v-else class="font-medium text-neutral-900">{{ item.nama_produk }}</span>
                        <span class="block text-[11px] text-neutral-500">
                            {{ item.kode_produk || 'Data Historis / Tanpa SKU' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-neutral-600">{{ item.kategori || 'Umum / Legacy' }}</td>
                    <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-neutral-600">
                        {{ formatCurrency(item.harga_rata_rata) }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-neutral-700">
                        {{ item.jumlah_transaksi }}x
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-neutral-900">
                        {{ item.total_terjual }} unit
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-neutral-900">
                        {{ formatCurrency(item.total_pendapatan) }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-right">
                        <BaseBadge :variant="stockBadge(item.stok).variant">
                            {{ stockBadge(item.stok).text }}
                        </BaseBadge>
                    </td>
                </tr>

                <template #footer>
                    <BasePagination :meta="products" :links="products.links" class="print:hidden" />
                </template>
            </BaseTable>
        </div>
    </AdminLayout>
</template>

