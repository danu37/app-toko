<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BaseBadge from '@/Components/UI/BaseBadge.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BaseModal from '@/Components/UI/BaseModal.vue';
import BasePagination from '@/Components/UI/BasePagination.vue';
import BaseSelect from '@/Components/UI/BaseSelect.vue';
import BaseTable from '@/Components/UI/BaseTable.vue';
import TableAction from '@/Components/Tables/TableAction.vue';
import SearchInput from '@/Components/Forms/SearchInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import ConfirmDialog from '@/Components/Feedback/ConfirmDialog.vue';
import EmptyState from '@/Components/Feedback/EmptyState.vue';
import StatCard from '@/Pages/Dashboard/Components/StatCard.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { useDate } from '@/Composables/useDate';

const props = defineProps({
    users: {
        type: Object,
        required: true,
    },
    branches: {
        type: Array,
        default: () => [],
    },
    roles: {
        type: Array,
        default: () => ['admin', 'kasir'],
    },
    roleStats: {
        type: Object,
        default: () => ({ total: 0, admin: 0, kasir: 0, aktif: 0 }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '', role: '', status: '' }),
    },
});

const { user: authUser } = usePermissions();
const { formatDate } = useDate();

const search = ref(props.filters.search ?? '');
const roleFilter = ref(props.filters.role ?? '');
const statusFilter = ref(props.filters.status ?? '');
const loading = ref(false);
const deleteTarget = ref(null);
const deleting = ref(false);
const roleTarget = ref(null);

const roleForm = useForm({
    role: 'kasir',
    id_cabang: '',
    is_active: true,
});

const roleFilterOptions = computed(() =>
    props.roles.map((r) => ({
        value: r,
        label: r === 'admin' ? 'Admin' : 'Kasir',
    })),
);

const roleSelectOptions = computed(() =>
    props.roles.map((r) => ({
        value: r,
        label: r === 'admin' ? 'Administrator (Akses Penuh)' : 'Kasir (Operasional)',
    })),
);

const branchOptions = computed(() =>
    props.branches.map((b) => ({
        value: b.id_cabang,
        label: `${b.nama_cabang} (${b.kota})`,
    })),
);

const statusOptions = [
    { value: 'aktif', label: 'Aktif' },
    { value: 'nonaktif', label: 'Nonaktif' },
];

const hasActiveFilter = computed(() =>
    Boolean(search.value || roleFilter.value || statusFilter.value),
);

const headers = [
    { key: 'name', label: 'Nama & Email' },
    { key: 'role', label: 'Role' },
    { key: 'cabang', label: 'Cabang' },
    { key: 'status', label: 'Status' },
    { key: 'created_at', label: 'Terdaftar' },
    { key: 'aksi', label: 'Aksi', align: 'right' },
];

const applyFilters = () => {
    const query = {};
    if (search.value) query.search = search.value;
    if (roleFilter.value) query.role = roleFilter.value;
    if (statusFilter.value) query.status = statusFilter.value;
    loading.value = true;
    router.get(route('pengguna.index'), query, {
        preserveState: true,
        replace: true,
        onFinish: () => {
            loading.value = false;
        },
    });
};

const resetFilters = () => {
    search.value = '';
    roleFilter.value = '';
    statusFilter.value = '';
    applyFilters();
};

const openRoleModal = (item) => {
    roleTarget.value = item;
    roleForm.clearErrors();
    roleForm.role = item.role ?? 'kasir';
    roleForm.id_cabang = item.id_cabang ?? '';
    roleForm.is_active = Boolean(item.is_active);
};

const submitRole = () => {
    if (!roleTarget.value) return;
    roleForm.patch(route('pengguna.role', roleTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            roleTarget.value = null;
        },
    });
};

const confirmDelete = (item) => {
    deleteTarget.value = item;
};

const executeDelete = () => {
    if (!deleteTarget.value) return;
    deleting.value = true;
    router.delete(route('pengguna.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            deleteTarget.value = null;
        },
    });
};

