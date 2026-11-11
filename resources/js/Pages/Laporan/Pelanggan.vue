<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BasePagination from '@/Components/UI/BasePagination.vue';
import BaseTable from '@/Components/UI/BaseTable.vue';
import TableAction from '@/Components/Tables/TableAction.vue';
import SearchInput from '@/Components/Forms/SearchInput.vue';
import DateRangePicker from '@/Components/Forms/DateRangePicker.vue';
import EmptyState from '@/Components/Feedback/EmptyState.vue';
import StatCard from '@/Pages/Dashboard/Components/StatCard.vue';
import ReportTabs from '@/Pages/Laporan/Components/ReportTabs.vue';
import ReportPrintHeader from '@/Pages/Laporan/Components/ReportPrintHeader.vue';
import { useCurrency } from '@/Composables/useCurrency';

const props = defineProps({
    aggregates: {
        type: Object,
        default: () => ({
            total_pelanggan: 0,
            pelanggan_aktif: 0,
            total_transaksi: 0,
            total_belanja: 0,
        }),
    },
    customers: {
        type: Object,
        required: true,
    },
    storeInfo: {
        type: Object,
        default: () => ({ name: 'TokoKita' }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '', start_date: '', end_date: '' }),
    },
});

const { formatCurrency } = useCurrency();
const search = ref(props.filters.search ?? '');
const startDate = ref(props.filters.start_date ?? '');
const endDate = ref(props.filters.end_date ?? '');
const loading = ref(false);

const headers = [
    { key: 'nama', label: 'Nama Pelanggan' },
    { key: 'kota', label: 'Kota' },
    { key: 'no_hp', label: 'No. HP' },
    { key: 'total_transaksi', label: 'Jumlah Transaksi', align: 'right' },
    { key: 'total_belanja', label: 'Akumulasi Belanja', align: 'right' },
    { key: 'aksi', label: 'Aksi', align: 'right' },
];

const hasActiveFilter = computed(() => Boolean(search.value || startDate.value || endDate.value));

const activeQuery = computed(() => {
    const query = {};
    if (search.value) query.search = search.value;
    if (startDate.value) query.start_date = startDate.value;
    if (endDate.value) query.end_date = endDate.value;
    return query;
});

const exportUrl = computed(() =>
    route('laporan.export', {
        report: 'customers',
        ...activeQuery.value,
    }),
);

const printFilterSummary = computed(() => {
    const parts = [];
    if (search.value) parts.push(`Pencarian: "${search.value}"`);
    return parts;
});

const applyFilters = () => {
    loading.value = true;
    router.get(route('laporan.pelanggan'), activeQuery.value, {
        preserveState: true,
        replace: true,
        onFinish: () => {
            loading.value = false;
        },
    });
};

const resetFilters = () => {
    search.value = '';
    startDate.value = '';
    endDate.value = '';
    applyFilters();
};

const printReport = () => {
    window.print();
};
</script>

<template>
    <Head title="Laporan Pelanggan" />

    <AdminLayout title="Laporan Aktivitas Pelanggan">
        <div class="space-y-6">
            <ReportPrintHeader
                title="Laporan Aktivitas Pelanggan"
                :store-name="storeInfo?.name || 'TokoKita'"
                :start-date="startDate"
                :end-date="endDate"
                :summary-parts="printFilterSummary"
            />

            <ReportTabs>
                <template #actions>
                    <BaseButton :href="exportUrl" external variant="secondary" size="sm">
                        Export CSV
                    </BaseButton>
                    <BaseButton variant="secondary" size="sm" @click="printReport">
                        Cetak Laporan
                    </BaseButton>
                </template>
            </ReportTabs>

            <form
                class="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm print:hidden"
                @submit.prevent="applyFilters"
            >
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-12">
                    <div class="sm:col-span-2 lg:col-span-4">
                        <label for="customer_search" class="mb-1.5 block text-sm font-medium text-neutral-700">
                            Pencarian Pelanggan
                        </label>
                        <SearchInput
                            id="customer_search"
                            v-model="search"
                            placeholder="Cari nama pelanggan atau kota..."
                            @search="applyFilters"
                        />
                    </div>
                    <div class="sm:col-span-2 lg:col-span-5">
                        <DateRangePicker
                            :start-date="startDate"
                            :end-date="endDate"
                            @update:start-date="startDate = $event"
                            @update:end-date="endDate = $event"
                        />
                    </div>
                    <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-3">
                        <BaseButton type="submit">Terapkan Filter</BaseButton>
                        <BaseButton v-if="hasActiveFilter" type="button" variant="secondary" @click="resetFilters">
                            Reset
                        </BaseButton>
                    </div>
                </div>
            </form>

            <!-- Customer Summary KPIs -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    label="Pelanggan Tercatat"
                    :value="aggregates.total_pelanggan ?? 0"
                    description="Sesuai pencarian aktif"
                    tone="sky"
                />
                <StatCard
                    label="Pelanggan Bertransaksi"
                    :value="aggregates.pelanggan_aktif ?? 0"
                    description="Memiliki transaksi non-batal"
                    tone="amber"
                />
                <StatCard
                    label="Total Transaksi Pelanggan"
                    :value="`${aggregates.total_transaksi ?? 0}x`"
                    description="Pada periode yang dipilih"
                    tone="neutral"
                />
                <StatCard
                    label="Total Akumulasi Belanja"
                    :value="formatCurrency(aggregates.total_belanja)"
                    description="Akumulasi transaksi non-batal"
                    tone="emerald"
                />
            </div>

            <BaseTable :headers="headers" :loading="loading" :empty="(customers.data ?? []).length === 0">
                <template #empty>
                    <EmptyState
                        title="Data laporan pelanggan kosong"
                        description="Tidak ada pelanggan yang sesuai dengan filter pencarian."
                    />
                </template>
                <tr v-for="item in customers.data" :key="item.id_pelanggan" class="hover:bg-neutral-50/70">
                    <td class="px-4 py-3 font-medium text-neutral-900">
                        <Link :href="route('pelanggan.show', item.id_pelanggan)" class="hover:underline">
                            {{ item.nama_pelanggan }}
                        </Link>
                    </td>
                    <td class="px-4 py-3 text-neutral-700">{{ item.kota }}</td>
                    <td class="px-4 py-3 text-neutral-600">{{ item.no_hp || '-' }}</td>
                    <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-neutral-700">
                        {{ item.total_transaksi ?? 0 }}x
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-neutral-900">
                        {{ formatCurrency(item.total_belanja) }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-right print:hidden">
                        <TableAction :href="route('pelanggan.show', item.id_pelanggan)" variant="primary">
                            Detail
                        </TableAction>
                    </td>
                </tr>

                <template #footer>
                    <BasePagination :meta="customers" :links="customers.links" class="print:hidden" />
                </template>
            </BaseTable>
        </div>
    </AdminLayout>
</template>
