<script setup>
defineProps({
    label: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    tone: {
        type: String,
        default: 'neutral',
    },
});

const badgeColors = {
    neutral: 'bg-neutral-100 text-neutral-700',
    emerald: 'bg-emerald-50 text-emerald-700',
    sky: 'bg-sky-50 text-sky-700',
    amber: 'bg-amber-50 text-amber-700',
};
</script>

<template>
    <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
        <div class="flex items-start justify-between gap-3">
            <p class="text-xs font-medium uppercase tracking-wider text-neutral-500">
                {{ label }}
            </p>
            <span
                v-if="$slots.icon"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                :class="badgeColors[tone] || badgeColors.neutral"
            >
                <slot name="icon" />
            </span>
        </div>
        <p class="mt-3 text-2xl font-semibold tracking-tight text-neutral-900">
            {{ value }}
        </p>
        <p v-if="description" class="mt-1 text-xs text-neutral-500">
            {{ description }}
        </p>
    </div>
</template>

