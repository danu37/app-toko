<script setup>
import BaseButton from '@/Components/UI/BaseButton.vue';
import BaseModal from '@/Components/UI/BaseModal.vue';

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Konfirmasi Hapus',
    },
    message: {
        type: String,
        default: 'Apakah Anda yakin ingin melanjutkan tindakan ini? Perubahan tidak dapat dibatalkan.',
    },
    confirmLabel: {
        type: String,
        default: 'Hapus',
    },
    cancelLabel: {
        type: String,
        default: 'Batal',
    },
    variant: {
        type: String,
        default: 'danger',
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'confirm']);
</script>

<template>
    <BaseModal :show="show" :title="title" max-width="md" @close="emit('close')">
        <p class="text-sm leading-relaxed text-neutral-600">{{ message }}</p>
        <div class="mt-6 flex items-center justify-end gap-2.5">
            <BaseButton variant="secondary" :disabled="loading" @click="emit('close')">
                {{ cancelLabel }}
            </BaseButton>
            <BaseButton :variant="variant" :loading="loading" @click="emit('confirm')">
                {{ confirmLabel }}
            </BaseButton>
        </div>
    </BaseModal>
</template>