const roleMatrix = [
    { feature: 'Dashboard Operasional & POS Transaksi', admin: 'Akses Penuh', kasir: 'Diizinkan' },
    { feature: 'Lihat & Tambah Produk / Pelanggan', admin: 'Diizinkan', kasir: 'Diizinkan' },
    { feature: 'Edit & Hapus Produk / Pelanggan', admin: 'Diizinkan', kasir: 'Ditolak (HTTP 403)' },
    { feature: 'Kelola Kategori & Cabang', admin: 'Diizinkan', kasir: 'Lihat Saja (Mutasi 403)' },
    { feature: 'Laporan Penjualan, Transaksi & Rekap Pendapatan', admin: 'Diizinkan', kasir: 'Ditolak (HTTP 403)' },
    { feature: 'Manajemen Pengguna, Pengaturan Role & Sistem', admin: 'Diizinkan', kasir: 'Ditolak (HTTP 403)' },
];
</script>

<template>
    <Head title="Pengguna & Role" />

    <AdminLayout title="Manajemen Pengguna &amp; Pengaturan Role">
        <div class="space-y-6">
            <!-- Role Stats -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard label="Total Akun" :value="roleStats.total ?? 0" tone="neutral" />
                <StatCard label="Administrator" :value="roleStats.admin ?? 0" description="Akses penuh sistem" tone="sky" />
                <StatCard label="Kasir" :value="roleStats.kasir ?? 0" description="Operasional harian" tone="emerald" />
                <StatCard label="Akun Aktif" :value="roleStats.aktif ?? 0" description="Dapat mengakses aplikasi" tone="amber" />
            </div>

            <!-- Search & Filters -->
            <div class="flex flex-col justify-between gap-3 lg:flex-row lg:items-center">
                <form class="flex flex-wrap items-center gap-2" @submit.prevent="applyFilters">
                    <div class="w-full sm:w-64">
                        <SearchInput
                            v-model="search"
                            placeholder="Cari nama atau email..."
                            @search="applyFilters"
                        />
                    </div>
                    <div class="w-full sm:w-40">
                        <BaseSelect
                            v-model="roleFilter"
                            :options="roleFilterOptions"
                            placeholder="Semua Role"
                            aria-label="Filter role"
                            @change="applyFilters"
                        />
                    </div>
                    <div class="w-full sm:w-40">
                        <BaseSelect
                            v-model="statusFilter"
                            :options="statusOptions"
                            placeholder="Semua Status"
                            aria-label="Filter status akun"
                            @change="applyFilters"
                        />
                    </div>
                    <BaseButton type="submit" variant="secondary">Filter</BaseButton>
                    <BaseButton v-if="hasActiveFilter" type="button" variant="ghost" @click="resetFilters">
                        Reset
                    </BaseButton>
                </form>

                <BaseButton :href="route('pengguna.create')">
                    + Tambah Pengguna
                </BaseButton>
            </div>

            <!-- Users Table -->
            <BaseTable :headers="headers" :loading="loading" :empty="(users.data ?? []).length === 0">
                <template #empty>
                    <EmptyState
                        title="Pengguna tidak ditemukan"
                        description="Belum ada akun yang cocok dengan filter pencarian Anda."
                    >
                        <template #action>
                            <BaseButton :href="route('pengguna.create')">Tambah Pengguna Baru</BaseButton>
                        </template>
                    </EmptyState>
                </template>
                <tr
                    v-for="item in users.data"
                    :key="item.id"
                    class="transition hover:bg-neutral-50/70"
                >
                    <td class="px-4 py-3">
                        <div class="font-medium text-neutral-900">
                            {{ item.name }}
                            <span v-if="authUser?.id === item.id" class="ml-1 text-xs font-normal text-neutral-500">
                                (Anda)
                            </span>
                        </div>
                        <div class="text-xs text-neutral-500">{{ item.email }}</div>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3">
                        <BaseBadge :variant="item.role === 'admin' ? 'dark' : 'success'">
                            {{ item.role === 'admin' ? 'Admin' : 'Kasir' }}
                        </BaseBadge>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-neutral-700">
                        {{ item.cabang?.nama_cabang ?? 'Semua Cabang' }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-3">
                        <BaseBadge :variant="item.is_active ? 'info' : 'danger'">
                            {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                        </BaseBadge>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-xs text-neutral-500">
                        {{ formatDate(item.created_at) }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-right">
                        <div class="inline-flex items-center justify-end gap-1">
                            <TableAction
                                v-if="authUser?.id !== item.id"
                                @click="openRoleModal(item)"
                            >
                                Atur Role
                            </TableAction>
                            <TableAction :href="route('pengguna.edit', item.id)" variant="primary">
                                Edit
                            </TableAction>
                            <TableAction
                                v-if="authUser?.id !== item.id"
                                variant="danger"
                                @click="confirmDelete(item)"
                            >
                                Hapus
                            </TableAction>
                        </div>
                    </td>
                </tr>

                <template #footer>
                    <BasePagination :meta="users.meta" />
                </template>
            </BaseTable>

            <!-- Role Permission Matrix Reference -->
            <section class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                <h3 class="text-base font-semibold text-neutral-900">Pengaturan &amp; Matriks Hak Akses Role</h3>
                <p class="mt-1 text-xs text-neutral-500">
                    Role menentukan otorisasi backend melalui Middleware, Policy, dan Gate Laravel. Kasir yang mengakses endpoint Admin akan ditolak HTTP 403.
                </p>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200 text-left text-xs">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th class="px-4 py-2.5 font-semibold uppercase tracking-wider text-neutral-600">Fitur / Modul</th>
                                <th class="px-4 py-2.5 font-semibold uppercase tracking-wider text-neutral-600">Admin</th>
                                <th class="px-4 py-2.5 font-semibold uppercase tracking-wider text-neutral-600">Kasir</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            <tr v-for="row in roleMatrix" :key="row.feature">
                                <td class="px-4 py-2.5 font-medium text-neutral-800">{{ row.feature }}</td>
                                <td class="px-4 py-2.5">
                                    <BaseBadge variant="dark">{{ row.admin }}</BaseBadge>
                                </td>
                                <td class="px-4 py-2.5">
                                    <BaseBadge :variant="row.kasir.includes('403') ? 'danger' : 'success'">
                                        {{ row.kasir }}
                                    </BaseBadge>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Quick Role Modal -->
            <BaseModal
                :show="Boolean(roleTarget)"
                :title="`Atur Role: ${roleTarget?.name ?? ''}`"
                @close="roleTarget = null"
            >
                <form class="space-y-4" @submit.prevent="submitRole">
                    <p class="text-xs text-neutral-500">
                        Ubah role akses, cabang penempatan, atau status aktif akun <strong>{{ roleTarget?.email }}</strong>.
                    </p>

                    <BaseSelect
                        id="quick_role"
                        v-model="roleForm.role"
                        label="Role Akun"
                        :options="roleSelectOptions"
                        placeholder=""
                        required
                        :error="roleForm.errors.role"
                    />

                    <BaseSelect
                        id="quick_cabang"
                        v-model="roleForm.id_cabang"
                        label="Penempatan Cabang"
                        :options="branchOptions"
                        placeholder="Semua Cabang / Tidak Spesifik"
                        :error="roleForm.errors.id_cabang"
                    />

                    <label class="inline-flex cursor-pointer items-center gap-2 pt-1">
                        <Checkbox v-model:checked="roleForm.is_active" />
                        <span class="text-sm text-neutral-700">Akun aktif (diizinkan login)</span>
                    </label>
                    <p v-if="roleForm.errors.is_active" class="text-xs font-medium text-red-600">
                        {{ roleForm.errors.is_active }}
                    </p>

                    <div class="flex justify-end gap-2 border-t border-neutral-100 pt-4">
                        <BaseButton variant="secondary" @click="roleTarget = null">Batal</BaseButton>
                        <BaseButton type="submit" :loading="roleForm.processing">Simpan Role</BaseButton>
                    </div>
                </form>
            </BaseModal>

            <ConfirmDialog
                :show="Boolean(deleteTarget)"
                title="Hapus Pengguna"
                :message="`Yakin ingin menghapus akun ${deleteTarget?.name ?? ''} (${deleteTarget?.email ?? ''})?`"
                :loading="deleting"
                @close="deleteTarget = null"
                @confirm="executeDelete"
            />
        </div>
    </AdminLayout>
</template>
