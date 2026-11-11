<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'spinner',
        validator: (val) => ['spinner', 'skeleton', 'overlay'].includes(val),
    },
    size: {
        type: String,
        default: 'md',
        validator: (val) => ['xs', 'sm', 'md', 'lg'].includes(val),
    },
    text: {
        type: String,
        default: '',
    },
    rows: {
        type: Number,
        default: 3,
    },
});

const spinnerSizes = computed(() => {
    const map = {
        xs: 'h-3.5 w-3.5',
        sm: 'h-4 w-4',
        md: 'h-6 w-6',
        lg: 'h-8 w-8',
    };
    return map[props.size] || map.md;
});
</script>

<template>
    <div
        v-if="variant === 'skeleton'"
        role="status"
        aria-live="polite"
        class="w-full animate-pulse space-y-3"
    >
        <div
            v-for="row in rows"
            :key="row"
            class="flex items-center justify-between gap-4 rounded-lg border border-neutral-100 bg-neutral-50/70 px-4 py-3"
        >
            <div class="h-3.5 w-2/5 rounded bg-neutral-200" />
            <div class="h-3.5 w-1/5 rounded bg-neutral-200" />
            <div class="h-3.5 w-1/6 rounded bg-neutral-200" />
        </div>
        <span class="sr-only">{{ text || 'Memuat data...' }}</span>
    </div>

    <div
        v-else-if="variant === 'overlay'"
        role="status"
        aria-live="polite"
        class="flex flex-col items-center justify-center gap-2.5 rounded-xl bg-white/80 py-10 text-center backdrop-blur-sm"
    >
        <svg
            class="animate-spin text-neutral-900"
            :class="spinnerSizes"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3.5" />
            <path class="opacity-85" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
        <span v-if="text" class="text-xs font-medium text-neutral-600">{{ text }}</span>
        <span v-else class="sr-only">Memuat...</span>
    </div>

    <span
        v-else
        role="status"
        aria-live="polite"
        class="inline-flex items-center gap-2"
    >
        <svg
            class="animate-spin"
            :class="spinnerSizes"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-85" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
        <span v-if="text" class="text-xs font-medium text-neutral-600">{{ text }}</span>
        <span v-else class="sr-only">Memuat...</span>
    </span>
</template>

