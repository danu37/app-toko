<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BaseInput from '@/Components/UI/BaseInput.vue';
import BaseSelect from '@/Components/UI/BaseSelect.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { usePermissions } from '@/Composables/usePermissions';

const props = defineProps({
    user: {
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
});

const { user: authUser } = usePermissions();
const isSelf = computed(() => authUser.value?.id === props.user.id);

const form = useForm({
    name: props.user.name ?? '',
    email: props.user.email ?? '',
    password: '',
    password_confirmation: '',
    role: props.user.role ?? 'kasir',
    id_cabang: props.user.id_cabang ?? '',
    is_active: Boolean(props.user.is_active),
});

const roleOptions = computed(() =>
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

const submit = () => {
    form.put(route('pengguna.update', props.user.id), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Edit Pengguna" />

    <AdminLayout :title="`Edit Pengguna: ${user.name}`">
        <div class="mx-auto max-w-2xl rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
            <div class="mb-6 flex items-center justify-between border-b border-neutral-100 pb-4">
                <div>
                    <h2 class="text-base font-semibold text-neutral-900">Perbarui Akun Staff</h2>
                    <p class="text-xs text-neutral-500">
                        Kosongkan kata sandi jika tidak ingin mengubah kata sandi akun ini.
                    </p>
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
                        :disabled="isSelf"
                        required
                        :error="form.errors.role"
                    />

                    <BaseSelect
                        id="id_cabang"
                        v-model="form.id_cabang"
                        label="Penempatan Cabang"
                        :options="branchOptions"
                        placeholder="Semua Cabang / Tidak Spesifik"
                        :error="form.errors.id_cabang"
                    />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <BaseInput
                        id="password"
                        v-model="form.password"
                        label="Kata Sandi Baru (Opsional)"
                        type="password"
                        autocomplete="new-password"
                        :error="form.errors.password"
                    />

                    <BaseInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        label="Konfirmasi Kata Sandi Baru"
                        type="password"
                        autocomplete="new-password"
                        :error="form.errors.password_confirmation"
                    />
                </div>

                <div>
                    <label class="inline-flex cursor-pointer items-center gap-2 pt-1">
                        <Checkbox v-model:checked="form.is_active" :disabled="isSelf" />
                        <span class="text-sm text-neutral-700">
                            Akun aktif (dapat login ke sistem)
                            <span v-if="isSelf" class="text-xs text-neutral-400">
                                — akun aktif Anda sendiri tidak dapat dinonaktifkan
                            </span>
                        </span>
                    </label>
                    <p v-if="form.errors.is_active" class="mt-1 text-xs font-medium text-red-600">
                        {{ form.errors.is_active }}
                    </p>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-neutral-100 pt-4">
                    <BaseButton :href="route('pengguna.index')" variant="secondary">
                        Batal
                    </BaseButton>
                    <BaseButton type="submit" :loading="form.processing">
                        Perbarui Pengguna
                    </BaseButton>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

