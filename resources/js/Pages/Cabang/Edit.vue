<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BaseInput from '@/Components/UI/BaseInput.vue';

const props = defineProps({
    branch: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    nama_cabang: props.branch.nama_cabang ?? '',
    kota: props.branch.kota ?? '',
});

const submit = () => {
    form.put(route('cabang.update', props.branch.id_cabang));
};
</script>

<template>
    <Head title="Edit Cabang" />

    <AdminLayout :title="`Edit Cabang: ${branch.nama_cabang}`">
        <div class="mx-auto max-w-xl rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
            <div class="mb-6 flex items-center justify-between border-b border-neutral-100 pb-4">
                <h2 class="text-base font-semibold text-neutral-900">Perbarui Data Cabang</h2>
                <BaseButton :href="route('cabang.index')" variant="secondary" size="sm">
                    &larr; Kembali
                </BaseButton>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <BaseInput
                    id="nama_cabang"
                    v-model="form.nama_cabang"
                    label="Nama Cabang"
                    required
                    :error="form.errors.nama_cabang"
                />
                <BaseInput
                    id="kota"
                    v-model="form.kota"
                    label="Kota"
                    required
                    :error="form.errors.kota"
                />
                <div class="flex justify-end gap-3 border-t border-neutral-100 pt-4">
                    <BaseButton :href="route('cabang.index')" variant="secondary">Batal</BaseButton>
                    <BaseButton type="submit" :loading="form.processing">Simpan Perubahan</BaseButton>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

