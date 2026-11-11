<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BaseInput from '@/Components/UI/BaseInput.vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
            }
            if (form.errors.current_password) {
                form.reset('current_password');
            }
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-base font-semibold text-neutral-900">Ubah Kata Sandi</h2>
            <p class="mt-1 text-sm text-neutral-500">
                Gunakan kombinasi kata sandi yang kuat dan unik untuk menjaga keamanan akun.
            </p>
        </header>

        <form class="mt-6 space-y-4" @submit.prevent="updatePassword">
            <BaseInput
                id="current_password"
                ref="currentPasswordInput"
                v-model="form.current_password"
                label="Kata Sandi Saat Ini"
                type="password"
                autocomplete="current-password"
                :error="form.errors.current_password"
            />

            <BaseInput
                id="new_password"
                ref="passwordInput"
                v-model="form.password"
                label="Kata Sandi Baru"
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

            <div class="flex items-center gap-4 pt-2">
                <BaseButton type="submit" :loading="form.processing">
                    Simpan Kata Sandi
                </BaseButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm font-medium text-emerald-700">
                        Kata sandi diperbarui.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>

