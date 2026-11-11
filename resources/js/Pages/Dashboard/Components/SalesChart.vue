<script setup>
import { computed, ref, watch } from 'vue';
import EmptyState from '@/Components/Feedback/EmptyState.vue';
import { useCurrency } from '@/Composables/useCurrency';

const props = defineProps({
    salesChart: {
        type: Object,
        default: () => ({ daily: [], monthly: [] }),
    },
    series: {
        type: Array,
        default: null,
    },
    title: {
        type: String,
        default: 'Grafik Penjualan',
    },
    subtitle: {
        type: String,
        default: 'Pendapatan dari transaksi non-batal pada database.',
    },
    showModeToggle: {
        type: Boolean,
        default: true,
    },
});

const { formatCurrency } = useCurrency();
const mode = ref('daily');
const hoveredIndex = ref(null);

const activeSeries = computed(() => {
    if (Array.isArray(props.series)) {
        return props.series;
    }
    return mode.value === 'monthly'
        ? props.salesChart?.monthly ?? []
        : props.salesChart?.daily ?? [];
});

watch(activeSeries, () => {
    hoveredIndex.value = activeSeries.value.length > 0 ? activeSeries.value.length - 1 : null;
}, { immediate: true });

const maxValue = computed(() => {
    const vals = activeSeries.value.map((d) => Number(d.total_pendapatan) || 0);
    const max = Math.max(...vals, 0);
    return max > 0 ? max : 1;
});

const totalPeriodRevenue = computed(() =>
    activeSeries.value.reduce((acc, item) => acc + (Number(item.total_pendapatan) || 0), 0),
);

const totalPeriodTransactions = computed(() =>
    activeSeries.value.reduce((acc, item) => acc + (Number(item.total_transaksi) || 0), 0),
);

const selectedPoint = computed(() => {
    if (activeSeries.value.length === 0) return null;
    if (hoveredIndex.value !== null && activeSeries.value[hoveredIndex.value]) {
        return activeSeries.value[hoveredIndex.value];
    }
    return activeSeries.value[activeSeries.value.length - 1];
});

const barHeightPercent = (val) => {
    const num = Number(val) || 0;
    if (num <= 0) return 0;
    return Math.max(8, Math.round((num / maxValue.value) * 100));
};

const formatCompact = (value) => {
    const num = Number(value) || 0;
    if (num >= 1_000_000) {
        return `Rp ${(num / 1_000_000).toFixed(1).replace('.0', '')} jt`;
    }
    if (num >= 1_000) {
        return `Rp ${Math.round(num / 1_000)} rb`;
    }
    return `Rp ${num}`;
};
</script>

