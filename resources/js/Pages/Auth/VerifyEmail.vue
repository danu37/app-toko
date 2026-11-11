<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import BaseAlert from '@/Components/UI/BaseAlert.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';

const props = defineProps({
    status: {
        type: String,
        default: '',
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head title="Verifikasi Email" />

        <div class="mb-4 text-sm text-neutral-600">
            Terima kasih telah bergabung. Silakan verifikasi alamat email Anda melalui tautan yang dikirim ke kotak masuk email Anda.
        </div>

        <BaseAlert
            v-if="verificationLinkSent"
            variant="success"
            message="Tautan verifikasi baru telah dikirim ke email Anda."
            class="mb-4"
        />

        <form class="mt-4 flex items-center justify-between gap-3" @submit.prevent="submit">
            <BaseButton type="submit" :loading="form.processing">
                Kirim Ulang Verifikasi
            </BaseButton>

            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="text-sm font-medium text-neutral-600 underline hover:text-neutral-900"
            >
                Keluar
            </Link>
        </form>
    </GuestLayout>
</template>
