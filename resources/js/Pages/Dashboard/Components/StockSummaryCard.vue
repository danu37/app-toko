<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import BaseBadge from '@/Components/UI/BaseBadge.vue';
import EmptyState from '@/Components/Feedback/EmptyState.vue';

const props = defineProps({
    stockSummary: {
        type: Object,
        default: null,
    },
});

const hasCriticalStock = computed(
    () => (props.stockSummary?.low_stock_products ?? []).length > 0,
);

const displayedProducts = computed(() =>
    hasCriticalStock.value
        ? props.stockSummary?.low_stock_products ?? []
        : props.stockSummary?.lowest_stock_products ?? [],
);
</script>

<template>
    <section class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between gap-2 border-b border-neutral-100 pb-4">
            <div>
                <h3 class="text-base font-semibold text-neutral-900">Ringkasan Stok</h3>
                <p class="mt-0.5 text-xs text-neutral-500">
                    Total {{ stockSummary?.total_unit ?? 0 }} unit di {{ stockSummary?.total_sku ?? 0 }} SKU.
                </p>
            </div>
            <Link
                :href="route('produk.index')"
                class="text-xs font-medium text-neutral-700 hover:text-neutral-900 hover:underline"
            >
                Kelola &rarr;
            </Link>
        </div>

        <div v-if="!stockSummary" class="pt-4">
            <EmptyState title="Data stok tidak tersedia" description="Ringkasan stok belum dimuat." />
        </div>

        <div v-else class="mt-4 space-y-4">
            <!-- Stock Health Tiles -->
            <div class="grid grid-cols-3 gap-2 text-center">
                <div class="rounded-lg border border-emerald-200 bg-emerald-50/70 px-1.5 py-2.5">
                    <p class="text-lg font-bold text-emerald-800">{{ stockSummary.stok_aman }}</p>
                    <p class="whitespace-nowrap text-[11px] font-medium text-emerald-700">Aman (&gt;5)</p>
                </div>
                <div class="rounded-lg border border-amber-200 bg-amber-50/70 px-1.5 py-2.5">
                    <p class="text-lg font-bold text-amber-800">{{ stockSummary.stok_menipis }}</p>
                    <p class="whitespace-nowrap text-[11px] font-medium text-amber-700">Menipis (1–5)</p>
                </div>
                <div class="rounded-lg border border-red-200 bg-red-50/70 px-1.5 py-2.5">
                    <p class="text-lg font-bold text-red-800">{{ stockSummary.stok_habis }}</p>
                    <p class="whitespace-nowrap text-[11px] font-medium text-red-700">Habis (0)</p>
                </div>
            </div>

            <!-- Attention or Watchlist -->
            <div>
                <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-neutral-500">
                    {{ hasCriticalStock ? 'Perlu Restok Segera (<= 5 unit)' : 'Pantauan Stok Terendah (Kondisi Aman)' }}
                </p>

                <div v-if="displayedProducts.length === 0" class="rounded-lg bg-neutral-50 p-3 text-xs text-neutral-500">
                    Belum ada produk terdaftar di katalog.
                </div>

                <ul v-else class="divide-y divide-neutral-100 rounded-lg border border-neutral-100 bg-neutral-50/40">
                    <li
                        v-for="item in displayedProducts"
                        :key="item.id_produk"
                        class="flex items-center justify-between gap-2 px-3 py-2 text-xs"
                    >
                        <div class="min-w-0">
                            <Link
                                :href="route('produk.show', item.id_produk)"
                                class="block truncate font-medium text-neutral-900 hover:underline"
                            >
                                {{ item.nama_produk }}
                            </Link>
                            <span class="text-[11px] text-neutral-500">
                                {{ item.kategori || 'Tanpa Kategori' }}
                            </span>
                        </div>
                        <BaseBadge :variant="item.stok <= 0 ? 'danger' : item.stok <= 5 ? 'warning' : 'neutral'">
                            {{ item.stok <= 0 ? 'Habis (0)' : `${item.stok} unit` }}
                        </BaseBadge>
                    </li>
                </ul>
            </div>
        </div>
    </section>
</template>
