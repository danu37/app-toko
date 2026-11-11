<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BaseInput from '@/Components/UI/BaseInput.vue';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Reset Kata Sandi" />

        <div class="mb-5">
            <h1 class="text-lg font-semibold text-neutral-900">Atur Ulang Kata Sandi</h1>
            <p class="mt-1 text-sm text-neutral-500">
                Masukkan kata sandi baru untuk akun Anda.
            </p>
        </div>

        <form class="space-y-4" @submit.prevent="submit">
            <BaseInput
                id="email"
                v-model="form.email"
                label="Email"
                type="email"
                required
                autofocus
                autocomplete="username"
                :error="form.errors.email"
            />

            <BaseInput
                id="password"
                v-model="form.password"
                label="Kata Sandi Baru"
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

            <div class="flex justify-end pt-2">
                <BaseButton type="submit" class="w-full" :loading="form.processing">
                    Simpan Kata Sandi Baru
                </BaseButton>
            </div>
        </form>
    </GuestLayout>
</template>
