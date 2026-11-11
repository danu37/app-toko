<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BaseInput from '@/Components/UI/BaseInput.vue';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Konfirmasi Kata Sandi" />

        <div class="mb-5">
            <h1 class="text-lg font-semibold text-neutral-900">Konfirmasi Keamanan</h1>
            <p class="mt-1 text-sm text-neutral-500">
                Area ini dilindungi. Masukkan kata sandi Anda sebelum melanjutkan.
            </p>
        </div>

        <form class="space-y-4" @submit.prevent="submit">
            <BaseInput
                id="password"
                v-model="form.password"
                label="Kata Sandi"
                type="password"
                required
                autofocus
                autocomplete="current-password"
                :error="form.errors.password"
            />

            <div class="flex justify-end pt-2">
                <BaseButton type="submit" :loading="form.processing">
                    Konfirmasi
                </BaseButton>
            </div>
        </form>
    </GuestLayout>
</template>
