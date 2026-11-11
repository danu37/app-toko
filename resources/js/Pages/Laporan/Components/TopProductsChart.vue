<script setup>
import { computed } from 'vue';
import EmptyState from '@/Components/Feedback/EmptyState.vue';
import { useCurrency } from '@/Composables/useCurrency';

const props = defineProps({
    products: {
        type: Array,
        default: () => [],
    },
    metric: {
        type: String,
        default: 'qty',
        validator: (val) => ['qty', 'revenue'].includes(val),
    },
});

const { formatCurrency } = useCurrency();

const maxMetricValue = computed(() => {
    const values = props.products.map((item) =>
        props.metric === 'revenue' ? Number(item.total_pendapatan) || 0 : Number(item.total_terjual) || 0,
    );
    const max = Math.max(...values, 0);
    return max > 0 ? max : 1;
});

const widthPercent = (item) => {
    const val =
        props.metric === 'revenue' ? Number(item.total_pendapatan) || 0 : Number(item.total_terjual) || 0;
    if (val <= 0) return 0;
    return Math.max(6, Math.round((val / maxMetricValue.value) * 100));
};
</script>

<template>
    <section class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
        <div class="border-b border-neutral-100 pb-4">
            <h3 class="text-base font-semibold text-neutral-900">Grafik Produk Terlaris (Top 10)</h3>
            <p class="mt-0.5 text-xs text-neutral-500">
                Perbandingan {{ metric === 'revenue' ? 'pendapatan' : 'jumlah unit terjual' }} dari transaksi non-batal di database.
            </p>
        </div>

        <div v-if="products.length === 0" class="py-4">
            <EmptyState
                title="Belum ada produk terjual"
                description="Grafik terisi otomatis setelah item transaksi tercatat sesuai filter."
            />
        </div>

        <div v-else class="mt-4 space-y-3.5">
            <div
                v-for="(item, index) in products"
                :key="`${item.nama_produk}-${index}`"
                class="space-y-1.5"
            >
                <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                    <div class="flex min-w-0 items-center gap-2">
                        <span
                            class="inline-flex h-5 w-6 shrink-0 items-center justify-center rounded font-semibold"
                            :class="index === 0 ? 'bg-neutral-900 text-white' : 'bg-neutral-100 text-neutral-700'"
                        >
                            #{{ index + 1 }}
                        </span>
                        <span class="truncate font-medium text-neutral-900">{{ item.nama_produk }}</span>
                        <span v-if="item.kategori" class="hidden text-neutral-400 sm:inline">
                            &bull; {{ item.kategori }}
                        </span>
                    </div>

                    <div class="shrink-0 font-medium tabular-nums text-neutral-700">
                        <span class="font-semibold text-neutral-900">{{ item.total_terjual }} unit</span>
                        <span class="text-neutral-400"> &bull; </span>
                        <span>{{ formatCurrency(item.total_pendapatan) }}</span>
                    </div>
                </div>

                <div class="h-2.5 w-full overflow-hidden rounded-full bg-neutral-100">
                    <div
                        class="h-full rounded-full transition-all duration-300"
                        :class="index === 0 ? 'bg-neutral-900' : 'bg-neutral-500'"
                        :style="{ width: `${widthPercent(item)}%` }"
                    />
                </div>
            </div>
        </div>
    </section>
</template>

