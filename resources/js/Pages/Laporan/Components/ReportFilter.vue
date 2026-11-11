<script setup>
import { computed } from 'vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BaseSelect from '@/Components/UI/BaseSelect.vue';
import DateRangePicker from '@/Components/Forms/DateRangePicker.vue';
import SearchInput from '@/Components/Forms/SearchInput.vue';

const props = defineProps({
    search: {
        type: String,
        default: '',
    },
    showSearch: {
        type: Boolean,
        default: false,
    },
    searchLabel: {
        type: String,
        default: 'Pencarian',
    },
    searchPlaceholder: {
        type: String,
        default: 'Cari data...',
    },
    startDate: {
        type: String,
        default: '',
    },
    endDate: {
        type: String,
        default: '',
    },
    cabang: {
        type: [String, Number],
        default: '',
    },
    showBranch: {
        type: Boolean,
        default: true,
    },
    pelanggan: {
        type: [String, Number],
        default: '',
    },
    showCustomer: {
        type: Boolean,
        default: true,
    },
    kasir: {
        type: [String, Number],
        default: '',
    },
    showCashier: {
        type: Boolean,
        default: false,
    },
    kategori: {
        type: [String, Number],
        default: '',
    },
    showCategory: {
        type: Boolean,
        default: false,
    },
    status: {
        type: String,
        default: '',
    },
    showStatus: {
        type: Boolean,
        default: false,
    },
    sort: {
        type: String,
        default: 'qty',
    },
    showSort: {
        type: Boolean,
        default: false,
    },
    branches: {
        type: Array,
        default: () => [],
    },
    customers: {
        type: Array,
        default: () => [],
    },
    cashiers: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits([
    'update:search',
    'update:startDate',
    'update:endDate',
    'update:cabang',
    'update:pelanggan',
    'update:kasir',
    'update:kategori',
    'update:status',
    'update:sort',
    'apply',
    'reset',
]);

const branchOptions = computed(() =>
    props.branches.map((b) => ({
        value: b.id_cabang,
        label: b.kota ? `${b.nama_cabang} (${b.kota})` : b.nama_cabang,
    })),
);

const customerOptions = computed(() =>
    props.customers.map((c) => ({
        value: c.id_pelanggan,
        label: c.kota ? `${c.nama_pelanggan} (${c.kota})` : c.nama_pelanggan,
    })),
);

const cashierOptions = computed(() =>
    props.cashiers.map((u) => ({
        value: u.id,
        label: `${u.name} (${u.role === 'admin' ? 'Admin' : 'Kasir'})`,
    })),
);

const categoryOptions = computed(() =>
    props.categories.map((cat) => ({
        value: cat.id_kategori,
        label: cat.nama_kategori,
    })),
);

const statusOptions = [
    { value: 'selesai', label: 'Selesai' },
    { value: 'dibatalkan', label: 'Dibatalkan' },
];

const sortOptions = [
    { value: 'qty', label: 'Qty Terbanyak' },
    { value: 'revenue', label: 'Omzet Tertinggi' },
];

const hasActiveFilter = computed(() =>
    Boolean(
        props.search ||
            props.startDate ||
            props.endDate ||
            props.cabang ||
            props.pelanggan ||
            props.kasir ||
            props.kategori ||
            props.status ||
            (props.showSort && props.sort && props.sort !== 'qty'),
    ),
);
</script>

<template>
    <form
        class="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm print:hidden"
        @submit.prevent="emit('apply')"
    >
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-12">
            <div v-if="showSearch" class="sm:col-span-2 lg:col-span-6">
                <label class="mb-1.5 block text-sm font-medium text-neutral-700">
                    {{ searchLabel }}
                </label>
                <SearchInput
                    :model-value="search"
                    :placeholder="searchPlaceholder"
                    @update:model-value="emit('update:search', $event)"
                    @search="emit('apply')"
                />
            </div>

            <div class="sm:col-span-2 lg:col-span-6">
                <DateRangePicker
                    :start-date="startDate"
                    :end-date="endDate"
                    @update:start-date="emit('update:startDate', $event)"
                    @update:end-date="emit('update:endDate', $event)"
                />
            </div>

            <div v-if="showBranch" class="lg:col-span-3">
                <BaseSelect
                    id="filter_cabang"
                    :model-value="cabang"
                    label="Cabang"
                    placeholder="Semua Cabang"
                    :options="branchOptions"
                    @update:model-value="emit('update:cabang', $event)"
                />
            </div>

            <div v-if="showCustomer" class="lg:col-span-3">
                <BaseSelect
                    id="filter_pelanggan"
                    :model-value="pelanggan"
                    label="Pelanggan"
                    placeholder="Semua Pelanggan"
                    :options="customerOptions"
                    @update:model-value="emit('update:pelanggan', $event)"
                />
            </div>

            <div v-if="showCashier" class="lg:col-span-3">
                <BaseSelect
                    id="filter_kasir"
                    :model-value="kasir"
                    label="Kasir / Staff"
                    placeholder="Semua Kasir"
                    :options="cashierOptions"
                    @update:model-value="emit('update:kasir', $event)"
                />
            </div>

            <div v-if="showCategory" class="lg:col-span-3">
                <BaseSelect
                    id="filter_kategori"
                    :model-value="kategori"
                    label="Kategori"
                    placeholder="Semua Kategori"
                    :options="categoryOptions"
                    @update:model-value="emit('update:kategori', $event)"
                />
            </div>

            <div v-if="showStatus" class="lg:col-span-3">
                <BaseSelect
                    id="filter_status"
                    :model-value="status"
                    label="Status"
                    placeholder="Semua Status"
                    :options="statusOptions"
                    @update:model-value="emit('update:status', $event)"
                />
            </div>

            <div v-if="showSort" class="lg:col-span-3">
                <BaseSelect
                    id="filter_sort"
                    :model-value="sort"
                    label="Urutkan"
                    placeholder=""
                    :options="sortOptions"
                    @update:model-value="emit('update:sort', $event)"
                />
            </div>

            <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-3">
                <BaseButton type="submit">Terapkan Filter</BaseButton>
                <BaseButton v-if="hasActiveFilter" type="button" variant="secondary" @click="emit('reset')">
                    Reset
                </BaseButton>
            </div>
        </div>
    </form>
</template>
