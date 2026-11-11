<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CashierLayout from '@/Layouts/CashierLayout.vue';
import BaseBadge from '@/Components/UI/BaseBadge.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BaseInput from '@/Components/UI/BaseInput.vue';
import BaseModal from '@/Components/UI/BaseModal.vue';
import BasePagination from '@/Components/UI/BasePagination.vue';
import BaseTable from '@/Components/UI/BaseTable.vue';
import TableAction from '@/Components/Tables/TableAction.vue';
import SearchInput from '@/Components/Forms/SearchInput.vue';
import ImageUpload from '@/Components/Forms/ImageUpload.vue';
import ConfirmDialog from '@/Components/Feedback/ConfirmDialog.vue';
import EmptyState from '@/Components/Feedback/EmptyState.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { usePermissions } from '@/Composables/usePermissions';

const props = defineProps({
    categories: {
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
const modalOpen = ref(false);
const editingItem = ref(null);
const deleteTarget = ref(null);
const deleting = ref(false);
const imageCompressing = ref(false);

const form = useForm({
    nama_kategori: '',
    deskripsi: '',
    gambar: null,
    is_active: true,
});

const headers = [
    { key: 'nama', label: 'Kategori' },
    { key: 'slug', label: 'Slug' },
    { key: 'produk', label: 'Jumlah Produk' },
    { key: 'status', label: 'Status' },
    { key: 'aksi', label: 'Aksi', align: 'right' },
];

const applySearch = () => {
    loading.value = true;
    router.get(route('kategori.index'), search.value ? { search: search.value } : {}, {
        preserveState: true,
        replace: true,
        onFinish: () => {
            loading.value = false;
        },
    });
};

const openCreateModal = () => {
    editingItem.value = null;
    imageCompressing.value = false;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    modalOpen.value = true;
};

const openEditModal = (item) => {
    editingItem.value = item;
    imageCompressing.value = false;
    form.clearErrors();
    form.nama_kategori = item.nama_kategori ?? '';
    form.deskripsi = item.deskripsi ?? '';
    form.gambar = null;
    form.is_active = Boolean(item.is_active);
    modalOpen.value = true;
};

const submitForm = () => {
    if (editingItem.value) {
        form.transform((data) => ({
            ...data,
            _method: 'put',
        })).post(route('kategori.update', editingItem.value.id_kategori), {
            forceFormData: true,
            onSuccess: () => {
                modalOpen.value = false;
                form.reset();
            },
        });
    } else {
        form.transform((data) => data).post(route('kategori.store'), {
            forceFormData: true,
            onSuccess: () => {
                modalOpen.value = false;
                form.reset();
            },
        });
    }
};

const executeDelete = () => {
    if (!deleteTarget.value) return;
    deleting.value = true;
    router.delete(route('kategori.destroy', deleteTarget.value.id_kategori), {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            deleteTarget.value = null;
        },
    });
};
</script>

<template>
    <Head title="Kategori Produk" />

    <component :is="isAdmin ? AdminLayout : CashierLayout" title="Kategori Produk">
        <div class="space-y-5">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <form class="flex w-full max-w-md items-center gap-2" @submit.prevent="applySearch">
                    <SearchInput v-model="search" placeholder="Cari kategori..." @search="applySearch" />
                    <BaseButton type="submit" variant="secondary">Cari</BaseButton>
                </form>

                <BaseButton v-if="can.manageCategories" @click="openCreateModal">
                    + Tambah Kategori
                </BaseButton>
            </div>

            <BaseTable :headers="headers" :loading="loading" :empty="(categories.data ?? []).length === 0">
                <template #empty>
                    <EmptyState
                        title="Belum ada kategori"
                        description="Tambahkan kategori produk untuk mengelompokkan barang toko."
                    />
                </template>
                <tr v-for="item in categories.data" :key="item.id_kategori" class="transition hover:bg-neutral-50/70">
                    <td class="px-4 py-3">
                        <div class="font-medium text-neutral-900">{{ item.nama_kategori }}</div>
                        <div v-if="item.deskripsi" class="line-clamp-1 text-xs text-neutral-500">
                            {{ item.deskripsi }}
                        </div>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-xs text-neutral-500">
                        {{ item.slug }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-neutral-700">
                        {{ item.produk_count ?? 0 }} produk
                    </td>
                    <td class="whitespace-nowrap px-4 py-3">
                        <BaseBadge :variant="item.is_active ? 'success' : 'neutral'">
                            {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                        </BaseBadge>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-right">
                        <div v-if="can.manageCategories" class="inline-flex items-center gap-1">
                            <TableAction variant="primary" @click="openEditModal(item)">Edit</TableAction>
                            <TableAction variant="danger" @click="deleteTarget = item">Hapus</TableAction>
                        </div>
                        <span v-else class="text-xs text-neutral-400">Hanya Lihat</span>
                    </td>
                </tr>

                <template #footer>
                    <BasePagination :meta="categories.meta" />
                </template>
            </BaseTable>

            <BaseModal
                v-if="can.manageCategories"
                :show="modalOpen"
                :title="editingItem ? 'Edit Kategori' : 'Tambah Kategori Baru'"
                @close="modalOpen = false"
            >
                <form class="space-y-4" @submit.prevent="submitForm">
                    <BaseInput
                        id="nama_kategori"
                        v-model="form.nama_kategori"
                        label="Nama Kategori"
                        required
                        :error="form.errors.nama_kategori"
                    />
                    <div>
                        <label for="deskripsi_kategori" class="mb-1.5 block text-sm font-medium text-neutral-700">
                            Deskripsi
                        </label>
                        <textarea
                            id="deskripsi_kategori"
                            v-model="form.deskripsi"
                            rows="3"
                            class="block w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm text-neutral-900 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900"
                        />
                        <p v-if="form.errors.deskripsi" class="mt-1 text-xs font-medium text-red-600">
                            {{ form.errors.deskripsi }}
                        </p>
                    </div>
                    <ImageUpload
                        v-model="form.gambar"
                        label="Gambar Kategori (Opsional)"
                        :existing-url="editingItem?.gambar_url ?? null"
                        :error="form.errors.gambar"
                        @compressing="imageCompressing = $event"
                    />
                    <label class="inline-flex cursor-pointer items-center gap-2">
                        <Checkbox v-model:checked="form.is_active" />
                        <span class="text-sm text-neutral-700">Kategori aktif</span>
                    </label>
                    <div class="flex justify-end gap-2 border-t border-neutral-100 pt-4">
                        <BaseButton variant="secondary" @click="modalOpen = false">Batal</BaseButton>
                        <BaseButton type="submit" :loading="form.processing" :disabled="imageCompressing">
                            Simpan
                        </BaseButton>
                    </div>
                </form>
            </BaseModal>

            <ConfirmDialog
                :show="Boolean(deleteTarget)"
                title="Hapus Kategori"
                :message="`Yakin ingin menghapus kategori ${deleteTarget?.nama_kategori ?? ''}?`"
                :loading="deleting"
                @close="deleteTarget = null"
                @confirm="executeDelete"
            />
        </div>
    </component>
</template>

