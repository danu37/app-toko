<script setup>
const model = defineModel({
    type: String,
    default: '',
});

defineProps({
    placeholder: {
        type: String,
        default: 'Cari data...',
    },
    label: {
        type: String,
        default: 'Pencarian',
    },
});

const emit = defineEmits(['search', 'clear']);

const handleClear = () => {
    model.value = '';
    emit('clear');
    emit('search', '');
};
</script>

<template>
    <div class="relative w-full">
        <label class="sr-only">{{ label }}</label>
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-neutral-400">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>
        <input
            v-model="model"
            type="search"
            :placeholder="placeholder"
            class="block w-full rounded-lg border border-neutral-300 bg-white py-2 pl-9 pr-9 text-sm text-neutral-900 placeholder:text-neutral-400 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900"
            @keyup.enter="emit('search', model)"
        />
        <button
            v-if="model"
            type="button"
            class="absolute inset-y-0 right-0 flex items-center pr-3 text-neutral-400 hover:text-neutral-700"
            aria-label="Bersihkan pencarian"
            @click="handleClear"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
</template>

