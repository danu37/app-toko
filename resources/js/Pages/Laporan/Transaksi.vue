<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BaseBadge from '@/Components/UI/BaseBadge.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BasePagination from '@/Components/UI/BasePagination.vue';
import BaseTable from '@/Components/UI/BaseTable.vue';
import TableAction from '@/Components/Tables/TableAction.vue';
import EmptyState from '@/Components/Feedback/EmptyState.vue';
import ReportTabs from '@/Pages/Laporan/Components/ReportTabs.vue';
import ReportFilter from '@/Pages/Laporan/Components/ReportFilter.vue';
import ReportPrintHeader from '@/Pages/Laporan/Components/ReportPrintHeader.vue';
import StatCard from '@/Pages/Dashboard/Components/StatCard.vue';
import { useCurrency } from '@/Composables/useCurrency';
import { useDate } from '@/Composables/useDate';

const props = defineProps({
    aggregates: {
        type: Object,
        required: true,
    },
    transactions: {
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
        default: () => ({ start_date: '', end_date: '', cabang: '', pelanggan: '', kasir: '', status: '', search: '' }),
    },
});

const { formatCurrency } = useCurrency();
const { formatDate } = useDate();

const startDate = ref(props.filters.start_date ?? '');
const endDate = ref(props.filters.end_date ?? '');
const cabang = ref(props.filters.cabang ?? '');
const pelanggan = ref(props.filters.pelanggan ?? '');
const kasir = ref(props.filters.kasir ?? '');
const status = ref(props.filters.status ?? '');
const search = ref(props.filters.search ?? '');
const loading = ref(false);

const headers = [
    { key: 'kode', label: 'Kode / Metode' },
    { key: 'tanggal', label: 'Tanggal' },
    { key: 'pelanggan', label: 'Pelanggan' },
    { key: 'kasir', label: 'Kasir & Cabang' },
    { key: 'status', label: 'Status' },
    { key: 'total', label: 'Total Belanja', align: 'right' },
    { key: 'aksi', label: 'Aksi', align: 'right' },
];

const activeQuery = computed(() => {
    const query = {};
    if (search.value) query.search = search.value;
    if (startDate.value) query.start_date = startDate.value;
    if (endDate.value) query.end_date = endDate.value;
    if (cabang.value) query.cabang = cabang.value;
    if (pelanggan.value) query.pelanggan = pelanggan.value;
    if (kasir.value) query.kasir = kasir.value;
    if (status.value) query.status = status.value;
    return query;
});

const exportUrl = computed(() =>
    route('laporan.export', {
        report: 'transactions',
        ...activeQuery.value,
    }),
);

const printFilterSummary = computed(() => {
    const parts = [];
    if (search.value) parts.push(`Kata Kunci: "${search.value}"`);
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
    if (status.value) parts.push(`Status: ${status.value}`);
    return parts;
});

const applyFilters = () => {
    loading.value = true;
    router.get(route('laporan.transaksi'), activeQuery.value, {
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
    status.value = '';
    applyFilters();
};

const printReport = () => {
    window.print();
};
</script>

<template>
    <Head title="Laporan Transaksi" />

    <AdminLayout title="Laporan Transaksi">
        <div class="space-y-6">
            <ReportPrintHeader
                title="Laporan Transaksi"
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
                v-model:status="status"
                :branches="branches"
                :customers="customers"
                :cashiers="cashiers"
                show-search
                search-placeholder="Kode, pelanggan, kasir, atau produk..."
                show-cashier
                show-status
                @apply="applyFilters"
                @reset="resetFilters"
            />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    label="Total Transaksi"
                    :value="aggregates.total_transaksi ?? 0"
                    :description="`${aggregates.total_selesai ?? 0} selesai${aggregates.total_dibatalkan ? ` • ${aggregates.total_dibatalkan} batal` : ''}`"
                    tone="sky"
                />
                <StatCard
                    label="Rekap Pendapatan"
                    :value="formatCurrency(aggregates.total_omzet)"
                    description="Akumulasi transaksi non-batal"
                    tone="emerald"
                />
                <StatCard
                    label="Rata-Rata Belanja"
                    :value="formatCurrency(aggregates.rata_rata)"
                    description="Per transaksi selesai"
                    tone="amber"
                />
                <StatCard
                    label="Total Item Terjual"
                    :value="`${aggregates.total_item_terjual ?? 0} unit`"
                    description="Barang tercatat pada detail"
                    tone="neutral"
                />
            </div>

            <BaseTable :headers="headers" :loading="loading" :empty="(transactions.data ?? []).length === 0">
                <template #empty>
                    <EmptyState
                        title="Tidak ada data transaksi"
                        description="Sesuaikan pencarian, rentang tanggal, cabang, pelanggan, atau kasir untuk melihat laporan transaksi."
                    />
                </template>
                <tr v-for="item in transactions.data" :key="item.id_transaksi" class="hover:bg-neutral-50/70">
                    <td class="whitespace-nowrap px-4 py-3 font-medium text-neutral-900">
                        {{ item.kode_transaksi || `#TRX-${item.id_transaksi}` }}
                        <span class="block text-[11px] font-normal text-neutral-500">
                            {{ item.detail_count ?? 0 }} item
                            <template v-if="item.metode_pembayaran">
                                &bull; {{ String(item.metode_pembayaran).toUpperCase() }}
                            </template>
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-neutral-600">{{ formatDate(item.tanggal) }}</td>
                    <td class="px-4 py-3 text-neutral-800">
                        <Link
                            v-if="item.pelanggan?.id_pelanggan"
                            :href="route('pelanggan.show', item.pelanggan.id_pelanggan)"
                            class="font-medium hover:underline"
                        >
                            {{ item.pelanggan.nama_pelanggan }}
                        </Link>
                        <span v-else>{{ item.pelanggan?.nama_pelanggan ?? 'Umum' }}</span>
                    </td>
                    <td class="px-4 py-3 text-xs text-neutral-600">
                        <div class="font-medium text-neutral-800">{{ item.nama_kasir ?? item.kasir?.name ?? 'Transaksi Legacy' }}</div>
                        <div>{{ item.cabang?.nama_cabang ?? '-' }}</div>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3">
                        <BaseBadge :variant="item.status === 'dibatalkan' ? 'danger' : 'success'">
                            {{ item.status ?? 'selesai' }}
                        </BaseBadge>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-neutral-900">
                        {{ formatCurrency(item.total_belanja) }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-right print:hidden">
                        <TableAction :href="route('transaksi.show', item.id_transaksi)" variant="primary">
                            Detail
                        </TableAction>
                    </td>
                </tr>

                <template #footer>
                    <BasePagination :meta="transactions.meta" class="print:hidden" />
                </template>
            </BaseTable>
        </div>
    </AdminLayout>
</template>
