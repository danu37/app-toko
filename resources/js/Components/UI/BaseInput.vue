<script setup>
import { computed, ref, useId } from 'vue';

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
    type: {
        type: String,
        default: 'text',
    },
    error: {
        type: String,
        default: '',
    },
    hint: {
        type: String,
        default: '',
    },
    required: {
        type: Boolean,
        default: false,
    },
});

const generatedId = useId();
const inputId = computed(() => props.id || generatedId);
const inputRef = ref(null);

defineExpose({
    focus: () => inputRef.value?.focus(),
});
</script>

<template>
    <div>
        <label
            v-if="label"
            :for="inputId"
            class="mb-1.5 block text-sm font-medium text-neutral-700"
        >
            {{ label }}
            <span v-if="required" class="text-red-600" aria-hidden="true">*</span>
        </label>
        <div class="relative">
            <input
                :id="inputId"
                ref="inputRef"
                v-model="model"
                :type="type"
                :required="required"
                :aria-invalid="Boolean(error)"
                v-bind="$attrs"
                class="block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 placeholder:text-neutral-400 transition focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900 disabled:cursor-not-allowed disabled:bg-neutral-100 disabled:text-neutral-500"
                :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': error }"
            />
            <slot name="suffix" />
        </div>
        <p v-if="hint && !error" class="mt-1 text-xs text-neutral-500">{{ hint }}</p>
        <p v-if="error" class="mt-1 text-xs font-medium text-red-600">{{ error }}</p>
    </div>
</template>
