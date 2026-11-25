<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CashierLayout from '@/Layouts/CashierLayout.vue';
import BaseBadge from '@/Components/UI/BaseBadge.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BaseSelect from '@/Components/UI/BaseSelect.vue';
import SearchInput from '@/Components/Forms/SearchInput.vue';
import DateRangePicker from '@/Components/Forms/DateRangePicker.vue';
import EmptyState from '@/Components/Feedback/EmptyState.vue';
import StatCard from '@/Pages/Dashboard/Components/StatCard.vue';
import TransaksiTabs from '@/Pages/Transaksi/Components/TransaksiTabs.vue';
import ReportPrintHeader from '@/Pages/Laporan/Components/ReportPrintHeader.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { useCurrency } from '@/Composables/useCurrency';
import { useDate } from '@/Composables/useDate';

const props = defineProps({
    aggregates: {
        type: Object,
        required: true,
    },
    paymentMethodRecap: {
        type: Array,
        default: () => [],
    },
    transactions: {
        type: Array,
        default: () => [],
    },
    branches: {
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
        default: () => ({
            search: '',
            start_date: '',
            end_date: '',
            cabang: '',
            kasir: '',
            status: '',
            metode: '',
        }),
    },
});

const { user, isAdmin } = usePermissions();
const { formatCurrency } = useCurrency();
const { formatDate } = useDate();

const search = ref(props.filters.search ?? '');
const startDate = ref(props.filters.start_date ?? '');
const endDate = ref(props.filters.end_date ?? '');
const cabang = ref(props.filters.cabang ?? '');
const kasir = ref(props.filters.kasir ?? '');
const status = ref(props.filters.status ?? '');
const metode = ref(props.filters.metode ?? '');
const loading = ref(false);

let pageStyleEl = null;

onMounted(() => {
    if (typeof document === 'undefined') return;
    pageStyleEl = document.createElement('style');
    pageStyleEl.setAttribute('data-a4-report-page', 'true');
    pageStyleEl.textContent = '@media print { @page { size: A4 portrait; margin: 12mm; } }';
    document.head.appendChild(pageStyleEl);
});

onUnmounted(() => {
    if (pageStyleEl && pageStyleEl.parentNode) {
        pageStyleEl.parentNode.removeChild(pageStyleEl);
        pageStyleEl = null;
    }
});

const branchOptions = computed(() =>
    props.branches.map((b) => ({
        value: b.id_cabang,
        label: b.nama_cabang,
    })),
);

const cashierOptions = computed(() =>
    props.cashiers.map((u) => ({
        value: u.id,
        label: `${u.name} (${u.role === 'admin' ? 'Admin' : 'Kasir'})`,
    })),
);

const statusOptions = [
    { value: 'selesai', label: 'Selesai' },
    { value: 'dibatalkan', label: 'Dibatalkan' },
];

const methodOptions = [
    { value: 'tunai', label: 'Tunai' },
    { value: 'qris', label: 'QRIS' },
    { value: 'transfer', label: 'Transfer Bank' },
    { value: 'debit', label: 'Kartu Debit' },
];

const methodLabel = (value) => {
    if (!value || value === 'tidak_tercatat') return 'Tidak Tercatat';
    return String(value).toUpperCase();
};

const activeQuery = computed(() => {
    const query = {};
    if (search.value) query.search = search.value;
    if (startDate.value) query.start_date = startDate.value;
    if (endDate.value) query.end_date = endDate.value;
    if (cabang.value) query.cabang = cabang.value;
    if (isAdmin.value && kasir.value) query.kasir = kasir.value;
    if (status.value) query.status = status.value;
    if (metode.value) query.metode = metode.value;
    return query;
});

