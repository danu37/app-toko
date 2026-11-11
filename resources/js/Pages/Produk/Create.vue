<script setup>
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CashierLayout from '@/Layouts/CashierLayout.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BaseInput from '@/Components/UI/BaseInput.vue';
import BaseSelect from '@/Components/UI/BaseSelect.vue';
import ImageUpload from '@/Components/Forms/ImageUpload.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { usePermissions } from '@/Composables/usePermissions';

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
});

const { isAdmin } = usePermissions();
const imageCompressing = ref(false);

const form = useForm({
    id_kategori: '',
    kode_produk: '',
    nama_produk: '',
    harga: '',
    stok: 0,
    deskripsi: '',
    gambar: null,
    is_featured: false,
    is_active: true,
});

const categoryOptions = computed(() =>
    props.categories.map((cat) => ({
        value: cat.id_kategori,
        label: cat.nama_kategori,
    })),
);

const submit = () => {
    form.post(route('produk.store'), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Tambah Produk" />

    <component :is="isAdmin ? AdminLayout : CashierLayout" title="Tambah Barang / Produk">
        <div class="mx-auto max-w-2xl rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
            <div class="mb-6 flex items-center justify-between border-b border-neutral-100 pb-4">
                <div>
                    <h2 class="text-base font-semibold text-neutral-900">Form Tambah Produk Baru</h2>
                    <p class="text-xs text-neutral-500">Pastikan harga dan stok awal tercatat dengan benar.</p>
                </div>
                <BaseButton :href="route('produk.index')" variant="secondary" size="sm">
                    &larr; Kembali
                </BaseButton>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <BaseInput
                        id="nama_produk"
                        v-model="form.nama_produk"
                        label="Nama Produk"
                        required
                        :error="form.errors.nama_produk"
                    />
                    <BaseInput
                        id="kode_produk"
                        v-model="form.kode_produk"
                        label="Kode Produk / SKU (Opsional)"
                        :error="form.errors.kode_produk"
                    />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <BaseSelect
                        id="id_kategori"
                        v-model="form.id_kategori"
                        label="Kategori"
                        :options="categoryOptions"
                        placeholder="Pilih kategori"
                        :error="form.errors.id_kategori"
                    />
                    <BaseInput
                        id="harga"
                        v-model="form.harga"
                        label="Harga (Rp)"
                        type="number"
                        min="0"
                        step="100"
                        required
                        :error="form.errors.harga"
                    />
                    <BaseInput
                        id="stok"
                        v-model="form.stok"
                        label="Stok Awal"
                        type="number"
                        min="0"
                        required
                        :error="form.errors.stok"
                    />
                </div>

                <div>
                    <label for="deskripsi" class="mb-1.5 block text-sm font-medium text-neutral-700">
                        Deskripsi
                    </label>
                    <textarea
                        id="deskripsi"
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
                    :error="form.errors.gambar"
                    @compressing="imageCompressing = $event"
                />

                <div class="flex flex-wrap gap-6 pt-1">
                    <label class="inline-flex cursor-pointer items-center gap-2">
                        <Checkbox v-model:checked="form.is_active" />
                        <span class="text-sm text-neutral-700">Produk aktif</span>
                    </label>
                    <label class="inline-flex cursor-pointer items-center gap-2">
                        <Checkbox v-model:checked="form.is_featured" />
                        <span class="text-sm text-neutral-700">Tampilkan di produk unggulan</span>
                    </label>
                </div>

                <div class="flex justify-end gap-3 border-t border-neutral-100 pt-4">
                    <BaseButton :href="route('produk.index')" variant="secondary">Batal</BaseButton>
                    <BaseButton type="submit" :loading="form.processing" :disabled="imageCompressing">
                        Simpan Produk
                    </BaseButton>
                </div>
            </form>
        </div>
    </component>
</template>

