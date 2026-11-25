<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Checkbox from '@/Components/Checkbox.vue';
import BaseAlert from '@/Components/UI/BaseAlert.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BaseInput from '@/Components/UI/BaseInput.vue';
import { useRfidStoreScanner } from '@/Composables/useRfidStoreScanner';

defineProps({
    canResetPassword: {
        type: Boolean,
        default: false,
    },
    status: {
        type: String,
        default: '',
    },
});

const showPassword = ref(false);
const { rfidState } = useRfidStoreScanner();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Masuk" />

        <div class="mb-5">
            <h1 class="text-lg font-semibold text-neutral-900">Masuk ke Akun Anda</h1>
            <p class="mt-1 text-sm text-neutral-500">
                Akses sistem untuk Admin, Kasir, dan Pelanggan.
            </p>
        </div>

        <!-- Status / Flash Alert -->
        <BaseAlert
            v-if="status"
            variant="success"
            :message="status"
            class="mb-4"
        />

        <!-- Error Alert (Credentials atau RFID) -->
        <BaseAlert
            v-if="rfidState.error"
            variant="danger"
            :message="rfidState.error"
            class="mb-4"
        />
        <BaseAlert
            v-else-if="rfidState.success"
            variant="success"
            :message="rfidState.success"
            class="mb-4"
        />
        <BaseAlert
            v-else-if="form.errors.email || form.errors.password"
            variant="danger"
            :message="form.errors.email || form.errors.password"
            class="mb-4"
        />

        <form class="space-y-4" @submit.prevent="submit">
            <BaseInput
                id="email"
                v-model="form.email"
                label="Alamat Email"
                type="email"
                placeholder="nama@toko.test"
                required
                autofocus
                autocomplete="username"
                :error="form.errors.email"
            />

            <div>
                <div class="mb-1.5 flex items-center justify-between">
                    <label for="password" class="block text-sm font-medium text-neutral-700">
                        Kata Sandi <span class="text-red-600" aria-hidden="true">*</span>
                    </label>
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-xs font-medium text-neutral-600 transition hover:text-neutral-900 hover:underline"
                    >
                        Lupa kata sandi?
                    </Link>
                </div>

                <div class="relative">
                    <input
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        required
                        autocomplete="current-password"
                        placeholder="Masukkan kata sandi"
                        class="block w-full rounded-lg border border-neutral-300 bg-white py-2 pl-3 pr-10 text-sm text-neutral-900 shadow-sm placeholder:text-neutral-400 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900"
                        :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.password }"
                    />
                    <button
                        type="button"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-xs font-medium text-neutral-500 hover:text-neutral-900 focus:outline-none"
                        :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Lihat kata sandi'"
                        @click="showPassword = !showPassword"
                    >
                        {{ showPassword ? 'Tutup' : 'Lihat' }}
                    </button>
                </div>
                <p v-if="form.errors.password" class="mt-1 text-xs font-medium text-red-600">
                    {{ form.errors.password }}
                </p>
            </div>

            <div class="flex items-center justify-between pt-1">
                <label class="inline-flex cursor-pointer items-center gap-2">
                    <Checkbox v-model:checked="form.remember" name="remember" />
                    <span class="text-sm text-neutral-600">Ingat sesi saya</span>
                </label>
            </div>

            <BaseButton
                type="submit"
                size="lg"
                class="w-full"
                :loading="form.processing || rfidState.processing"
            >
                {{ rfidState.processing ? 'Memverifikasi Kartu RFID...' : form.processing ? 'Memproses...' : 'Masuk' }}
            </BaseButton>
        </form>

        <div class="mt-5 space-y-2.5 border-t border-neutral-100 pt-4 text-center text-xs text-neutral-500">
            <p>
                Belum punya akun?
                <Link :href="route('register')" class="font-semibold text-neutral-900 hover:underline">
                    Daftar sebagai Pelanggan
                </Link>
            </p>
            <div class="flex items-center justify-center gap-2 text-neutral-400">
                <span class="h-2 w-2 rounded-full" :class="rfidState.processing ? 'bg-amber-500 animate-ping' : 'bg-emerald-500'" />
                <span>
                    {{ rfidState.processing ? 'Sedang memproses kartu RFID...' : 'Atau tempelkan kartu RFID pada reader untuk login instan.' }}
                </span>
            </div>
        </div>
    </GuestLayout>
</template>
