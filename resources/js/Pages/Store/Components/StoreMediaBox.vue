<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    src: {
        type: String,
        default: null,
    },
    alt: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        default: '',
    },
    variant: {
        type: String,
        default: 'product',
        validator: (val) => ['product', 'category'].includes(val),
    },
    aspectClass: {
        type: String,
        default: 'aspect-[4/5]',
    },
    eager: {
        type: Boolean,
        default: false,
    },
});

const loaded = ref(false);
const errored = ref(false);

watch(
    () => props.src,
    () => {
        loaded.value = false;
        errored.value = false;
    },
);
</script>

<template>
    <div class="relative w-full overflow-hidden bg-stone-100" :class="aspectClass">
        <template v-if="src && !errored">
            <div
                v-if="!loaded"
                class="absolute inset-0 animate-pulse bg-stone-200/70"
                aria-hidden="true"
            />
            <img
                :src="src"
                :alt="alt"
                :loading="eager ? 'eager' : 'lazy'"
                decoding="async"
                class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]"
                :class="loaded ? 'opacity-100' : 'opacity-0'"
                @load="loaded = true"
                @error="errored = true"
            />
        </template>

        <div
            v-else
            class="flex h-full w-full flex-col items-center justify-center bg-gradient-to-b from-stone-100 to-stone-200/60 p-6 text-center"
        >
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl border border-neutral-200/80 bg-white/90 text-neutral-400 shadow-sm">
                <svg
                    v-if="variant === 'category'"
                    class="h-6 w-6"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
                    />
                </svg>
                <svg
                    v-else
                    class="h-6 w-6"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
                    />
                </svg>
            </div>
            <span
                v-if="label"
                class="mt-3 max-w-full truncate rounded-full border border-neutral-200 bg-white/80 px-3 py-0.5 text-[11px] font-medium uppercase tracking-wider text-neutral-500"
            >
                {{ label }}
            </span>
        </div>
    </div>
</template>

