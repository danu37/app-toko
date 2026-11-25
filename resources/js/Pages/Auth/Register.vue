<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import BaseAlert from '@/Components/UI/BaseAlert.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BaseInput from '@/Components/UI/BaseInput.vue';

const showPassword = ref(false);

const form = useForm({
    name: '',
    email: '',
    no_hp: '',
    kota: '',
    alamat: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Daftar Akun Pelanggan" />

        <div class="mb-5">
            <h1 class="text-lg font-semibold text-neutral-900">Daftar Akun Pelanggan</h1>
            <p class="mt-1 text-sm text-neutral-500">
                Buat akun pelanggan untuk memantau riwayat transaksi dan nota pembelian Anda.
            </p>
        </div>

        <BaseAlert
            v-if="Object.keys(form.errors).length > 0"
            variant="danger"
            :message="Object.values(form.errors)[0]"
            class="mb-4"
        />

        <form class="space-y-3.5" @submit.prevent="submit">
            <BaseInput
                id="reg_name"
                v-model="form.name"
                label="Nama Lengkap"
                type="text"
                placeholder="Nama lengkap Anda"
                required
                autofocus
                autocomplete="name"
                :error="form.errors.name"
            />

            <BaseInput
                id="reg_email"
                v-model="form.email"
                label="Alamat Email"
                type="email"
                placeholder="nama@email.com"
                required
                autocomplete="username"
                :error="form.errors.email"
            />

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <BaseInput
                    id="reg_no_hp"
                    v-model="form.no_hp"
                    label="No. HP (Opsional)"
                    type="text"
                    placeholder="08xxxxxxxxxx"
                    :error="form.errors.no_hp"
                />
                <BaseInput
                    id="reg_kota"
                    v-model="form.kota"
                    label="Kota (Opsional)"
                    type="text"
                    placeholder="Contoh: Ponorogo"
                    :error="form.errors.kota"
                />
            </div>

            <BaseInput
                id="reg_alamat"
                v-model="form.alamat"
                label="Alamat (Opsional)"
                type="text"
                placeholder="Alamat singkat"
                :error="form.errors.alamat"
            />

            <div>
                <label for="reg_password" class="mb-1.5 block text-sm font-medium text-neutral-700">
                    Kata Sandi <span class="text-red-600" aria-hidden="true">*</span>
                </label>
                <div class="relative">
                    <input
                        id="reg_password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        required
                        autocomplete="new-password"
                        placeholder="Minimal 8 karakter"
                        class="block w-full rounded-lg border border-neutral-300 bg-white py-2 pl-3 pr-10 text-sm text-neutral-900 shadow-sm placeholder:text-neutral-400 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900"
                        :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.password }"
                    />
                    <button
                        type="button"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-xs font-medium text-neutral-500 hover:text-neutral-900 focus:outline-none"
                        @click="showPassword = !showPassword"
                    >
                        {{ showPassword ? 'Tutup' : 'Lihat' }}
                    </button>
                </div>
                <p v-if="form.errors.password" class="mt-1 text-xs font-medium text-red-600">
                    {{ form.errors.password }}
                </p>
            </div>

            <BaseInput
                id="reg_password_confirmation"
                v-model="form.password_confirmation"
                label="Konfirmasi Kata Sandi"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Ulangi kata sandi"
                required
                autocomplete="new-password"
                :error="form.errors.password_confirmation"
            />

            <BaseButton
                type="submit"
                size="lg"
                class="w-full"
                :loading="form.processing"
            >
                {{ form.processing ? 'Mendaftarkan Akun...' : 'Daftar Sekarang' }}
            </BaseButton>
        </form>

        <div class="mt-5 border-t border-neutral-100 pt-4 text-center text-xs text-neutral-500">
            Sudah memiliki akun?
            <Link :href="route('login')" class="font-semibold text-neutral-900 hover:underline">
                Masuk di sini
            </Link>
        </div>
    </GuestLayout>
</template>
