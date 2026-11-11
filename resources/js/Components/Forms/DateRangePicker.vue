<script setup>
import BaseInput from '@/Components/UI/BaseInput.vue';

defineProps({
    startDate: {
        type: String,
        default: '',
    },
    endDate: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:startDate', 'update:endDate']);
</script>

<template>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <BaseInput
            id="filter_start_date"
            :model-value="startDate"
            type="date"
            label="Dari Tanggal"
            @update:model-value="emit('update:startDate', $event)"
        />
        <BaseInput
            id="filter_end_date"
            :model-value="endDate"
            type="date"
            label="Sampai Tanggal"
            @update:model-value="emit('update:endDate', $event)"
        />
    </div>
</template>

