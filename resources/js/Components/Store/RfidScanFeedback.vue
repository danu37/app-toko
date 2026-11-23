<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    rfidState: {
        type: Object,
        required: true,
    },
});

defineEmits(['close']);
</script>

<template>
    <!-- Floating RFID Feedback Overlay / Toast -->
    <aside aria-label="Notifikasi Pemindaian RFID">
        <!-- 1. State: Sedang Memverifikasi / Processing -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="-translate-y-4 opacity-0 scale-95"
            enter-to-class="translate-y-0 opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="-translate-y-2 opacity-0 scale-95"
        >
            <div
                v-if="rfidState.processing"
                class="fixed inset-x-0 top-6 z-50 flex justify-center px-4 pointer-events-none"
            >
                <div class="pointer-events-auto flex items-center gap-3.5 rounded-2xl border border-neutral-800 bg-neutral-900/95 px-5 py-3.5 text-white shadow-2xl backdrop-blur-md">
                    <div class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-neutral-800 text-emerald-400">
                        <svg class="h-6 w-6 animate-pulse" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <rect x="2" y="5" width="20" height="14" rx="2.5" stroke-width="1.8" />
                            <line x1="2" y1="10" x2="22" y2="10" stroke-width="1.8" />
                            <circle cx="6" cy="15" r="1.5" fill="currentColor" />
                            <path d="M14 13.5a2.5 2.5 0 0 1 0 3" stroke-linecap="round" stroke-width="1.8" />
                            <path d="M17 12a5.5 5.5 0 0 1 0 6" stroke-linecap="round" stroke-width="1.8" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-block h-2 w-2 rounded-full bg-emerald-400 animate-ping" />
                            <p class="text-sm font-semibold tracking-tight">Memverifikasi Kartu RFID...</p>
                        </div>
                        <p class="mt-0.5 text-xs text-neutral-300">
                            UID: <span class="font-mono font-medium text-emerald-300">{{ rfidState.scannedUid }}</span> &bull; Mengalihkan ke dashboard...
                        </p>
                    </div>
                    <div class="ml-2">
                        <svg class="h-5 w-5 animate-spin text-neutral-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                        </svg>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- 2. State: Error / Ditolak -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="-translate-y-4 opacity-0 scale-95"
            enter-to-class="translate-y-0 opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="-translate-y-2 opacity-0 scale-95"
        >
            <div
                v-if="rfidState.error"
                class="fixed inset-x-0 top-6 z-50 flex justify-center px-4"
            >
                <div class="flex max-w-lg items-start gap-3 rounded-2xl border border-red-200 bg-white p-4 text-neutral-900 shadow-2xl ring-1 ring-black/5">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <circle cx="12" cy="12" r="10" stroke-width="2" />
                            <line x1="12" y1="8" x2="12" y2="12" stroke-width="2" />
                            <line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1 pt-0.5">
                        <h4 class="text-sm font-semibold text-red-900">Gagal Masuk via RFID</h4>
                        <p class="mt-0.5 text-xs text-neutral-600 leading-relaxed">
                            {{ rfidState.error }}
                        </p>
                        <div class="mt-2.5 flex items-center gap-3">
                            <Link
                                :href="route('login')"
                                class="text-xs font-semibold text-neutral-900 underline hover:text-neutral-700"
                            >
                                Masuk Manual dengan Email &amp; Sandi &rarr;
                            </Link>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="shrink-0 rounded-lg p-1 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-700"
                        aria-label="Tutup notifikasi"
                        @click="$emit('close')"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </Transition>

        <!-- 3. State: Info (Misal sudah login dan diarahkan ke dashboard) -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="-translate-y-4 opacity-0 scale-95"
            enter-to-class="translate-y-0 opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="-translate-y-2 opacity-0 scale-95"
        >
            <div
                v-if="rfidState.info"
                class="fixed inset-x-0 top-6 z-50 flex justify-center px-4"
            >
                <div class="flex max-w-md items-center gap-3 rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-neutral-900 shadow-xl">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-neutral-900 text-white text-xs font-bold">
                        TK
                    </span>
                    <p class="text-xs font-medium text-neutral-700">
                        {{ rfidState.info }}
                    </p>
                    <button
                        type="button"
                        class="ml-auto shrink-0 rounded-lg p-1 text-neutral-400 hover:text-neutral-700"
                        @click="$emit('close')"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </Transition>

        <!-- 4. State: Sukses / Mengalihkan -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="-translate-y-4 opacity-0 scale-95"
            enter-to-class="translate-y-0 opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="-translate-y-2 opacity-0 scale-95"
        >
            <div
                v-if="rfidState.success"
                class="fixed inset-x-0 top-6 z-50 flex justify-center px-4 pointer-events-none"
            >
                <div class="pointer-events-auto flex items-center gap-3 rounded-2xl border border-emerald-600 bg-emerald-600 px-5 py-3 text-white shadow-xl">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <p class="text-xs font-semibold">{{ rfidState.success }}</p>
                </div>
            </div>
        </Transition>
    </aside>
</template>

