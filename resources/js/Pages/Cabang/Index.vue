<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CashierLayout from '@/Layouts/CashierLayout.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BasePagination from '@/Components/UI/BasePagination.vue';
import BaseTable from '@/Components/UI/BaseTable.vue';
import TableAction from '@/Components/Tables/TableAction.vue';
import SearchInput from '@/Components/Forms/SearchInput.vue';
import ConfirmDialog from '@/Components/Feedback/ConfirmDialog.vue';
import EmptyState from '@/Components/Feedback/EmptyState.vue';
import { usePermissions } from '@/Composables/usePermissions';

const props = defineProps({
    branches: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ search: '' }),
    },
});

const { can, isAdmin } = usePermissions();

const search = ref(props.filters.search ?? '');
const loading = ref(false);
const deleteTarget = ref(null);
const deleting = ref(false);

const headers = [
    { key: 'nama', label: 'Nama Cabang' },
    { key: 'kota', label: 'Kota' },
    { key: 'staff', label: 'Staff Terdaftar', align: 'right' },
    { key: 'transaksi', label: 'Transaksi', align: 'right' },
    { key: 'aksi', label: 'Aksi', align: 'right' },
];

const applySearch = () => {
    loading.value = true;
    router.get(route('cabang.index'), search.value ? { search: search.value } : {}, {
        preserveState: true,
        replace: true,
        onFinish: () => {
            loading.value = false;
        },
    });
};

const executeDelete = () => {
    if (!deleteTarget.value) return;
    deleting.value = true;
    router.delete(route('cabang.destroy', deleteTarget.value.id_cabang), {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            deleteTarget.value = null;
        },
    });
};
</script>

<template>
    <Head title="Daftar Cabang" />

    <component :is="isAdmin ? AdminLayout : CashierLayout" title="Daftar Cabang Toko">
        <div class="space-y-5">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <form class="flex w-full max-w-md items-center gap-2" @submit.prevent="applySearch">
                    <SearchInput v-model="search" placeholder="Cari cabang atau kota..." @search="applySearch" />
                    <BaseButton type="submit" variant="secondary">Cari</BaseButton>
                </form>

                <BaseButton v-if="can.manageBranches" :href="route('cabang.create')">
                    + Tambah Cabang
                </BaseButton>
            </div>

            <BaseTable :headers="headers" :loading="loading" :empty="(branches.data ?? []).length === 0">
                <template #empty>
                    <EmptyState
                        title="Cabang belum tersedia"
                        description="Belum ada cabang toko yang cocok dengan pencarian Anda."
                    />
                </template>
                <tr v-for="item in branches.data" :key="item.id_cabang" class="transition hover:bg-neutral-50/70">
                    <td class="px-4 py-3 font-medium text-neutral-900">{{ item.nama_cabang }}</td>
                    <td class="px-4 py-3 text-neutral-700">{{ item.kota }}</td>
                    <td class="whitespace-nowrap px-4 py-3 text-right text-neutral-700">
                        {{ item.users_count ?? 0 }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-right text-neutral-700">
                        {{ item.transaksi_count ?? 0 }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-right">
                        <div v-if="can.manageBranches" class="inline-flex items-center gap-1">
                            <TableAction :href="route('cabang.edit', item.id_cabang)" variant="primary">
                                Edit
                            </TableAction>
                            <TableAction variant="danger" @click="deleteTarget = item">
                                Hapus
                            </TableAction>
                        </div>
                        <span v-else class="text-xs text-neutral-400">Hanya Lihat</span>
                    </td>
                </tr>

                <template #footer>
                    <BasePagination :meta="branches.meta" />
                </template>
            </BaseTable>

            <ConfirmDialog
                v-if="can.manageBranches"
                :show="Boolean(deleteTarget)"
                title="Hapus Cabang"
                :message="`Yakin ingin menghapus cabang ${deleteTarget?.nama_cabang ?? ''}?`"
                :loading="deleting"
                @close="deleteTarget = null"
                @confirm="executeDelete"
            />
        </div>
    </component>
</template>