<template>
    <section class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col justify-between gap-3 border-b border-neutral-100 pb-4 sm:flex-row sm:items-center">
            <div>
                <h3 class="text-base font-semibold text-neutral-900">{{ title }}</h3>
                <p class="mt-0.5 text-xs text-neutral-500">{{ subtitle }}</p>
            </div>

            <div
                v-if="showModeToggle && !series"
                class="inline-flex rounded-lg border border-neutral-200 bg-neutral-50 p-0.5 text-xs print:hidden"
                role="tablist"
                aria-label="Periode grafik penjualan"
            >
                <button
                    type="button"
                    role="tab"
                    :aria-selected="mode === 'daily'"
                    class="rounded-md px-3 py-1.5 font-medium transition"
                    :class="mode === 'daily' ? 'bg-white text-neutral-900 shadow-sm' : 'text-neutral-600 hover:text-neutral-900'"
                    @click="mode = 'daily'"
                >
                    Harian
                </button>
                <button
                    type="button"
                    role="tab"
                    :aria-selected="mode === 'monthly'"
                    class="rounded-md px-3 py-1.5 font-medium transition"
                    :class="mode === 'monthly' ? 'bg-white text-neutral-900 shadow-sm' : 'text-neutral-600 hover:text-neutral-900'"
                    @click="mode = 'monthly'"
                >
                    Bulanan
                </button>
            </div>
        </div>

        <div v-if="activeSeries.length === 0" class="py-4">
            <EmptyState
                title="Belum ada data grafik penjualan"
                description="Grafik akan otomatis terisi setelah transaksi non-batal tercatat di database."
            />
        </div>

        <div v-else class="mt-4 space-y-4">
            <!-- Interactive Readout & Period Totals -->
            <div class="grid grid-cols-1 gap-3 rounded-xl bg-neutral-50 p-3.5 sm:grid-cols-3">
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-wider text-neutral-500">
                        Periode Disorot
                    </p>
                    <p class="mt-0.5 text-sm font-semibold text-neutral-900">
                        {{ selectedPoint?.full_label || selectedPoint?.label }}
                    </p>
                    <p class="text-xs text-neutral-600">
                        {{ formatCurrency(selectedPoint?.total_pendapatan) }} ({{ selectedPoint?.total_transaksi }} trx)
                    </p>
                </div>
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-wider text-neutral-500">
                        Total Grafik Tampil
                    </p>
                    <p class="mt-0.5 text-sm font-semibold text-neutral-900">
                        {{ formatCurrency(totalPeriodRevenue) }}
                    </p>
                    <p class="text-xs text-neutral-500">{{ activeSeries.length }} titik periode</p>
                </div>
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-wider text-neutral-500">
                        Volume Periode Tampil
                    </p>
                    <p class="mt-0.5 text-sm font-semibold text-neutral-900">
                        {{ totalPeriodTransactions }} transaksi
                    </p>
                    <p class="text-xs text-neutral-500">Puncak: {{ formatCompact(maxValue) }}</p>
                </div>
            </div>

            <!-- Minimalist Responsive Bar Chart -->
            <div class="overflow-x-auto pb-1">
                <div class="flex min-w-[520px] items-end gap-2 pt-3">
                    <!-- Y-Axis Reference -->
                    <div class="flex h-44 w-14 shrink-0 flex-col justify-between pr-2 text-right text-[10px] font-medium text-neutral-400">
                        <span>{{ formatCompact(maxValue) }}</span>
                        <span>{{ formatCompact(maxValue / 2) }}</span>
                        <span>Rp 0</span>
                    </div>

                    <!-- Bars Grid -->
                    <div class="relative flex h-44 flex-1 items-end gap-1 border-b border-l border-neutral-200 px-1.5 pt-2">
                        <!-- Subtle horizontal guide lines -->
                        <div class="pointer-events-none absolute inset-x-0 top-1/2 border-t border-dashed border-neutral-200/80" />

                        <button
                            v-for="(item, idx) in activeSeries"
                            :key="item.key || item.label"
                            type="button"
                            class="group relative z-10 flex h-full flex-1 flex-col items-center justify-end focus:outline-none"
                            :aria-label="`${item.full_label || item.label}: ${formatCurrency(item.total_pendapatan)} (${item.total_transaksi} transaksi)`"
                            @mouseenter="hoveredIndex = idx"
                            @focus="hoveredIndex = idx"
                            @click="hoveredIndex = idx"
                        >
                            <div class="flex w-full max-w-[38px] flex-1 items-end">
                                <div
                                    class="w-full rounded-t-md transition-all duration-200"
                                    :class="
                                        hoveredIndex === idx
                                            ? 'bg-neutral-900'
                                            : 'bg-neutral-300 group-hover:bg-neutral-600'
                                    "
                                    :style="{ height: `${barHeightPercent(item.total_pendapatan)}%` }"
                                />
                            </div>
                        </button>
                    </div>
                </div>

                <!-- X-Axis Labels aligned to bar area -->
                <div class="mt-2 flex min-w-[520px] items-start gap-2">
                    <div class="w-14 shrink-0" aria-hidden="true" />
                    <div class="flex flex-1 gap-1 px-1.5">
                        <span
                            v-for="(item, idx) in activeSeries"
                            :key="`${item.key || item.label}-label`"
                            class="flex-1 truncate text-center text-[10px] tracking-tight"
                            :class="hoveredIndex === idx ? 'font-semibold text-neutral-900' : 'text-neutral-500'"
                        >
                            {{ item.label }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