const printFilterSummary = computed(() => {
    const parts = [];
    if (!isAdmin.value && user.value?.name) {
        parts.push(`Kasir: ${user.value.name}`);
    } else if (isAdmin.value && kasir.value) {
        const found = props.cashiers.find((u) => String(u.id) === String(kasir.value));
        if (found) parts.push(`Kasir: ${found.name}`);
    }
    if (cabang.value) {
        const found = props.branches.find((b) => String(b.id_cabang) === String(cabang.value));
        if (found) parts.push(`Cabang: ${found.nama_cabang}`);
    }
    if (metode.value) parts.push(`Metode: ${methodLabel(metode.value)}`);
    if (status.value) parts.push(`Status: ${status.value}`);
    if (search.value) parts.push(`Kata Kunci: "${search.value}"`);
    return parts;
});

const applyFilters = () => {
    loading.value = true;
    router.get(route('transaksi.laporan'), activeQuery.value, {
        preserveState: true,
        replace: true,
        onFinish: () => {
            loading.value = false;
        },
    });
};

const setTodayPreset = () => {
    const today = new Date().toISOString().slice(0, 10);
    startDate.value = today;
    endDate.value = today;
    applyFilters();
};

const resetFilters = () => {
    search.value = '';
    startDate.value = '';
    endDate.value = '';
    cabang.value = '';
    kasir.value = '';
    status.value = '';
    metode.value = '';
    applyFilters();
};

const downloadPdfUrl = computed(() => route('transaksi.laporan.pdf', activeQuery.value));

const downloadPdf = () => {
    window.location.assign(downloadPdfUrl.value);
};
</script>

