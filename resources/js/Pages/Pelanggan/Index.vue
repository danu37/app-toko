<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CashierLayout from '@/Layouts/CashierLayout.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BasePagination from '@/Components/UI/BasePagination.vue';
import BaseSelect from '@/Components/UI/BaseSelect.vue';
import BaseTable from '@/Components/UI/BaseTable.vue';
import TableAction from '@/Components/Tables/TableAction.vue';
import SearchInput from '@/Components/Forms/SearchInput.vue';
import ConfirmDialog from '@/Components/Feedback/ConfirmDialog.vue';
import EmptyState from '@/Components/Feedback/EmptyState.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { useCurrency } from '@/Composables/useCurrency';

const props = defineProps({
    customers: {
        type: Object,
        required: true,
    },
    cities: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', kota: '' }),
    },
});

const { can, isAdmin } = usePermissions();
const { formatCurrency } = useCurrency();

const search = ref(props.filters.search ?? '');
const kota = ref(props.filters.kota ?? '');
const loading = ref(false);
const deleteTarget = ref(null);
const deleting = ref(false);

const cityOptions = computed(() =>
    props.cities.map((c) => ({ value: c, label: c })),
);

const hasActiveFilter = computed(() => Boolean(search.value || kota.value));

const headers = [
    { key: 'nama', label: 'Nama Pelanggan' },
    { key: 'kota', label: 'Kota' },
    { key: 'no_hp', label: 'No. HP' },
    { key: 'transaksi', label: 'Transaksi', align: 'right' },
    { key: 'total', label: 'Total Belanja', align: 'right' },
    { key: 'aksi', label: 'Aksi', align: 'right' },
];

const applyFilters = () => {
    const query = {};
    if (search.value) query.search = search.value;
    if (kota.value) query.kota = kota.value;
    loading.value = true;
    router.get(route('pelanggan.index'), query, {
        preserveState: true,
        replace: true,
        onFinish: () => {
            loading.value = false;
        },
    });
};

const resetFilters = () => {
    search.value = '';
    kota.value = '';
    applyFilters();
};

const executeDelete = () => {
    if (!deleteTarget.value) return;
    deleting.value = true;
    router.delete(route('pelanggan.destroy', deleteTarget.value.id_pelanggan), {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            deleteTarget.value = null;
        },
    });
};
</script>

<template>
    <Head title="Daftar Pelanggan" />

    <component :is="isAdmin ? AdminLayout : CashierLayout" title="Daftar Pelanggan">
        <div class="space-y-5">
            <div class="flex flex-col justify-between gap-3 lg:flex-row lg:items-center">
                <form class="flex flex-wrap items-center gap-2" @submit.prevent="applyFilters">
                    <div class="w-full sm:w-64">
                        <SearchInput v-model="search" placeholder="Cari nama, kota, atau nomor HP..." @search="applyFilters" />
                    </div>
                    <div v-if="cityOptions.length > 0" class="w-full sm:w-44">
                        <BaseSelect
                            v-model="kota"
                            :options="cityOptions"
                            placeholder="Semua Kota"
                            aria-label="Filter kota"
                            @change="applyFilters"
                        />
                    </div>
                    <BaseButton type="submit" variant="secondary">Cari</BaseButton>
                    <BaseButton v-if="hasActiveFilter" type="button" variant="ghost" @click="resetFilters">
                        Reset
                    </BaseButton>
                </form>

                <BaseButton v-if="can.createCustomers" :href="route('pelanggan.create')">
                    + Tambah Pelanggan
                </BaseButton>
            </div>

            <BaseTable :headers="headers" :loading="loading" :empty="(customers.data ?? []).length === 0">
                <template #empty>
                    <EmptyState
                        title="Pelanggan belum ditemukan"
                        description="Tambahkan data pelanggan baru untuk mempercepat transaksi di kasir."
                    >
                        <template v-if="can.createCustomers" #action>
                            <BaseButton :href="route('pelanggan.create')">Tambah Pelanggan Baru</BaseButton>
                        </template>
                    </EmptyState>
                </template>
                <tr v-for="item in customers.data" :key="item.id_pelanggan" class="transition hover:bg-neutral-50/70">
                    <td class="px-4 py-3">
                        <div class="font-medium text-neutral-900">{{ item.nama_pelanggan }}</div>
                        <div v-if="item.alamat" class="line-clamp-1 text-xs text-neutral-500">{{ item.alamat }}</div>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-neutral-700">{{ item.kota }}</td>
                    <td class="whitespace-nowrap px-4 py-3 text-neutral-600">{{ item.no_hp || '-' }}</td>
                    <td class="whitespace-nowrap px-4 py-3 text-right text-neutral-700">
                        {{ item.transaksi_count ?? 0 }}x
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-neutral-900">
                        {{ formatCurrency(item.total_belanja_sum ?? 0) }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-right">
                        <div class="inline-flex items-center justify-end gap-1">
                            <TableAction :href="route('pelanggan.show', item.id_pelanggan)">
                                Detail
                            </TableAction>
                            <TableAction
                                v-if="can.editCustomers"
                                :href="route('pelanggan.edit', item.id_pelanggan)"
                                variant="primary"
                            >
                                Edit
                            </TableAction>
                            <TableAction
                                v-if="can.deleteCustomers"
                                variant="danger"
                                @click="deleteTarget = item"
                            >
                                Hapus
                            </TableAction>
                        </div>
                    </td>
                </tr>

                <template #footer>
                    <BasePagination :meta="customers.meta" />
                </template>
            </BaseTable>

            <ConfirmDialog
                v-if="can.deleteCustomers"
                :show="Boolean(deleteTarget)"
                title="Hapus Pelanggan"
                :message="`Yakin ingin menghapus pelanggan ${deleteTarget?.nama_pelanggan ?? ''}?`"
                :loading="deleting"
                @close="deleteTarget = null"
                @confirm="executeDelete"
            />
        </div>
    </component>
</template>
