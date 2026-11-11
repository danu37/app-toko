<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BaseInput from '@/Components/UI/BaseInput.vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
        default: false,
    },
    status: {
        type: String,
        default: '',
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user?.name ?? '',
    email: user?.email ?? '',
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-base font-semibold text-neutral-900">Informasi Profil</h2>
            <p class="mt-1 text-sm text-neutral-500">
                Perbarui nama dan alamat email akun Anda. Perubahan role hanya dapat dilakukan oleh Administrator.
            </p>
        </header>

        <form class="mt-6 space-y-4" @submit.prevent="form.patch(route('profile.update'))">
            <BaseInput
                id="profile_name"
                v-model="form.name"
                label="Nama Lengkap"
                type="text"
                required
                autofocus
                autocomplete="name"
                :error="form.errors.name"
            />

            <BaseInput
                id="profile_email"
                v-model="form.email"
                label="Alamat Email"
                type="email"
                required
                autocomplete="username"
                :error="form.errors.email"
            />

            <div v-if="mustVerifyEmail && user && user.email_verified_at === null">
                <p class="mt-2 text-sm text-neutral-700">
                    Alamat email Anda belum terverifikasi.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="font-medium text-neutral-900 underline hover:text-neutral-700"
                    >
                        Kirim ulang tautan verifikasi.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-emerald-700"
                >
                    Tautan verifikasi baru telah dikirim ke email Anda.
                </div>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <BaseButton type="submit" :loading="form.processing">
                    Simpan Profil
                </BaseButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm font-medium text-emerald-700">
                        Tersimpan.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>

