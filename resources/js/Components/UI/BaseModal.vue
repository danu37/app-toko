<script setup>
import { computed, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: '',
    },
    maxWidth: {
        type: String,
        default: 'lg',
        validator: (val) => ['sm', 'md', 'lg', 'xl', '2xl'].includes(val),
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close']);

const maxWidthClass = computed(() => {
    const map = {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
    };
    return map[props.maxWidth] || map.lg;
});

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        e.preventDefault();
        close();
    }
};

watch(
    () => props.show,
    (visible) => {
        document.body.style.overflow = visible ? 'hidden' : '';
    },
);

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                role="dialog"
                aria-modal="true"
                class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto px-4 py-6"
            >
                <div
                    class="fixed inset-0 bg-neutral-900/50 backdrop-blur-sm"
                    aria-hidden="true"
                    @click="close"
                />

                <div
                    class="relative z-10 w-full overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-lg"
                    :class="maxWidthClass"
                >
                    <div
                        v-if="title || $slots.header"
                        class="flex items-center justify-between border-b border-neutral-200 px-6 py-4"
                    >
                        <slot name="header">
                            <h3 class="text-base font-semibold text-neutral-900">{{ title }}</h3>
                        </slot>
                        <button
                            v-if="closeable"
                            type="button"
                            class="rounded-lg p-1 text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-700 focus:outline-none focus:ring-2 focus:ring-neutral-900"
                            aria-label="Tutup dialog"
                            @click="close"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="p-6">
                        <slot />
                    </div>

                    <div
                        v-if="$slots.footer"
                        class="border-t border-neutral-100 bg-neutral-50/70 px-6 py-4"
                    >
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
