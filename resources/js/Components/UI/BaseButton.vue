<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import BaseLoading from '@/Components/UI/BaseLoading.vue';

const props = defineProps({
    type: {
        type: String,
        default: 'button',
    },
    variant: {
        type: String,
        default: 'primary',
        validator: (val) => ['primary', 'secondary', 'outline', 'danger', 'ghost', 'emerald'].includes(val),
    },
    size: {
        type: String,
        default: 'md',
        validator: (val) => ['sm', 'md', 'lg'].includes(val),
    },
    href: {
        type: String,
        default: null,
    },
    external: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    block: {
        type: Boolean,
        default: false,
    },
});

const variantClasses = computed(() => {
    const map = {
        primary: 'border-transparent bg-neutral-900 text-white hover:bg-neutral-800 focus:ring-neutral-900',
        secondary: 'border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50 focus:ring-neutral-500',
        outline: 'border-neutral-900 bg-transparent text-neutral-900 hover:bg-neutral-900 hover:text-white focus:ring-neutral-900',
        danger: 'border-transparent bg-red-600 text-white hover:bg-red-700 focus:ring-red-600',
        ghost: 'border-transparent bg-transparent text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900 focus:ring-neutral-400',
        emerald: 'border-transparent bg-emerald-700 text-white hover:bg-emerald-800 focus:ring-emerald-700',
    };
    return map[props.variant] || map.primary;
});

const sizeClasses = computed(() => {
    const map = {
        sm: 'px-3 py-1.5 text-xs',
        md: 'px-4 py-2 text-sm',
        lg: 'px-5 py-2.5 text-sm',
    };
    return map[props.size] || map.md;
});

const classes = computed(() => [
    'inline-flex items-center justify-center gap-2 rounded-lg border font-medium transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
    props.block ? 'w-full' : '',
    variantClasses.value,
    sizeClasses.value,
]);
</script>

<template>
    <a v-if="href && external" :href="href" :class="classes">
        <slot />
    </a>
    <Link v-else-if="href" :href="href" :class="classes">
        <slot />
    </Link>
    <button
        v-else
        :type="type"
        :disabled="disabled || loading"
        :aria-busy="loading"
        :class="classes"
    >
        <BaseLoading v-if="loading" variant="spinner" size="sm" />
        <slot />
    </button>
</template>
