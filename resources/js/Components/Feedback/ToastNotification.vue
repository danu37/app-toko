<script setup>
import { computed, onUnmounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import BaseAlert from '@/Components/UI/BaseAlert.vue';

const props = defineProps({
    duration: {
        type: Number,
        default: 5000,
    },
});

const page = usePage();
const dismissed = ref(false);
let timer = null;

const successMessage = computed(() => page.props.flash?.success ?? null);
const errorMessage = computed(() => page.props.flash?.error ?? null);
const visible = computed(() => !dismissed.value && Boolean(successMessage.value || errorMessage.value));
const variant = computed(() => (errorMessage.value ? 'danger' : 'success'));
const message = computed(() => errorMessage.value || successMessage.value || '');

const clearTimer = () => {
    if (timer) {
        clearTimeout(timer);
        timer = null;
    }
};

watch(
    () => [successMessage.value, errorMessage.value],
    ([success, error]) => {
        dismissed.value = false;
        clearTimer();

        if ((success || error) && props.duration > 0) {
            timer = setTimeout(() => {
                dismissed.value = true;
            }, props.duration);
        }
    },
    { immediate: true },
);

onUnmounted(clearTimer);
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="translate-y-1 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="visible" class="mb-5 print:hidden">
            <BaseAlert
                :variant="variant"
                :message="message"
                dismissible
                @close="dismissed = true"
            />
        </div>
    </Transition>
</template>
