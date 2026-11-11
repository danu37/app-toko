<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BaseInput from '@/Components/UI/BaseInput.vue';
import BaseModal from '@/Components/UI/BaseModal.vue';

const confirmingUserDeletion = ref(false);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <section class="space-y-4">
        <header>
            <h2 class="text-base font-semibold text-neutral-900">Hapus Akun</h2>
            <p class="mt-1 text-sm text-neutral-500">
                Penghapusan akun bersifat permanen. Pastikan seluruh pekerjaan operasional Anda telah diselesaikan sebelum melanjutkan.
            </p>
        </header>

        <BaseButton variant="danger" @click="confirmUserDeletion">
            Hapus Akun Saya
        </BaseButton>

        <BaseModal :show="confirmingUserDeletion" title="Konfirmasi Hapus Akun" max-width="md" @close="closeModal">
            <p class="text-sm text-neutral-600">
                Masukkan kata sandi Anda untuk mengonfirmasi penghapusan akun secara permanen.
            </p>

            <div class="mt-4">
                <BaseInput
                    id="delete_account_password"
                    v-model="form.password"
                    label="Kata Sandi"
                    type="password"
                    placeholder="Masukkan kata sandi"
                    :error="form.errors.password"
                    @keyup.enter="deleteUser"
                />
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <BaseButton variant="secondary" :disabled="form.processing" @click="closeModal">
                    Batal
                </BaseButton>
                <BaseButton variant="danger" :loading="form.processing" @click="deleteUser">
                    Ya, Hapus Akun
                </BaseButton>
            </div>
        </BaseModal>
    </section>
</template>

