<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        default: null,
    },
    variant: {
        type: String,
        default: 'neutral',
    },
});

const classesFor = (variant) => {
    if (variant === 'danger') {
        return 'text-red-600 hover:bg-red-50 hover:text-red-700';
    }
    if (variant === 'primary') {
        return 'text-neutral-900 hover:bg-neutral-100';
    }
    return 'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900';
};
</script>

<template>
    <Link
        v-if="href"
        :href="href"
        class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium transition focus:outline-none focus:ring-2 focus:ring-neutral-900"
        :class="classesFor(variant)"
    >
        <slot />
    </Link>
    <button
        v-else
        type="button"
        class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium transition focus:outline-none focus:ring-2 focus:ring-neutral-900"
        :class="classesFor(variant)"
    >
        <slot />
    </button>
</template>

