<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BaseInput from '@/Components/UI/BaseInput.vue';

const props = defineProps({
    customer: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    nama_pelanggan: props.customer.nama_pelanggan ?? '',
    kota: props.customer.kota ?? '',
    no_hp: props.customer.no_hp ?? '',
    alamat: props.customer.alamat ?? '',
});

const submit = () => {
    form.put(route('pelanggan.update', props.customer.id_pelanggan));
};
</script>

<template>
    <Head title="Edit Pelanggan" />

    <AdminLayout :title="`Edit Pelanggan: ${customer.nama_pelanggan}`">
        <div class="mx-auto max-w-xl rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
            <div class="mb-6 flex items-center justify-between border-b border-neutral-100 pb-4">
                <div>
                    <h2 class="text-base font-semibold text-neutral-900">Perbarui Data Pelanggan</h2>
                    <p class="text-xs text-neutral-500">Hanya Administrator yang dapat mengubah atau menghapus data pelanggan.</p>
                </div>
                <BaseButton :href="route('pelanggan.index')" variant="secondary" size="sm">
                    &larr; Kembali
                </BaseButton>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <BaseInput
                    id="nama_pelanggan"
                    v-model="form.nama_pelanggan"
                    label="Nama Pelanggan"
                    required
                    :error="form.errors.nama_pelanggan"
                />

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <BaseInput
                        id="kota"
                        v-model="form.kota"
                        label="Kota"
                        required
                        :error="form.errors.kota"
                    />
                    <BaseInput
                        id="no_hp"
                        v-model="form.no_hp"
                        label="Nomor HP"
                        :error="form.errors.no_hp"
                    />
                </div>

                <div>
                    <label for="alamat" class="mb-1.5 block text-sm font-medium text-neutral-700">Alamat</label>
                    <textarea
                        id="alamat"
                        v-model="form.alamat"
                        rows="3"
                        class="block w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm text-neutral-900 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900"
                    />
                    <p v-if="form.errors.alamat" class="mt-1 text-xs font-medium text-red-600">{{ form.errors.alamat }}</p>
                </div>

                <div class="flex justify-end gap-3 border-t border-neutral-100 pt-4">
                    <BaseButton :href="route('pelanggan.index')" variant="secondary">Batal</BaseButton>
                    <BaseButton type="submit" :loading="form.processing">Simpan Perubahan</BaseButton>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

