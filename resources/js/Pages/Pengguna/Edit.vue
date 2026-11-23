<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BaseBadge from '@/Components/UI/BaseBadge.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BaseInput from '@/Components/UI/BaseInput.vue';
import BaseSelect from '@/Components/UI/BaseSelect.vue';
import Checkbox from '@/Components/Checkbox.vue';
import ConfirmDialog from '@/Components/Feedback/ConfirmDialog.vue';
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
    customers: {
        type: Array,
        default: () => [],
    },
    roles: {
        type: Array,
        default: () => ['admin', 'kasir', 'pelanggan'],
    },
});

const { user: authUser } = usePermissions();
const isSelf = computed(() => authUser.value?.id === props.user.id);
const showDetachConfirm = ref(false);
const detachingRfid = ref(false);

const form = useForm({
    name: props.user.name ?? '',
    email: props.user.email ?? '',
    password: '',
    password_confirmation: '',
    role: props.user.role ?? 'kasir',
    rfid_uid: props.user.rfid_uid ?? '',
    id_cabang: props.user.id_cabang ?? '',
    id_pelanggan: props.user.id_pelanggan ?? '',
    is_active: Boolean(props.user.is_active),
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
    form.put(route('pengguna.update', props.user.id), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

const handleDetachRfid = () => {
    detachingRfid.value = true;
    router.delete(route('pengguna.rfid.destroy', props.user.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.rfid_uid = '';
            showDetachConfirm.value = false;
        },
        onFinish: () => {
            detachingRfid.value = false;
        },
    });
};

const toggleCard = (cardId) => {
    router.patch(route('pengguna.rfid.toggle', [props.user.id, cardId]), {}, {
        preserveScroll: true,
    });
};

const deleteCard = (cardId) => {
    router.delete(route('pengguna.rfid.card.destroy', [props.user.id, cardId]), {
        preserveScroll: true,
        onSuccess: () => {
            form.rfid_uid = '';
        },
    });
};
</script>

<template>
    <Head title="Edit Pengguna" />

    <AdminLayout :title="`Edit Pengguna: ${user.name}`">
        <div class="mx-auto max-w-2xl rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
            <div class="mb-6 flex items-center justify-between border-b border-neutral-100 pb-4">
                <div>
                    <h2 class="text-base font-semibold text-neutral-900">Perbarui Akun Pengguna</h2>
                    <p class="text-xs text-neutral-500">
                        Atur role akun, penempatan cabang / relasi pelanggan, serta kartu RFID.
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

                <!-- Pengelolaan RFID UID & Kartu -->
                <div class="rounded-xl border border-neutral-200 bg-neutral-50/60 p-4 space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <label for="rfid_uid" class="block text-sm font-medium text-neutral-800">
                                Manajemen Kartu RFID
                            </label>
                            <p class="text-xs text-neutral-500">
                                Digunakan untuk login instan mesin reader oleh Admin, Kasir, maupun Pelanggan.
                            </p>
                        </div>
                        <BaseBadge v-if="user.has_rfid" variant="info">
                            Kartu Terdaftar
                        </BaseBadge>
                        <BaseBadge v-else variant="neutral">
                            Belum Ada Kartu
                        </BaseBadge>
                    </div>

                    <!-- Daftar Kartu RFID Terhubung -->
                    <div v-if="(user.rfid_cards || []).length > 0" class="space-y-2">
                        <div
                            v-for="card in user.rfid_cards"
                            :key="card.id"
                            class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-neutral-200 bg-white px-3.5 py-2.5 text-xs shadow-xs"
                        >
                            <div class="flex items-center gap-2">
                                <span class="font-mono font-bold text-neutral-900 tracking-wider">
                                    {{ card.uid }}
                                </span>
                                <BaseBadge :variant="card.is_active ? 'success' : 'danger'">
                                    {{ card.is_active ? 'Aktif' : 'Nonaktif' }}
                                </BaseBadge>
                            </div>

                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="text-xs font-semibold hover:underline"
                                    :class="card.is_active ? 'text-amber-600' : 'text-emerald-600'"
                                    @click="toggleCard(card.id)"
                                >
                                    {{ card.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                                <span class="text-neutral-300">|</span>
                                <button
                                    type="button"
                                    class="text-xs font-semibold text-red-600 hover:underline"
                                    @click="deleteCard(card.id)"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Input Ubah / Daftarkan UID Baru -->
                    <div>
                        <div class="text-xs font-medium text-neutral-700 mb-1">
                            {{ user.has_rfid ? 'Ganti UID Kartu / Masukkan UID Baru:' : 'Daftarkan UID Kartu:' }}
                        </div>
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <div class="relative flex-1">
                                <input
                                    id="rfid_uid"
                                    v-model="form.rfid_uid"
                                    type="text"
                                    placeholder="Scan kartu atau ketik nomor UID..."
                                    class="block w-full rounded-lg border border-neutral-300 bg-white py-2 pl-3 pr-8 font-mono text-sm tracking-wider text-neutral-900 shadow-sm placeholder:text-neutral-400 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900"
                                    :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.rfid_uid }"
                                />
                                <button
                                    v-if="form.rfid_uid"
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-xs text-neutral-400 hover:text-neutral-600"
                                    @click="form.rfid_uid = ''"
                                >
                                    &times;
                                </button>
                            </div>

                            <BaseButton
                                v-if="user.has_rfid"
                                type="button"
                                variant="danger"
                                size="sm"
                                @click="showDetachConfirm = true"
                            >
                                Lepas Semua Kartu
                            </BaseButton>
                        </div>
                        <p v-if="form.errors.rfid_uid" class="mt-1 text-xs font-medium text-red-600">
                            {{ form.errors.rfid_uid }}
                        </p>
                        <p v-if="user.rfid_uid && form.rfid_uid !== user.rfid_uid" class="mt-1 text-xs text-amber-600 font-medium">
                            Perubahan UID akan disimpan ketika Anda menekan tombol "Perbarui Pengguna" di bawah.
                        </p>
                    </div>
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

        <!-- Dialog Konfirmasi Lepas Kartu RFID -->
        <ConfirmDialog
            :show="showDetachConfirm"
            title="Konfirmasi Lepas Kartu RFID"
            :message="`Apakah Anda yakin ingin melepas semua kartu RFID dari akun ${user.name}? Pengguna ini tidak lagi dapat menggunakan kartu untuk masuk sistem sampai kartu baru didaftarkan.`"
            confirm-label="Lepas Kartu"
            variant="danger"
            :loading="detachingRfid"
            @confirm="handleDetachRfid"
            @close="showDetachConfirm = false"
        />
    </AdminLayout>
</template>
