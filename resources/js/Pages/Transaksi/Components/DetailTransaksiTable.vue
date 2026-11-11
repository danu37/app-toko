<script setup>
import BaseTable from '@/Components/UI/BaseTable.vue';
import EmptyState from '@/Components/Feedback/EmptyState.vue';
import { useCurrency } from '@/Composables/useCurrency';

defineProps({
    details: {
        type: Array,
        default: () => [],
    },
});

const { formatCurrency } = useCurrency();

const headers = [
    { key: 'nama', label: 'Nama Produk' },
    { key: 'harga', label: 'Harga Satuan', align: 'right' },
    { key: 'jumlah', label: 'Jumlah', align: 'right' },
    { key: 'subtotal', label: 'Subtotal', align: 'right' },
];
</script>

<template>
    <EmptyState
        v-if="details.length === 0"
        title="Detail barang tidak tersedia"
        description="Transaksi ini tidak memiliki baris detail (atau merupakan data historis sebelum rincian item tercatat)."
    />

    <BaseTable v-else :headers="headers">
        <tr v-for="row in details" :key="row.id_detail" class="hover:bg-neutral-50/70">
            <td class="px-4 py-3 font-medium text-neutral-900">
                {{ row.nama_produk }}
                <span v-if="row.produk?.kode_produk" class="ml-1 text-xs text-neutral-400">
                    ({{ row.produk.kode_produk }})
                </span>
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-right text-neutral-700">
                {{ formatCurrency(row.harga) }}
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-right text-neutral-700">
                {{ row.jumlah }}
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-neutral-900">
                {{ formatCurrency(row.subtotal) }}
            </td>
        </tr>
    </BaseTable>
</template>

