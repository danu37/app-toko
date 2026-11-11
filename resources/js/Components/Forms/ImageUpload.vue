<script setup>
import { computed, onUnmounted, ref, watch } from 'vue';
import {
    ALLOWED_IMAGE_EXTENSIONS,
    ALLOWED_IMAGE_TYPES,
    MAX_IMAGE_BYTES,
    autoCompressImage,
    formatFileSize,
} from '@/Utils/imageCompression';

const props = defineProps({
    modelValue: {
        type: [File, null],
        default: null,
    },
    existingUrl: {
        type: String,
        default: null,
    },
    label: {
        type: String,
        default: 'Gambar Produk',
    },
    error: {
        type: String,
        default: '',
    },
    maxSizeKb: {
        type: Number,
        default: 2048,
    },
});

const emit = defineEmits(['update:modelValue', 'compressing']);
const objectUrl = ref(null);
const fileInput = ref(null);
const clientError = ref('');
const isCompressing = ref(false);
const compressionMeta = ref(null);
let activeJobId = 0;

const maxBytesLimit = computed(() =>
    props.maxSizeKb > 0 ? props.maxSizeKb * 1024 : MAX_IMAGE_BYTES,
);

const revokePreviewUrl = () => {
    if (objectUrl.value) {
        URL.revokeObjectURL(objectUrl.value);
        objectUrl.value = null;
    }
};

watch(
    () => props.modelValue,
    (file) => {
        revokePreviewUrl();
        if (file instanceof File) {
            objectUrl.value = URL.createObjectURL(file);
        } else if (!isCompressing.value) {
            compressionMeta.value = null;
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        }
    },
);

onUnmounted(revokePreviewUrl);

const previewUrl = computed(() => objectUrl.value || props.existingUrl);
const displayError = computed(() => clientError.value || props.error);

const setCompressingState = (value) => {
    isCompressing.value = value;
    emit('compressing', value);
};

const onFileChange = async (event) => {
    const file = event.target.files?.[0] ?? null;
    const jobId = ++activeJobId;

    clientError.value = '';
    compressionMeta.value = null;

    if (!file) {
        setCompressingState(false);
        emit('update:modelValue', null);
        return;
    }

    const extension = file.name.toLowerCase().split('.').pop() || '';
    if (!ALLOWED_IMAGE_EXTENSIONS.includes(extension) || (file.type && !ALLOWED_IMAGE_TYPES.includes(file.type))) {
        clientError.value = 'Format file tidak didukung. Gunakan gambar JPG, JPEG, PNG, atau WEBP.';
        emit('update:modelValue', null);
        event.target.value = '';
        return;
    }

    setCompressingState(true);
    emit('update:modelValue', null);

    // Beri kesempatan browser merender indikator loading sebelum proses Canvas berjalan.
    await new Promise((resolve) => requestAnimationFrame(resolve));

    try {
        const result = await autoCompressImage(file, maxBytesLimit.value);
        if (jobId !== activeJobId) {
            return;
        }

        compressionMeta.value = {
            originalSizeText: formatFileSize(result.originalSize),
            compressedSizeText: formatFileSize(result.compressedSize),
            savedPercent: result.savedPercent,
            wasCompressed: result.wasCompressed,
            convertedFormat: result.convertedFormat,
            statusText: result.statusText,
            originalDimensions: `${result.originalDimensions.width} × ${result.originalDimensions.height}`,
            finalDimensions: `${result.finalDimensions.width} × ${result.finalDimensions.height}`,
            formatLabel: (result.file.name.split('.').pop() || 'img').toUpperCase(),
        };

        emit('update:modelValue', result.file);
    } catch (err) {
        if (jobId !== activeJobId) {
            return;
        }

        clientError.value = err?.message || 'Gagal mengompres gambar secara otomatis.';
        compressionMeta.value = null;
        emit('update:modelValue', null);
        if (fileInput.value) {
            fileInput.value.value = '';
        }
    } finally {
        if (jobId === activeJobId) {
            setCompressingState(false);
        }
    }
};

