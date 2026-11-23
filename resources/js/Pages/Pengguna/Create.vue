<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BaseInput from '@/Components/UI/BaseInput.vue';
import BaseSelect from '@/Components/UI/BaseSelect.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    branches: {
        type: Array,
        default: () => [],
    },
    customers: {
        type: Array,
        default: () => [],
    },
    roles: {
        type: Array,
        default: () => ['admin', 'kasir', 'pelanggan'],
    },
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'kasir',
    rfid_uid: '',
    id_cabang: '',
    id_pelanggan: '',
    is_active: true,
});

const roleOptions = computed(() =>
    props.roles.map((r) => ({
        value: r,
        label: r === 'admin'
            ? 'Administrator (Akses Penuh)'
            : (r === 'kasir' ? 'Kasir (Operasional POS)' : 'Pelanggan (Dashboard Pelanggan)'),
    })),
);

const branchOptions = computed(() =>
    props.branches.map((b) => ({
        value: b.id_cabang,
        label: `${b.nama_cabang} (${b.kota})`,
    })),
);

const customerOptions = computed(() =>
    props.customers.map((c) => ({
        value: c.id_pelanggan,
        label: `${c.nama_pelanggan} ${c.no_hp ? `(${c.no_hp})` : ''} — ${c.kota || 'Umum'}`,
    })),
);

const submit = () => {
    form.post(route('pengguna.store'));
};
</script>

<template>
    <Head title="Tambah Pengguna" />

    <AdminLayout title="Tambah Pengguna Baru">
        <div class="mx-auto max-w-2xl rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
            <div class="mb-6 flex items-center justify-between border-b border-neutral-100 pb-4">
                <div>
                    <h2 class="text-base font-semibold text-neutral-900">Data Akun Pengguna</h2>
                    <p class="text-xs text-neutral-500">Tetapkan role (Admin, Kasir, atau Pelanggan) dan kartu RFID.</p>
                </div>
                <BaseButton :href="route('pengguna.index')" variant="secondary" size="sm">
                    &larr; Kembali
                </BaseButton>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <BaseInput
                        id="name"
                        v-model="form.name"
                        label="Nama Lengkap"
                        required
                        :error="form.errors.name"
                    />

                    <BaseInput
                        id="email"
                        v-model="form.email"
                        label="Alamat Email"
                        type="email"
                        required
                        :error="form.errors.email"
                    />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <BaseSelect
                        id="role"
                        v-model="form.role"
                        label="Role Akun"
                        :options="roleOptions"
                        placeholder=""
                        required
                        :error="form.errors.role"
                    />

                    <BaseSelect
                        v-if="form.role !== 'pelanggan'"
                        id="id_cabang"
                        v-model="form.id_cabang"
                        label="Penempatan Cabang"
                        :options="branchOptions"
                        placeholder="Semua Cabang / Tidak Spesifik"
                        :error="form.errors.id_cabang"
                    />

                    <BaseSelect
                        v-else
                        id="id_pelanggan"
                        v-model="form.id_pelanggan"
                        label="Hubungkan Data Pelanggan (tbl_pelanggan)"
                        :options="customerOptions"
                        placeholder="Pilih Pelanggan Terdaftar (Opsional)"
                        :error="form.errors.id_pelanggan"
                    />
                </div>

                <div>
                    <BaseInput
                        id="rfid_uid"
                        v-model="form.rfid_uid"
                        label="UID Kartu RFID (Opsional)"
                        placeholder="Contoh: 0012345678 atau A1B2C3D4"
                        :error="form.errors.rfid_uid"
                    />
                    <p class="mt-1 text-xs text-neutral-500">
                        Scan menggunakan reader atau masukkan UID manual. Kartu ini dapat digunakan oleh Admin, Kasir, maupun Pelanggan untuk login instan.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <BaseInput
                        id="password"
                        v-model="form.password"
                        label="Kata Sandi"
                        type="password"
                        required
                        autocomplete="new-password"
                        :error="form.errors.password"
                    />

                    <BaseInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        label="Konfirmasi Kata Sandi"
                        type="password"
                        required
                        autocomplete="new-password"
                        :error="form.errors.password_confirmation"
                    />
                </div>

                <label class="inline-flex cursor-pointer items-center gap-2 pt-1">
                    <Checkbox v-model:checked="form.is_active" />
                    <span class="text-sm text-neutral-700">Akun aktif (dapat login ke sistem)</span>
                </label>

                <div class="flex items-center justify-end gap-3 border-t border-neutral-100 pt-4">
                    <BaseButton :href="route('pengguna.index')" variant="secondary">
                        Batal
                    </BaseButton>
                    <BaseButton type="submit" :loading="form.processing">
                        Simpan Pengguna
                    </BaseButton>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
