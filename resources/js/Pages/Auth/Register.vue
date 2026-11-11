<script setup>
import { Head } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
</script>

<template>
    <GuestLayout>
        <Head title="Registrasi Dinonaktifkan" />

        <div class="space-y-4 text-center">
            <h1 class="text-lg font-semibold text-neutral-900">Registrasi Publik Ditutup</h1>
            <p class="text-sm text-neutral-600">
                Pendaftaran akun staf baru (Admin &amp; Kasir) hanya dapat dibuat oleh Administrator sistem.
            </p>
            <BaseButton :href="route('login')" class="w-full">
                Masuk ke Halaman Login
            </BaseButton>
        </div>
    </GuestLayout>
</template>
