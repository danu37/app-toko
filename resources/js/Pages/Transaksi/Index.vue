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
import ConfirmDialog from '@/Components/Feedback/ConfirmDialog.vue';
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
        default: () => ({ search: '', tanggal: '', cabang: '', kasir: '', status: '', metode: '' }),
    },
});

const { can, isAdmin } = usePermissions();
const { formatCurrency } = useCurrency();
const { formatDate } = useDate();

const search = ref(props.filters.search ?? '');
const tanggal = ref(props.filters.tanggal ?? '');
const cabang = ref(props.filters.cabang ?? '');
const kasir = ref(props.filters.kasir ?? '');
const status = ref(props.filters.status ?? '');
const metode = ref(props.filters.metode ?? '');
const loading = ref(false);
const deleteTarget = ref(null);
const deleting = ref(false);

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
    { value: 'menunggu_verifikasi', label: 'Menunggu Verifikasi' },
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
    Boolean(search.value || tanggal.value || cabang.value || kasir.value || status.value || metode.value),
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
    if (isAdmin.value && kasir.value) query.kasir = kasir.value;
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
    kasir.value = '';
    status.value = '';
    metode.value = '';
    applyFilters();
};

const executeDelete = () => {
    if (!deleteTarget.value) return;
    deleting.value = true;
    router.delete(route('transaksi.destroy', deleteTarget.value.id_transaksi), {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            deleteTarget.value = null;
        },
    });
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
                    <div class="lg:col-span-3">
                        <SearchInput
                            v-model="search"
                            placeholder="Cari kode, pelanggan, kasir, atau produk..."
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
                    <div v-if="isAdmin && cashierOptions.length > 0" class="lg:col-span-2">
                        <BaseSelect
                            v-model="kasir"
                            :options="cashierOptions"
                            placeholder="Semua Kasir"
                            aria-label="Filter kasir"
                            @change="applyFilters"
                        />
                    </div>
                    <div class="lg:col-span-1">
                        <BaseSelect
                            v-model="metode"
                            :options="methodOptions"
                            placeholder="Metode"
                            aria-label="Filter metode"
                            @change="applyFilters"
                        />
                    </div>
                    <div class="lg:col-span-1">
                        <BaseSelect
                            v-model="status"
                            :options="statusOptions"
                            placeholder="Status"
                            aria-label="Filter status"
                            @change="applyFilters"
                        />
                    </div>
                    <div class="flex items-center gap-2 lg:col-span-1">
                        <BaseButton type="submit" variant="secondary" class="w-full">Filter</BaseButton>
                    </div>
                    <div v-if="hasActiveFilter" class="flex items-center lg:col-span-12">
                        <BaseButton type="button" variant="ghost" size="sm" @click="resetFilters">
                            Reset Filter
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
                        <span v-if="item.bukti_pembayaran_url" class="block text-[10px] font-medium normal-case text-sky-700">
                            Ada Bukti Bayar
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3">
                        <BaseBadge
                            :variant="
                                item.status === 'menunggu_verifikasi'
                                    ? 'warning'
                                    : item.status === 'dibatalkan'
                                      ? 'danger'
                                      : 'success'
                            "
                        >
                            {{
                                item.status === 'menunggu_verifikasi'
                                    ? 'Menunggu Verifikasi'
                                    : item.status ?? 'selesai'
                            }}
                        </BaseBadge>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-neutral-900">
                        {{ formatCurrency(item.total_belanja) }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-right">
                        <div class="inline-flex items-center justify-end gap-1">
                            <TableAction :href="route('transaksi.show', item.id_transaksi)" variant="primary">
                                Detail
                            </TableAction>
                            <TableAction
                                v-if="can.manageTransactions"
                                variant="danger"
                                @click="deleteTarget = item"
                            >
                                Hapus
                            </TableAction>
                        </div>
                    </td>
                </tr>

                <template #footer>
                    <BasePagination :meta="transactions.meta" />
                </template>
            </BaseTable>

            <ConfirmDialog
                v-if="can.manageTransactions"
                :show="Boolean(deleteTarget)"
                title="Hapus Transaksi"
                :message="`Yakin ingin menghapus transaksi ${deleteTarget?.kode_transaksi || `#TRX-${deleteTarget?.id_transaksi ?? ''}`}? Stok produk pada transaksi ini akan dikembalikan.`"
                :loading="deleting"
                @close="deleteTarget = null"
                @confirm="executeDelete"
            />
        </div>
    </component>
</template>
