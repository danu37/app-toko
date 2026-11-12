<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CashierLayout from '@/Layouts/CashierLayout.vue';
import BaseBadge from '@/Components/UI/BaseBadge.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BasePagination from '@/Components/UI/BasePagination.vue';
import BaseTable from '@/Components/UI/BaseTable.vue';
import TableAction from '@/Components/Tables/TableAction.vue';
import ConfirmDialog from '@/Components/Feedback/ConfirmDialog.vue';
import EmptyState from '@/Components/Feedback/EmptyState.vue';
import StatCard from '@/Pages/Dashboard/Components/StatCard.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { useCurrency } from '@/Composables/useCurrency';
import { useDate } from '@/Composables/useDate';

const props = defineProps({
    customer: {
        type: Object,
        required: true,
    },
    customerSummary: {
        type: Object,
        required: true,
    },
    transactions: {
        type: Object,
        required: true,
    },
});

const { can, isAdmin } = usePermissions();
const { formatCurrency } = useCurrency();
const { formatDate } = useDate();

const confirmDeleteOpen = ref(false);
const deleting = ref(false);

const headers = [
    { key: 'kode', label: 'Kode / ID' },
    { key: 'tanggal', label: 'Tanggal' },
    { key: 'cabang', label: 'Cabang & Kasir' },
    { key: 'metode', label: 'Metode' },
    { key: 'status', label: 'Status' },
    { key: 'total', label: 'Total Belanja', align: 'right' },
    { key: 'aksi', label: 'Aksi', align: 'right' },
];

const executeDelete = () => {
    deleting.value = true;
    router.delete(route('pelanggan.destroy', props.customer.id_pelanggan), {
        onFinish: () => {
            deleting.value = false;
            confirmDeleteOpen.value = false;
        },
    });
};
</script>

<template>
    <Head :title="`Detail Pelanggan: ${customer.nama_pelanggan}`" />

    <component
        :is="isAdmin ? AdminLayout : CashierLayout"
        :title="`Detail Pelanggan: ${customer.nama_pelanggan}`"
    >
        <div class="space-y-6">
            <!-- Top Actions & Profile Card -->
            <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-neutral-100 pb-4">
                    <BaseButton :href="route('pelanggan.index')" variant="secondary" size="sm">
                        &larr; Kembali ke Daftar
                    </BaseButton>
                    <div class="flex flex-wrap items-center gap-2">
                        <BaseButton v-if="can.createTransactions" :href="route('transaksi.create')" variant="emerald" size="sm">
                            + Transaksi Baru (POS)
                        </BaseButton>
                        <BaseButton v-if="can.editCustomers" :href="route('pelanggan.edit', customer.id_pelanggan)" size="sm">
                            Edit Pelanggan
                        </BaseButton>
                        <BaseButton
                            v-if="can.deleteCustomers"
                            variant="danger"
                            size="sm"
                            @click="confirmDeleteOpen = true"
                        >
                            Hapus
                        </BaseButton>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <p class="text-xs text-neutral-500">Nama Pelanggan</p>
                        <p class="mt-0.5 text-base font-semibold text-neutral-900">{{ customer.nama_pelanggan }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-neutral-500">Kota</p>
                        <p class="mt-0.5 text-sm font-medium text-neutral-800">{{ customer.kota || '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-neutral-500">Nomor HP</p>
                        <p class="mt-0.5 text-sm font-medium text-neutral-800">{{ customer.no_hp || '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-neutral-500">Alamat Lengkap</p>
                        <p class="mt-0.5 text-sm text-neutral-700">{{ customer.alamat || 'Belum tercatat' }}</p>
                    </div>
                </div>
            </div>

            <!-- Customer Summary Cards -->
            <div
                class="enter-stagger-grid grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"
                style="--stagger-base: 55ms"
            >
                <StatCard
                    label="Total Transaksi"
                    :value="customerSummary.total_transaksi ?? 0"
                    :description="`${customerSummary.total_transaksi_selesai ?? 0} transaksi selesai`"
                    tone="sky"
                />
                <StatCard
                    label="Akumulasi Belanja"
                    :value="formatCurrency(customerSummary.total_belanja)"
                    description="Dari transaksi non-batal"
                    tone="emerald"
                />
                <StatCard
                    label="Rata-Rata Belanja"
                    :value="formatCurrency(customerSummary.rata_rata_belanja)"
                    description="Per transaksi selesai"
                    tone="amber"
                />
                <StatCard
                    label="Transaksi Terakhir"
                    :value="customerSummary.transaksi_terakhir ? formatDate(customerSummary.transaksi_terakhir) : '-'"
                    description="Tanggal transaksi terakhir"
                    tone="neutral"
                />
            </div>

            <!-- Customer Transaction History -->
            <div class="space-y-3">
                <div>
                    <h3 class="text-base font-semibold text-neutral-900">Riwayat Transaksi Pelanggan</h3>
                    <p class="text-xs text-neutral-500">Seluruh transaksi atas nama {{ customer.nama_pelanggan }}.</p>
                </div>

                <BaseTable :headers="headers" :empty="(transactions.data ?? []).length === 0">
                    <template #empty>
                        <EmptyState
                            title="Belum ada riwayat transaksi"
                            description="Pelanggan ini belum memiliki catatan transaksi di sistem."
                        >
                            <template v-if="can.createTransactions" #action>
                                <BaseButton :href="route('transaksi.create')" variant="emerald">
                                    Buat Transaksi Baru
                                </BaseButton>
                            </template>
                        </EmptyState>
                    </template>

                    <tr v-for="item in transactions.data" :key="item.id_transaksi" class="transition hover:bg-neutral-50/70">
                        <td class="whitespace-nowrap px-4 py-3 font-medium text-neutral-900">
                            {{ item.kode_transaksi || `#TRX-${item.id_transaksi}` }}
                            <span class="block text-[11px] font-normal text-neutral-400">
                                {{ item.detail_count ?? 0 }} item
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-neutral-600">
                            {{ formatDate(item.tanggal) }}
                        </td>
                        <td class="px-4 py-3 text-xs text-neutral-500">
                            <div class="font-medium text-neutral-800">{{ item.cabang?.nama_cabang ?? '-' }}</div>
                            <div>{{ item.nama_kasir ?? item.kasir?.name ?? '-' }}</div>
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

            <ConfirmDialog
                v-if="can.deleteCustomers"
                :show="confirmDeleteOpen"
                title="Hapus Pelanggan"
                :message="`Yakin ingin menghapus pelanggan ${customer.nama_pelanggan}? Pelanggan yang sudah memiliki transaksi tidak dapat dihapus.`"
                :loading="deleting"
                @close="confirmDeleteOpen = false"
                @confirm="executeDelete"
            />
        </div>
    </component>
</template>

