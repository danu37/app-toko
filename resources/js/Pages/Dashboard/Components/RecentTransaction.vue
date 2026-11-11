<script setup>
import { Link } from '@inertiajs/vue3';
import BaseBadge from '@/Components/UI/BaseBadge.vue';
import BaseTable from '@/Components/UI/BaseTable.vue';
import EmptyState from '@/Components/Feedback/EmptyState.vue';
import { useCurrency } from '@/Composables/useCurrency';
import { useDate } from '@/Composables/useDate';

defineProps({
    transactions: {
        type: Array,
        default: () => [],
    },
});

const { formatCurrency } = useCurrency();
const { formatDate } = useDate();

const headers = [
    { key: 'kode', label: 'Kode / ID' },
    { key: 'tanggal', label: 'Tanggal' },
    { key: 'pelanggan', label: 'Pelanggan' },
    { key: 'kasir', label: 'Kasir / Cabang' },
    { key: 'status', label: 'Status' },
    { key: 'total', label: 'Total Belanja', align: 'right' },
    { key: 'aksi', label: 'Aksi', align: 'right' },
];

const statusVariant = (status) => {
    if (status === 'dibatalkan') return 'danger';
    if (status === 'pending') return 'warning';
    return 'success';
};
</script>

<template>
    <div v-if="transactions.length === 0">
        <EmptyState
            title="Belum ada transaksi terbaru"
            description="Transaksi yang dicatat kasir akan muncul secara otomatis di sini."
        />
    </div>

    <BaseTable v-else :headers="headers">
        <tr
            v-for="item in transactions"
            :key="item.id_transaksi"
            class="transition hover:bg-neutral-50/70"
        >
            <td class="whitespace-nowrap px-4 py-3 font-medium text-neutral-900">
                {{ item.kode_transaksi || `#TRX-${item.id_transaksi}` }}
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-neutral-600">
                {{ formatDate(item.tanggal) }}
            </td>
            <td class="px-4 py-3 text-neutral-800">
                {{ item.pelanggan?.nama_pelanggan ?? 'Pelanggan Umum' }}
            </td>
            <td class="px-4 py-3 text-xs text-neutral-500">
                <div>{{ item.nama_kasir ?? item.kasir?.name ?? '-' }}</div>
                <div>{{ item.cabang?.nama_cabang ?? '-' }}</div>
            </td>
            <td class="whitespace-nowrap px-4 py-3">
                <BaseBadge :variant="statusVariant(item.status)">
                    {{ item.status ?? 'selesai' }}
                </BaseBadge>
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-neutral-900">
                {{ formatCurrency(item.total_belanja) }}
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-right">
                <Link
                    :href="route('transaksi.show', item.id_transaksi)"
                    class="text-xs font-medium text-neutral-700 hover:text-neutral-900 hover:underline"
                >
                    Detail
                </Link>
            </td>
        </tr>
    </BaseTable>
</template>

