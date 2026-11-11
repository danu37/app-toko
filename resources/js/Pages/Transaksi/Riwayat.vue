<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CashierLayout from '@/Layouts/CashierLayout.vue';
import BaseBadge from '@/Components/UI/BaseBadge.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BasePagination from '@/Components/UI/BasePagination.vue';
import BaseSelect from '@/Components/UI/BaseSelect.vue';
import BaseTable from '@/Components/UI/BaseTable.vue';
import TableAction from '@/Components/Tables/TableAction.vue';
import SearchInput from '@/Components/Forms/SearchInput.vue';
import DateRangePicker from '@/Components/Forms/DateRangePicker.vue';
import EmptyState from '@/Components/Feedback/EmptyState.vue';
import TransaksiTabs from '@/Pages/Transaksi/Components/TransaksiTabs.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { useCurrency } from '@/Composables/useCurrency';
import { useDate } from '@/Composables/useDate';

const props = defineProps({
    transactions: {
        type: Object,
        required: true,
    },
    branches: {
        type: Array,
        default: () => [],
    },
    cashiers: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', start_date: '', end_date: '', cabang: '', kasir: '', status: '' }),
    },
});

const { isAdmin } = usePermissions();
const { formatCurrency } = useCurrency();
const { formatDate } = useDate();

const search = ref(props.filters.search ?? '');
const startDate = ref(props.filters.start_date ?? '');
const endDate = ref(props.filters.end_date ?? '');
const cabang = ref(props.filters.cabang ?? '');
const kasir = ref(props.filters.kasir ?? '');
const status = ref(props.filters.status ?? '');
const loading = ref(false);

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

const hasActiveFilter = computed(() =>
    Boolean(search.value || startDate.value || endDate.value || cabang.value || kasir.value || status.value),
);

const headers = [
    { key: 'waktu', label: 'Tanggal & Kode' },
    { key: 'pelanggan', label: 'Pelanggan & Cabang' },
    { key: 'items', label: 'Ringkasan Barang' },
    { key: 'status', label: 'Status' },
    { key: 'total', label: 'Total Belanja', align: 'right' },
    { key: 'aksi', label: 'Aksi', align: 'right' },
];

const applyFilters = () => {
    const query = {};
    if (search.value) query.search = search.value;
    if (startDate.value) query.start_date = startDate.value;
    if (endDate.value) query.end_date = endDate.value;
    if (cabang.value) query.cabang = cabang.value;
    if (isAdmin.value && kasir.value) query.kasir = kasir.value;
    if (status.value) query.status = status.value;

    loading.value = true;
    router.get(route('transaksi.riwayat'), query, {
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
    kasir.value = '';
    status.value = '';
    applyFilters();
};
</script>

<template>
    <Head title="Riwayat Transaksi" />

    <component :is="isAdmin ? AdminLayout : CashierLayout" title="Riwayat Transaksi Toko">
        <div class="space-y-5">
            <TransaksiTabs />

            <form
                class="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm print:hidden"
                @submit.prevent="applyFilters"
            >
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-12">
                    <div class="sm:col-span-2 lg:col-span-4">
                        <label for="riwayat_search" class="mb-1.5 block text-sm font-medium text-neutral-700">
                            Pencarian
                        </label>
                        <SearchInput
                            id="riwayat_search"
                            v-model="search"
                            placeholder="Kode, pelanggan, kasir, atau barang..."
                            @search="applyFilters"
                        />
                    </div>

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
                            id="riwayat_cabang"
                            v-model="cabang"
                            label="Cabang"
                            :options="branchOptions"
                            placeholder="Semua Cabang"
                        />
                    </div>

                    <div v-if="isAdmin && cashierOptions.length > 0" class="lg:col-span-2">
                        <BaseSelect
                            id="riwayat_kasir"
                            v-model="kasir"
                            label="Kasir / Staff"
                            :options="cashierOptions"
                            placeholder="Semua Kasir"
                        />
                    </div>

                    <div class="lg:col-span-2">
                        <BaseSelect
                            id="riwayat_status"
                            v-model="status"
                            label="Status"
                            :options="statusOptions"
                            placeholder="Semua"
                        />
                    </div>

                    <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-4">
                        <BaseButton type="submit" variant="secondary">Terapkan Filter</BaseButton>
                        <BaseButton v-if="hasActiveFilter" type="button" variant="ghost" @click="resetFilters">
                            Reset
                        </BaseButton>
                    </div>
                </div>
            </form>

            <BaseTable :headers="headers" :loading="loading" :empty="(transactions.data ?? []).length === 0">
                <template #empty>
                    <EmptyState
                        title="Riwayat transaksi kosong"
                        description="Belum ada riwayat transaksi yang sesuai dengan rentang tanggal atau kata kunci."
                    />
                </template>

                <tr v-for="item in transactions.data" :key="item.id_transaksi" class="transition hover:bg-neutral-50/70">
                    <td class="whitespace-nowrap px-4 py-3">
                        <div class="font-medium text-neutral-900">
                            {{ item.kode_transaksi || `#TRX-${item.id_transaksi}` }}
                        </div>
                        <div class="text-xs text-neutral-500">{{ formatDate(item.tanggal) }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <Link
                            v-if="item.pelanggan?.id_pelanggan"
                            :href="route('pelanggan.show', item.pelanggan.id_pelanggan)"
                            class="font-medium text-neutral-900 hover:underline"
                        >
                            {{ item.pelanggan.nama_pelanggan }}
                        </Link>
                        <span v-else class="font-medium text-neutral-800">Pelanggan Umum</span>
                        <div class="text-xs text-neutral-500">
                            {{ item.cabang?.nama_cabang ?? '-' }} &bull; Kasir: {{ item.nama_kasir ?? item.kasir?.name ?? '-' }}
                        </div>
                    </td>
                    <td class="max-w-xs px-4 py-3">
                        <div v-if="(item.detail ?? []).length > 0" class="flex flex-wrap gap-1">
                            <span
                                v-for="det in item.detail.slice(0, 3)"
                                :key="det.id_detail"
                                class="inline-flex rounded-md bg-neutral-100 px-2 py-0.5 text-[11px] text-neutral-700"
                            >
                                {{ det.nama_produk }} ({{ det.jumlah }}x)
                            </span>
                            <span
                                v-if="item.detail.length > 3"
                                class="inline-flex rounded-md bg-neutral-100 px-2 py-0.5 text-[11px] font-medium text-neutral-500"
                            >
                                +{{ item.detail.length - 3 }} lainnya
                            </span>
                        </div>
                        <span v-else class="text-xs text-neutral-400">Tanpa rincian item</span>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3">
                        <BaseBadge :variant="item.status === 'dibatalkan' ? 'danger' : 'success'">
                            {{ item.status ?? 'selesai' }}
                        </BaseBadge>
                        <span v-if="item.metode_pembayaran" class="mt-1 block text-[10px] uppercase text-neutral-500">
                            {{ item.metode_pembayaran }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-neutral-900">
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
    </component>
</template>
