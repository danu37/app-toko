<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'neutral',
        validator: (val) => ['neutral', 'primary', 'dark', 'success', 'warning', 'danger', 'info', 'outline'].includes(val),
    },
    size: {
        type: String,
        default: 'md',
        validator: (val) => ['sm', 'md'].includes(val),
    },
    dot: {
        type: Boolean,
        default: false,
    },
});

const variantClasses = computed(() => {
    const map = {
        neutral: 'border-neutral-200 bg-neutral-100 text-neutral-700',
        primary: 'border-neutral-900 bg-neutral-900 text-white',
        dark: 'border-neutral-900 bg-neutral-900 text-white',
        success: 'border-emerald-200 bg-emerald-50 text-emerald-700',
        warning: 'border-amber-200 bg-amber-50 text-amber-800',
        danger: 'border-red-200 bg-red-50 text-red-700',
        info: 'border-sky-200 bg-sky-50 text-sky-700',
        outline: 'border-neutral-300 bg-white text-neutral-700',
    };
    return map[props.variant] || map.neutral;
});

const dotColor = computed(() => {
    const map = {
        neutral: 'bg-neutral-500',
        primary: 'bg-white',
        dark: 'bg-white',
        success: 'bg-emerald-600',
        warning: 'bg-amber-600',
        danger: 'bg-red-600',
        info: 'bg-sky-600',
        outline: 'bg-neutral-700',
    };
    return map[props.variant] || map.neutral;
});

const sizeClasses = computed(() => (props.size === 'sm' ? 'px-2 py-0.5 text-[11px]' : 'px-2.5 py-0.5 text-xs'));
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-full border font-medium"
        :class="[variantClasses, sizeClasses]"
    >
        <span v-if="dot" class="h-1.5 w-1.5 rounded-full" :class="dotColor" aria-hidden="true" />
        <slot />
    </span>
</template>
