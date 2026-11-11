<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'info',
        validator: (val) => ['info', 'success', 'warning', 'danger'].includes(val),
    },
    title: {
        type: String,
        default: '',
    },
    message: {
        type: String,
        default: '',
    },
    dismissible: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const styles = computed(() => {
    const map = {
        info: {
            container: 'border-neutral-200 bg-neutral-50 text-neutral-800',
            icon: 'text-neutral-700',
            closeBtn: 'text-neutral-500 hover:text-neutral-900 focus:ring-neutral-900',
            path: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        },
        success: {
            container: 'border-emerald-200 bg-emerald-50/90 text-emerald-900',
            icon: 'text-emerald-600',
            closeBtn: 'text-emerald-700 hover:text-emerald-900 focus:ring-emerald-600',
            path: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        },
        warning: {
            container: 'border-amber-200 bg-amber-50/90 text-amber-900',
            icon: 'text-amber-600',
            closeBtn: 'text-amber-700 hover:text-amber-900 focus:ring-amber-600',
            path: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        },
        danger: {
            container: 'border-red-200 bg-red-50/90 text-red-900',
            icon: 'text-red-600',
            closeBtn: 'text-red-700 hover:text-red-900 focus:ring-red-600',
            path: 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        },
    };
    return map[props.variant] || map.info;
});
</script>

<template>
    <div
        role="alert"
        class="flex items-start justify-between gap-3 rounded-xl border px-4 py-3 text-sm"
        :class="styles.container"
    >
        <div class="flex items-start gap-2.5">
            <svg
                class="mt-0.5 h-5 w-5 shrink-0"
                :class="styles.icon"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                aria-hidden="true"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="styles.path" />
            </svg>
            <div class="min-w-0">
                <p v-if="title" class="font-semibold">{{ title }}</p>
                <p v-if="message" :class="title ? 'mt-0.5 text-xs opacity-90' : 'font-medium'">
                    {{ message }}
                </p>
                <slot v-if="$slots.default" />
            </div>
        </div>

        <button
            v-if="dismissible"
            type="button"
            class="shrink-0 rounded-md p-1 opacity-75 transition hover:opacity-100 focus:outline-none focus:ring-2"
            :class="styles.closeBtn"
            aria-label="Tutup peringatan"
            @click="emit('close')"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
</template>

