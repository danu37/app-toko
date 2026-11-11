<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CashierLayout from '@/Layouts/CashierLayout.vue';
import BaseBadge from '@/Components/UI/BaseBadge.vue';
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
    products: {
        type: Object,
        required: true,
    },
    categories: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', kategori: null, status: '' }),
    },
});

const { can, isAdmin } = usePermissions();
const { formatCurrency } = useCurrency();

const search = ref(props.filters.search ?? '');
const kategori = ref(props.filters.kategori ?? '');
const status = ref(props.filters.status ?? '');
const loading = ref(false);
const deleteTarget = ref(null);
const deleting = ref(false);

const statusOptions = [
    { value: 'aktif', label: 'Produk Aktif' },
    { value: 'nonaktif', label: 'Produk Nonaktif' },
    { value: 'menipis', label: 'Stok Menipis (1-5)' },
    { value: 'habis', label: 'Stok Habis (0)' },
];

const hasActiveFilter = computed(() => Boolean(search.value || kategori.value || status.value));

const headers = [
    { key: 'produk', label: 'Barang / Produk' },
    { key: 'kategori', label: 'Kategori' },
    { key: 'harga', label: 'Harga', align: 'right' },
    { key: 'stok', label: 'Stok', align: 'right' },
    { key: 'status', label: 'Status' },
    { key: 'aksi', label: 'Aksi', align: 'right' },
];

const applyFilters = () => {
    const query = {};
    if (search.value) query.search = search.value;
    if (kategori.value) query.kategori = kategori.value;
    if (status.value) query.status = status.value;
    loading.value = true;
    router.get(route('produk.index'), query, {
        preserveState: true,
        replace: true,
        onFinish: () => {
            loading.value = false;
        },
    });
};

const resetFilters = () => {
    search.value = '';
    kategori.value = '';
    status.value = '';
    applyFilters();
};

const executeDelete = () => {
    if (!deleteTarget.value) return;
    deleting.value = true;
    router.delete(route('produk.destroy', deleteTarget.value.id_produk), {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            deleteTarget.value = null;
        },
    });
};
</script>

<template>
    <Head title="Daftar Produk" />

    <component :is="isAdmin ? AdminLayout : CashierLayout" title="Daftar Barang / Produk">
        <div class="space-y-5">
            <div class="flex flex-col justify-between gap-3 lg:flex-row lg:items-center">
                <form class="flex flex-wrap items-center gap-2" @submit.prevent="applyFilters">
                    <div class="w-full sm:w-60">
                        <SearchInput v-model="search" placeholder="Cari nama atau kode produk..." @search="applyFilters" />
                    </div>
                    <div class="w-full sm:w-44">
                        <BaseSelect
                            v-model="kategori"
                            :options="categories"
                            option-value="id_kategori"
                            option-label="nama_kategori"
                            placeholder="Semua Kategori"
                            aria-label="Filter kategori"
                            @change="applyFilters"
                        />
                    </div>
                    <div class="w-full sm:w-44">
                        <BaseSelect
                            v-model="status"
                            :options="statusOptions"
                            placeholder="Semua Status/Stok"
                            aria-label="Filter status produk"
                            @change="applyFilters"
                        />
                    </div>
                    <BaseButton type="submit" variant="secondary">Filter</BaseButton>
                    <BaseButton v-if="hasActiveFilter" type="button" variant="ghost" @click="resetFilters">
                        Reset
                    </BaseButton>
                </form>

                <BaseButton v-if="can.createProducts" :href="route('produk.create')">
                    + Tambah Produk
                </BaseButton>
            </div>

            <BaseTable :headers="headers" :loading="loading" :empty="(products.data ?? []).length === 0">
                <template #empty>
                    <EmptyState
                        title="Produk belum tersedia"
                        description="Belum ada produk yang cocok dengan pencarian Anda."
                    >
                        <template v-if="can.createProducts" #action>
                            <BaseButton :href="route('produk.create')">Tambah Produk Baru</BaseButton>
                        </template>
                    </EmptyState>
                </template>
                <tr v-for="item in products.data" :key="item.id_produk" class="transition hover:bg-neutral-50/70">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-neutral-200 bg-neutral-100">
                                <img
                                    v-if="item.gambar_url"
                                    :src="item.gambar_url"
                                    :alt="item.nama_produk"
                                    class="h-full w-full object-cover"
                                />
                                <svg v-else class="h-5 w-5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <div>
                                <div class="font-medium text-neutral-900">{{ item.nama_produk }}</div>
                                <div class="text-xs text-neutral-500">{{ item.kode_produk || 'Tanpa SKU' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-neutral-700">
                        {{ item.kategori?.nama_kategori ?? '-' }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-neutral-900">
                        {{ formatCurrency(item.harga) }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-right">
                        <span class="font-medium text-neutral-800">{{ item.stok }}</span>
                        <span
                            v-if="item.stok <= 0"
                            class="ml-1.5 inline-flex rounded bg-red-50 px-1.5 py-0.5 text-[10px] font-semibold text-red-700"
                        >
                            Habis
                        </span>
                        <span
                            v-else-if="item.stok <= 5"
                            class="ml-1.5 inline-flex rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700"
                        >
                            Menipis
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3">
                        <BaseBadge :variant="item.is_active ? 'success' : 'neutral'">
                            {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                        </BaseBadge>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-right">
                        <div class="inline-flex items-center justify-end gap-1">
                            <TableAction :href="route('produk.show', item.id_produk)">Detail</TableAction>
                            <TableAction
                                v-if="can.editProducts"
                                :href="route('produk.edit', item.id_produk)"
                                variant="primary"
                            >
                                Edit
                            </TableAction>
                            <TableAction
                                v-if="can.deleteProducts"
                                variant="danger"
                                @click="deleteTarget = item"
                            >
                                Hapus
                            </TableAction>
                        </div>
                    </td>
                </tr>

                <template #footer>
                    <BasePagination :meta="products.meta" />
                </template>
            </BaseTable>

            <ConfirmDialog
                v-if="can.deleteProducts"
                :show="Boolean(deleteTarget)"
                title="Hapus Produk"
                :message="`Yakin ingin menghapus produk ${deleteTarget?.nama_produk ?? ''}?`"
                :loading="deleting"
                @close="deleteTarget = null"
                @confirm="executeDelete"
            />
        </div>
    </component>
</template>
