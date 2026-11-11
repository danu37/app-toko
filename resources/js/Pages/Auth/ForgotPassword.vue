<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import BaseAlert from '@/Components/UI/BaseAlert.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BaseInput from '@/Components/UI/BaseInput.vue';

defineProps({
    status: {
        type: String,
        default: '',
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Lupa Kata Sandi" />

        <div class="mb-5">
            <h1 class="text-lg font-semibold text-neutral-900">Lupa Kata Sandi</h1>
            <p class="mt-1 text-sm text-neutral-500">
                Masukkan alamat email akun Anda untuk menerima tautan pengaturan ulang kata sandi.
            </p>
        </div>

        <BaseAlert
            v-if="status"
            variant="success"
            :message="status"
            class="mb-4"
        />

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

            <div class="flex items-center justify-between pt-2">
                <Link :href="route('login')" class="text-xs font-medium text-neutral-600 hover:text-neutral-900">
                    &larr; Kembali ke Login
                </Link>
                <BaseButton type="submit" :loading="form.processing">
                    Kirim Tautan Reset
                </BaseButton>
            </div>
        </form>
    </GuestLayout>
</template>
