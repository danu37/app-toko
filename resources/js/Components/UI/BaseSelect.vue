<script setup>
import { computed, useId } from 'vue';

defineOptions({ inheritAttrs: false });

const model = defineModel({
    type: [String, Number, null],
    default: '',
});

const props = defineProps({
    id: {
        type: String,
        default: undefined,
    },
    label: {
        type: String,
        default: '',
    },
    options: {
        type: Array,
        default: () => [],
    },
    optionValue: {
        type: String,
        default: 'value',
    },
    optionLabel: {
        type: String,
        default: 'label',
    },
    placeholder: {
        type: String,
        default: 'Pilih opsi...',
    },
    hint: {
        type: String,
        default: '',
    },
    error: {
        type: String,
        default: '',
    },
    required: {
        type: Boolean,
        default: false,
    },
});

const generatedId = useId();
const selectId = computed(() => props.id || generatedId);
</script>

<template>
    <div>
        <label
            v-if="label"
            :for="selectId"
            class="mb-1.5 block text-sm font-medium text-neutral-700"
        >
            {{ label }}
            <span v-if="required" class="text-red-600" aria-hidden="true">*</span>
        </label>
        <select
            :id="selectId"
            v-model="model"
            :required="required"
            :aria-invalid="Boolean(error)"
            v-bind="$attrs"
            class="block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 transition focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100 disabled:text-neutral-500"
            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': error }"
        >
            <option v-if="placeholder" value="">{{ placeholder }}</option>
            <slot>
                <option
                    v-for="opt in options"
                    :key="opt[optionValue]"
                    :value="opt[optionValue]"
                >
                    {{ opt[optionLabel] }}
                </option>
            </slot>
        </select>
        <p v-if="hint && !error" class="mt-1 text-xs text-neutral-500">{{ hint }}</p>
        <p v-if="error" class="mt-1 text-xs font-medium text-red-600">{{ error }}</p>
    </div>
</template>