const clearSelection = () => {
    activeJobId++;
    clientError.value = '';
    compressionMeta.value = null;
    setCompressingState(false);
    emit('update:modelValue', null);
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};
</script>

<template>
    <div>
        <label class="mb-1.5 block text-sm font-medium text-neutral-700">{{ label }}</label>
        <div class="rounded-xl border border-neutral-200 bg-neutral-50/70 p-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="relative flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-neutral-200 bg-white">
                    <img
                        v-if="previewUrl"
                        :src="previewUrl"
                        alt="Preview gambar"
                        class="h-full w-full object-contain"
                    />
                    <svg
                        v-else
                        class="h-8 w-8 text-neutral-300"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>

                    <div
                        v-if="isCompressing"
                        class="absolute inset-0 flex items-center justify-center bg-white/80"
                    >
                        <svg class="h-5 w-5 animate-spin text-neutral-900" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                    </div>
                </div>

                <div class="min-w-0 flex-1 space-y-2">
                    <input
                        ref="fileInput"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        :disabled="isCompressing"
                        class="block w-full text-sm text-neutral-600 file:mr-3 file:rounded-lg file:border file:border-neutral-300 file:bg-white file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-neutral-700 hover:file:bg-neutral-50 disabled:cursor-wait disabled:opacity-60"
                        @change="onFileChange"
                    />
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-xs text-neutral-500">
                            Format JPG, PNG, atau WEBP. File &gt; {{ Math.round(maxSizeKb / 1024) }} MB otomatis dikompres hingga maksimal {{ Math.round(maxSizeKb / 1024) }} MB.
                        </p>
                        <button
                            v-if="modelValue && !isCompressing"
                            type="button"
                            class="text-xs font-medium text-red-600 hover:underline"
                            @click="clearSelection"
                        >
                            Batalkan file baru
                        </button>
                    </div>

                    <div
                        v-if="isCompressing"
                        role="status"
                        class="inline-flex items-center gap-2 rounded-lg border border-neutral-200 bg-white px-3 py-1.5 text-xs font-medium text-neutral-700"
                    >
                        <svg class="h-3.5 w-3.5 animate-spin text-neutral-900" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        <span>Memeriksa &amp; mengompres gambar otomatis...</span>
                    </div>
                </div>
            </div>

            <div
                v-if="compressionMeta && !isCompressing && !displayError"
                data-compression-summary
                class="mt-3 rounded-lg border border-neutral-200 bg-white p-3 text-xs"
            >
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-neutral-100 pb-2">
                    <span
                        class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold"
                        :class="
                            compressionMeta.wasCompressed
                                ? 'bg-emerald-50 text-emerald-700'
                                : 'bg-neutral-100 text-neutral-700'
                        "
                    >
                        Status: {{ compressionMeta.statusText }}
                    </span>
                    <span class="text-[11px] text-neutral-500">
                        {{ compressionMeta.originalDimensions }}
                        <template v-if="compressionMeta.finalDimensions !== compressionMeta.originalDimensions">
                            &rarr; {{ compressionMeta.finalDimensions }}
                        </template>
                        px · {{ compressionMeta.formatLabel }}
                    </span>
                </div>

                <dl class="mt-2 grid grid-cols-3 gap-2 text-neutral-700">
                    <div>
                        <dt class="text-[11px] text-neutral-500">Original</dt>
                        <dd class="font-semibold text-neutral-900">{{ compressionMeta.originalSizeText }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] text-neutral-500">Compressed</dt>
                        <dd class="font-semibold text-neutral-900">{{ compressionMeta.compressedSizeText }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] text-neutral-500">Saved</dt>
                        <dd
                            class="font-semibold"
                            :class="compressionMeta.wasCompressed ? 'text-emerald-700' : 'text-neutral-700'"
                        >
                            {{ compressionMeta.savedPercent }}%
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <p v-if="displayError" class="mt-1.5 text-xs font-medium text-red-600">{{ displayError }}</p>
    </div>
</template>
