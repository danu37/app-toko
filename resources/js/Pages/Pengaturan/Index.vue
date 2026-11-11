<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BaseBadge from '@/Components/UI/BaseBadge.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';

defineProps({
    settings: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head title="Pengaturan Aplikasi" />

    <AdminLayout title="Pengaturan Aplikasi">
        <div class="space-y-6">
            <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h2 class="text-base font-semibold text-neutral-900">Informasi Sistem Toko</h2>
                        <p class="mt-1 text-sm text-neutral-500">
                            Ringkasan konfigurasi aplikasi dan status penyimpanan media. Halaman ini hanya dapat diakses oleh Administrator.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <BaseButton :href="route('pengguna.index')" variant="secondary" size="sm">
                            Kelola Pengguna &amp; Role
                        </BaseButton>
                        <BaseButton :href="route('cabang.index')" variant="secondary" size="sm">
                            Kelola Cabang
                        </BaseButton>
                    </div>
                </div>

                <dl class="mt-6 grid grid-cols-1 gap-4 border-t border-neutral-100 pt-5 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-lg bg-neutral-50 p-4">
                        <dt class="text-xs font-medium text-neutral-500">Nama Aplikasi</dt>
                        <dd class="mt-1 text-sm font-semibold text-neutral-900">{{ settings.app_name }}</dd>
                    </div>
                    <div class="rounded-lg bg-neutral-50 p-4">
                        <dt class="text-xs font-medium text-neutral-500">Environment</dt>
                        <dd class="mt-1 text-sm font-semibold uppercase text-neutral-900">{{ settings.app_env }}</dd>
                    </div>
                    <div class="rounded-lg bg-neutral-50 p-4">
                        <dt class="text-xs font-medium text-neutral-500">Zona Waktu &amp; Lokal</dt>
                        <dd class="mt-1 text-sm font-semibold text-neutral-900">
                            {{ settings.timezone }} ({{ settings.locale }})
                        </dd>
                    </div>
                    <div class="rounded-lg bg-neutral-50 p-4">
                        <dt class="text-xs font-medium text-neutral-500">Status Public Storage Link</dt>
                        <dd class="mt-1">
                            <BaseBadge :variant="settings.storage_linked ? 'success' : 'warning'">
                                {{ settings.storage_linked ? 'Terhubung (public/storage)' : 'Belum Terhubung' }}
                            </BaseBadge>
                        </dd>
                    </div>
                    <div class="rounded-lg bg-neutral-50 p-4 sm:col-span-2">
                        <dt class="text-xs font-medium text-neutral-500">Ringkasan Entitas Database</dt>
                        <dd class="mt-1 flex flex-wrap gap-x-5 gap-y-2 text-sm text-neutral-800">
                            <span><strong>{{ settings.stats?.products ?? 0 }}</strong> Produk</span>
                            <span><strong>{{ settings.stats?.categories ?? 0 }}</strong> Kategori</span>
                            <span><strong>{{ settings.stats?.customers ?? 0 }}</strong> Pelanggan</span>
                            <span><strong>{{ settings.stats?.transactions ?? 0 }}</strong> Transaksi</span>
                            <span><strong>{{ settings.stats?.branches ?? 0 }}</strong> Cabang</span>
                            <span><strong>{{ settings.stats?.users ?? 0 }}</strong> Pengguna</span>
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                <h3 class="text-base font-semibold text-neutral-900">Kebijakan Keamanan &amp; Bisnis Aktif</h3>
                <ul class="mt-4 space-y-2.5 text-sm text-neutral-600">
                    <li class="flex items-start gap-2.5">
                        <BaseBadge variant="dark">Role</BaseBadge>
                        <span>Registrasi publik dinonaktifkan; pembuatan dan perubahan role akun hanya dilakukan oleh Administrator.</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <BaseBadge variant="success">Kasir</BaseBadge>
                        <span>Kasir dapat melihat &amp; menambah produk/pelanggan serta mencatat transaksi, namun ditolak HTTP 403 saat edit/hapus.</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <BaseBadge variant="info">Transaksi</BaseBadge>
                        <span>Harga dan total belanja dihitung secara otoritatif di server menggunakan database transaction tanpa memercayai nilai browser.</span>
                    </li>
                </ul>
            </div>
        </div>
    </AdminLayout>
</template>
