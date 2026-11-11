<script setup>
import { Link } from '@inertiajs/vue3';
import BaseBadge from '@/Components/UI/BaseBadge.vue';
import EmptyState from '@/Components/Feedback/EmptyState.vue';
import { useCurrency } from '@/Composables/useCurrency';

defineProps({
    products: {
        type: Array,
        default: () => [],
    },
});

const { formatCurrency } = useCurrency();
</script>

<template>
    <section class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between gap-2 border-b border-neutral-100 pb-4">
            <div>
                <h3 class="text-base font-semibold text-neutral-900">Produk Penjualan Tertinggi</h3>
                <p class="mt-0.5 text-xs text-neutral-500">Berdasarkan jumlah unit terjual dari detail transaksi.</p>
            </div>
            <Link
                :href="route('laporan.produk')"
                class="text-xs font-medium text-neutral-700 hover:text-neutral-900 hover:underline"
            >
                Detail &rarr;
            </Link>
        </div>

        <div v-if="products.length === 0" class="pt-4">
            <EmptyState
                title="Belum ada produk terjual"
                description="Peringkat produk terlaris muncul setelah detail transaksi tercatat."
            />
        </div>

        <ul v-else class="mt-3 divide-y divide-neutral-100">
            <li
                v-for="(item, index) in products"
                :key="`${item.nama_produk}-${index}`"
                class="flex items-center justify-between gap-3 py-3 first:pt-1 last:pb-1"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <span
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-xs font-semibold"
                        :class="index === 0 ? 'bg-neutral-900 text-white' : 'bg-neutral-100 text-neutral-700'"
                    >
                        #{{ index + 1 }}
                    </span>

                    <div class="min-w-0">
                        <Link
                            v-if="item.id_produk"
                            :href="route('produk.show', item.id_produk)"
                            class="block truncate text-sm font-medium text-neutral-900 hover:underline"
                        >
                            {{ item.nama_produk }}
                        </Link>
                        <p v-else class="truncate text-sm font-medium text-neutral-900">
                            {{ item.nama_produk }}
                        </p>

                        <div class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-neutral-500">
                            <span>{{ item.total_transaksi }} transaksi</span>
                            <span v-if="item.stok !== null">&bull; Sisa stok {{ item.stok }}</span>
                        </div>
                    </div>
                </div>

                <div class="shrink-0 text-right">
                    <BaseBadge variant="dark">{{ item.total_terjual }} terjual</BaseBadge>
                    <p class="mt-1 text-xs font-semibold text-neutral-800">
                        {{ formatCurrency(item.total_pendapatan) }}
                    </p>
                </div>
            </li>
        </ul>
    </section>
</template>