<template>
    <Head title="Laporan Operasional Kasir (PDF)" />

    <component
        :is="isAdmin ? AdminLayout : CashierLayout"
        title="Laporan Operasional Kasir (PDF)"
    >
        <div class="space-y-6">
            <ReportPrintHeader
                :title="isAdmin ? 'Laporan Operasional Transaksi' : 'Laporan Shift & Transaksi Kasir'"
                :store-name="storeInfo?.name || 'TokoKita'"
                :start-date="startDate"
                :end-date="endDate"
                :summary-parts="printFilterSummary"
            />

            <TransaksiTabs class="print:hidden" />

            <!-- Filter & Tombol Cetak PDF -->
            <form
                class="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm print:hidden"
                @submit.prevent="applyFilters"
            >
                <div class="flex flex-col justify-between gap-3 border-b border-neutral-100 pb-3.5 sm:flex-row sm:items-center">
                    <div>
                        <h2 class="text-sm font-semibold text-neutral-900">
                            Filter &amp; Download Laporan PDF
                        </h2>
                        <p class="text-xs text-neutral-500">
                            <template v-if="isAdmin">
                                Pilih rentang tanggal dan filter lalu klik Download PDF untuk mengunduh laporan transaksi (A4).
                            </template>
                            <template v-else>
                                Menampilkan transaksi yang Anda proses ({{ user?.name }}). Klik Download PDF untuk mengunduh file laporan.
                            </template>
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <BaseButton type="button" variant="secondary" size="sm" @click="setTodayPreset">
                            Hari Ini
                        </BaseButton>
                        <BaseButton type="button" variant="emerald" size="sm" @click="downloadPdf">
                            Download PDF
                        </BaseButton>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-12">
                    <div class="sm:col-span-2 lg:col-span-4">
                        <DateRangePicker
                            :start-date="startDate"
                            :end-date="endDate"
                            @update:start-date="startDate = $event"
                            @update:end-date="endDate = $event"
                        />
                    </div>

                    <div class="lg:col-span-2">
                        <BaseSelect
                            id="laporan_metode"
                            v-model="metode"
                            label="Metode Bayar"
                            :options="methodOptions"
                            placeholder="Semua Metode"
                        />
                    </div>

                    <div class="lg:col-span-2">
                        <BaseSelect
                            id="laporan_status"
                            v-model="status"
                            label="Status"
                            :options="statusOptions"
                            placeholder="Semua Status"
                        />
                    </div>

                    <div class="lg:col-span-2">
                        <BaseSelect
                            id="laporan_cabang"
                            v-model="cabang"
                            label="Cabang"
                            :options="branchOptions"
                            placeholder="Semua Cabang"
                        />
                    </div>

                    <div v-if="isAdmin && cashierOptions.length > 0" class="lg:col-span-2">
                        <BaseSelect
                            id="laporan_kasir"
                            v-model="kasir"
                            label="Kasir / Staff"
                            :options="cashierOptions"
                            placeholder="Semua Kasir"
                        />
                    </div>

                    <div class="sm:col-span-2 lg:col-span-4">
                        <label for="laporan_search" class="mb-1.5 block text-sm font-medium text-neutral-700">
                            Pencarian
                        </label>
                        <SearchInput
                            id="laporan_search"
                            v-model="search"
                            placeholder="Kode transaksi atau nama pelanggan..."
                            @search="applyFilters"
                        />
                    </div>

                    <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-4">
                        <BaseButton type="submit" variant="primary" :loading="loading">
                            Terapkan Filter
                        </BaseButton>
                        <BaseButton type="button" variant="ghost" @click="resetFilters">
                            Reset
                        </BaseButton>
                    </div>
                </div>
            </form>

            <!-- Ringkasan KPI Operasional -->
            <div
                class="enter-stagger-grid grid grid-cols-2 gap-3 sm:grid-cols-4 print:grid-cols-4 print:gap-2"
                style="--stagger-base: 80ms"
            >
                <StatCard
                    label="Total Transaksi"
                    :value="aggregates.total_transaksi ?? 0"
                    :description="`${aggregates.total_selesai ?? 0} selesai${aggregates.total_dibatalkan ? ` • ${aggregates.total_dibatalkan} batal` : ''}`"
                    tone="sky"
                />
                <StatCard
                    label="Total Penjualan"
                    :value="formatCurrency(aggregates.total_omzet)"
                    description="Transaksi non-batal"
                    tone="emerald"
                />
                <StatCard
                    label="Rata-Rata Transaksi"
                    :value="formatCurrency(aggregates.rata_rata)"
                    description="Per transaksi selesai"
                    tone="amber"
                />
                <StatCard
                    label="Total Item Terjual"
                    :value="`${aggregates.total_item_terjual ?? 0} unit`"
                    description="Akumulasi detail barang"
                    tone="neutral"
                />
            </div>

            <!-- Rekap Metode Pembayaran (Rekonsiliasi Kasir) -->
            <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm print:rounded-none print:border-neutral-300 print:p-3 print:shadow-none">
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-neutral-900">
                            Rekap Metode Pembayaran (Rekonsiliasi)
                        </h3>
                        <p class="text-xs text-neutral-500 print:hidden">
                            Rincian nominal masuk berdasarkan metode pembayaran non-batal.
                        </p>
                    </div>
                </div>

                <div v-if="paymentMethodRecap.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200 text-xs sm:text-sm">
                        <thead class="bg-neutral-50 text-left text-xs font-semibold uppercase tracking-wider text-neutral-600">
                            <tr>
                                <th class="px-3 py-2">Metode Pembayaran</th>
                                <th class="px-3 py-2 text-right">Jumlah Transaksi</th>
                                <th class="px-3 py-2 text-right">Total Nominal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            <tr v-for="row in paymentMethodRecap" :key="row.metode">
                                <td class="px-3 py-2 font-medium text-neutral-900">
                                    {{ methodLabel(row.metode) }}
                                </td>
                                <td class="px-3 py-2 text-right tabular-nums text-neutral-700">
                                    {{ row.jumlah_transaksi }}
                                </td>
                                <td class="px-3 py-2 text-right font-semibold tabular-nums text-neutral-900">
                                    {{ formatCurrency(row.total_nominal) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="border-t-2 border-neutral-200 bg-neutral-50/70 font-semibold">
                            <tr>
                                <td class="px-3 py-2 text-neutral-900">Total Keseluruhan</td>
                                <td class="px-3 py-2 text-right tabular-nums text-neutral-900">
                                    {{ aggregates.total_selesai ?? 0 }}
                                </td>
                                <td class="px-3 py-2 text-right tabular-nums text-neutral-900">
                                    {{ formatCurrency(aggregates.total_omzet) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <p v-else class="text-xs text-neutral-500">
                    Belum ada transaksi selesai pada periode yang dipilih.
                </p>
            </div>

            <!-- Tabel Rincian Transaksi -->
            <div class="rounded-xl border border-neutral-200 bg-white shadow-sm print:rounded-none print:border-neutral-300 print:shadow-none">
                <div class="border-b border-neutral-100 px-4 py-3 print:px-3 print:py-2">
                    <h3 class="text-sm font-semibold text-neutral-900">
                        Rincian Daftar Transaksi ({{ transactions.length }})
                    </h3>
                </div>

                <div v-if="transactions.length === 0" class="p-6">
                    <EmptyState
                        title="Tidak ada transaksi pada periode ini"
                        description="Ubah rentang tanggal atau filter untuk menampilkan data transaksi yang siap dicetak ke PDF."
                    />
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200 text-xs sm:text-sm print:text-[11px]">
                        <thead class="bg-neutral-50 text-left text-xs font-semibold uppercase tracking-wider text-neutral-600 print:text-[10px]">
                            <tr>
                                <th class="px-3 py-2.5">No</th>
                                <th class="px-3 py-2.5">Kode Transaksi</th>
                                <th class="px-3 py-2.5">Tanggal</th>
                                <th class="px-3 py-2.5">Pelanggan</th>
                                <th class="px-3 py-2.5">Kasir &amp; Cabang</th>
                                <th class="px-3 py-2.5">Metode</th>
                                <th class="px-3 py-2.5">Status</th>
                                <th class="px-3 py-2.5 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100 bg-white">
                            <tr v-for="(item, idx) in transactions" :key="item.id_transaksi" class="hover:bg-neutral-50/70">
                                <td class="whitespace-nowrap px-3 py-2 text-neutral-500 tabular-nums">
                                    {{ idx + 1 }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-2 font-medium text-neutral-900">
                                    <Link
                                        :href="route('transaksi.show', item.id_transaksi)"
                                        class="hover:underline print:no-underline"
                                    >
                                        {{ item.kode_transaksi || `#TRX-${item.id_transaksi}` }}
                                    </Link>
                                    <span class="block text-[11px] font-normal text-neutral-500">
                                        {{ item.detail_count ?? 0 }} item
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-2 text-neutral-700">
                                    {{ formatDate(item.tanggal) }}
                                </td>
                                <td class="px-3 py-2 text-neutral-800">
                                    {{ item.pelanggan?.nama_pelanggan ?? 'Pelanggan Umum' }}
                                </td>
                                <td class="px-3 py-2 text-neutral-600">
                                    <div class="font-medium text-neutral-800">
                                        {{ item.nama_kasir ?? item.kasir?.name ?? '-' }}
                                    </div>
                                    <div class="text-[11px]">{{ item.cabang?.nama_cabang ?? '-' }}</div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-2 uppercase text-neutral-700">
                                    {{ methodLabel(item.metode_pembayaran) }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-2">
                                    <BaseBadge :variant="item.status === 'dibatalkan' ? 'danger' : 'success'">
                                        {{ item.status ?? 'selesai' }}
                                    </BaseBadge>
                                </td>
                                <td class="whitespace-nowrap px-3 py-2 text-right font-semibold tabular-nums text-neutral-900">
                                    {{ formatCurrency(item.total_belanja) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Blok Tanda Tangan (khusus Cetak PDF) -->
            <div class="hidden pt-8 text-xs text-neutral-800 print:grid print:grid-cols-2 print:gap-12">
                <div class="text-center">
                    <p>Dibuat Oleh (Kasir),</p>
                    <div class="h-16" />
                    <p class="font-semibold underline">{{ user?.name || '-' }}</p>
                </div>
                <div class="text-center">
                    <p>Mengetahui (Administrator / Supervisor),</p>
                    <div class="h-16" />
                    <p class="font-semibold underline">__________________________</p>
                </div>
            </div>
        </div>
    </component>
</template>
