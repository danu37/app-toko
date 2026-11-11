<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CashierLayout from '@/Layouts/CashierLayout.vue';
import BaseBadge from '@/Components/UI/BaseBadge.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BaseInput from '@/Components/UI/BaseInput.vue';
import BasePagination from '@/Components/UI/BasePagination.vue';
import BaseSelect from '@/Components/UI/BaseSelect.vue';
import BaseTable from '@/Components/UI/BaseTable.vue';
import TableAction from '@/Components/Tables/TableAction.vue';
import SearchInput from '@/Components/Forms/SearchInput.vue';
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
    filters: {
        type: Object,
        default: () => ({ search: '', tanggal: '', cabang: '', status: '', metode: '' }),
    },
});

const { isAdmin } = usePermissions();
const { formatCurrency } = useCurrency();
const { formatDate } = useDate();

const search = ref(props.filters.search ?? '');
const tanggal = ref(props.filters.tanggal ?? '');
const cabang = ref(props.filters.cabang ?? '');
const status = ref(props.filters.status ?? '');
const metode = ref(props.filters.metode ?? '');
const loading = ref(false);

const branchOptions = computed(() =>
    props.branches.map((b) => ({
        value: b.id_cabang,
        label: b.nama_cabang,
    })),
);

const statusOptions = [
    { value: 'selesai', label: 'Selesai' },
    { value: 'dibatalkan', label: 'Dibatalkan' },
];

const methodOptions = [
    { value: 'tunai', label: 'Tunai' },
    { value: 'transfer', label: 'Transfer' },
    { value: 'qris', label: 'QRIS' },
    { value: 'debit', label: 'Debit' },
];

const hasActiveFilter = computed(() =>
    Boolean(search.value || tanggal.value || cabang.value || status.value || metode.value),
);

const headers = [
    { key: 'kode', label: 'Kode / ID' },
    { key: 'tanggal', label: 'Tanggal' },
    { key: 'pelanggan', label: 'Pelanggan' },
    { key: 'kasir', label: 'Kasir & Cabang' },
    { key: 'metode', label: 'Metode' },
    { key: 'status', label: 'Status' },
    { key: 'total', label: 'Total Belanja', align: 'right' },
    { key: 'aksi', label: 'Aksi', align: 'right' },
];

const applyFilters = () => {
    const query = {};
    if (search.value) query.search = search.value;
    if (tanggal.value) query.tanggal = tanggal.value;
    if (cabang.value) query.cabang = cabang.value;
    if (status.value) query.status = status.value;
    if (metode.value) query.metode = metode.value;
    loading.value = true;
    router.get(route('transaksi.index'), query, {
        preserveState: true,
        replace: true,
        onFinish: () => {
            loading.value = false;
        },
    });
};

const resetFilters = () => {
    search.value = '';
    tanggal.value = '';
    cabang.value = '';
    status.value = '';
    metode.value = '';
    applyFilters();
};
</script>

<template>
    <Head title="Daftar Transaksi" />

    <component :is="isAdmin ? AdminLayout : CashierLayout" title="Daftar Transaksi">
        <div class="space-y-5">
            <TransaksiTabs />

            <form
                class="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm"
                @submit.prevent="applyFilters"
            >
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-12">
                    <div class="lg:col-span-4">
                        <SearchInput
                            v-model="search"
                            placeholder="Cari kode atau pelanggan..."
                            @search="applyFilters"
                        />
                    </div>
                    <div class="lg:col-span-2">
                        <BaseInput
                            v-model="tanggal"
                            type="date"
                            aria-label="Filter tanggal transaksi"
                            @change="applyFilters"
                        />
                    </div>
                    <div class="lg:col-span-2">
                        <BaseSelect
                            v-model="cabang"
                            :options="branchOptions"
                            placeholder="Semua Cabang"
                            aria-label="Filter cabang"
                            @change="applyFilters"
                        />
                    </div>
                    <div class="lg:col-span-2">
                        <BaseSelect
                            v-model="metode"
                            :options="methodOptions"
                            placeholder="Semua Metode"
                            aria-label="Filter metode"
                            @change="applyFilters"
                        />
                    </div>
                    <div class="flex items-center gap-2 lg:col-span-2">
                        <BaseButton type="submit" variant="secondary" class="flex-1">Filter</BaseButton>
                        <BaseButton v-if="hasActiveFilter" type="button" variant="ghost" @click="resetFilters">
                            Reset
                        </BaseButton>
                    </div>
                </div>
            </form>

            <BaseTable :headers="headers" :loading="loading" :empty="(transactions.data ?? []).length === 0">
                <template #empty>
                    <EmptyState
                        title="Transaksi belum ditemukan"
                        description="Belum ada transaksi yang sesuai dengan filter Anda."
                    />
                </template>
                <tr v-for="item in transactions.data" :key="item.id_transaksi" class="transition hover:bg-neutral-50/70">
                    <td class="whitespace-nowrap px-4 py-3 font-medium text-neutral-900">
                        {{ item.kode_transaksi || `#TRX-${item.id_transaksi}` }}
                        <span class="block text-[11px] font-normal text-neutral-400">
                            {{ item.detail_count ?? 0 }} item detail
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-neutral-600">
                        {{ formatDate(item.tanggal) }}
                    </td>
                    <td class="px-4 py-3 text-neutral-800">
                        <Link
                            v-if="item.pelanggan?.id_pelanggan"
                            :href="route('pelanggan.show', item.pelanggan.id_pelanggan)"
                            class="font-medium hover:underline"
                        >
                            {{ item.pelanggan.nama_pelanggan }}
                        </Link>
                        <span v-else>{{ item.pelanggan?.nama_pelanggan ?? 'Pelanggan Umum' }}</span>
                    </td>
                    <td class="px-4 py-3 text-xs text-neutral-500">
                        <div>{{ item.nama_kasir ?? item.kasir?.name ?? '-' }}</div>
                        <div>{{ item.cabang?.nama_cabang ?? '-' }}</div>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-xs uppercase text-neutral-600">
                        {{ item.metode_pembayaran || '-' }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-3">
                        <BaseBadge :variant="item.status === 'dibatalkan' ? 'danger' : 'success'">
                            {{ item.status ?? 'selesai' }}
                        </BaseBadge>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-neutral-900">
                        {{ formatCurrency(item.total_belanja) }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-right">
                        <TableAction :href="route('transaksi.show', item.id_transaksi)" variant="primary">
                            Detail
                        </TableAction>
                    </td>
                </tr>

                <template #footer>
                    <BasePagination :meta="transactions.meta" />
                </template>
            </BaseTable>
        </div>
    </component>
</template>
