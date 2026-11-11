<script setup>
import BaseButton from '@/Components/UI/BaseButton.vue';

defineProps({
    title: {
        type: String,
        default: 'Belum ada data',
    },
    description: {
        type: String,
        default: 'Data yang Anda cari tidak ditemukan atau belum tersedia.',
    },
    actionLabel: {
        type: String,
        default: '',
    },
    actionHref: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['action']);
</script>

<template>
    <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-neutral-300 bg-white px-6 py-12 text-center">
        <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 text-neutral-500">
            <slot name="icon">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
            </slot>
        </div>
        <h3 class="text-sm font-semibold text-neutral-900">{{ title }}</h3>
        <p class="mt-1 max-w-sm text-sm text-neutral-500">{{ description }}</p>
        <div v-if="$slots.action || actionLabel" class="mt-5">
            <slot name="action">
                <BaseButton
                    v-if="actionHref"
                    :href="actionHref"
                    variant="outline"
                    size="sm"
                >
                    {{ actionLabel }}
                </BaseButton>
                <BaseButton
                    v-else-if="actionLabel"
                    variant="outline"
                    size="sm"
                    @click="emit('action')"
                >
                    {{ actionLabel }}
                </BaseButton>
            </slot>
        </div>
    </div>
</template>
