<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    align: {
        type: String,
        default: 'right',
        validator: (val) => ['left', 'right'].includes(val),
    },
    width: {
        type: String,
        default: '48',
        validator: (val) => ['44', '48', '56', '64'].includes(val),
    },
    closeOnClick: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['open', 'close']);
const isOpen = ref(false);

const openMenu = () => {
    isOpen.value = true;
    emit('open');
};

const closeMenu = () => {
    if (!isOpen.value) return;
    isOpen.value = false;
    emit('close');
};

const toggleMenu = () => {
    if (isOpen.value) closeMenu();
    else openMenu();
};

const closeOnEscape = (e) => {
    if (isOpen.value && e.key === 'Escape') {
        closeMenu();
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));

const widthClass = computed(() => {
    const map = {
        44: 'w-44',
        48: 'w-48',
        56: 'w-56',
        64: 'w-64',
    };
    return map[props.width] || 'w-48';
});

const alignmentClass = computed(() =>
    props.align === 'left' ? 'left-0 origin-top-left' : 'right-0 origin-top-right',
);
</script>

<template>
    <div class="relative inline-block text-left">
        <div @click="toggleMenu">
            <slot name="trigger" :open="isOpen" :toggle="toggleMenu" />
        </div>

        <div
            v-if="isOpen"
            class="fixed inset-0 z-40"
            aria-hidden="true"
            @click="closeMenu"
        />

        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="scale-95 opacity-0"
            enter-to-class="scale-100 opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="scale-100 opacity-100"
            leave-to-class="scale-95 opacity-0"
        >
            <div
                v-if="isOpen"
                class="absolute z-50 mt-2 overflow-hidden rounded-xl border border-neutral-200 bg-white py-1 shadow-sm"
                :class="[widthClass, alignmentClass]"
                @click="closeOnClick && closeMenu()"
            >
                <slot name="content" :close="closeMenu" />
            </div>
        </Transition>
    </div>
</template>

