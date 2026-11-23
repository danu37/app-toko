<script setup>
import { computed } from 'vue';

const props = defineProps({
    value: {
        type: [String, Number],
        required: true,
    },
    height: {
        type: Number,
        default: 24,
    },
    showText: {
        type: Boolean,
        default: true,
    },
    textClass: {
        type: String,
        default: 'text-[9px]',
    },
});

// Pola Code 128 (107 simbol, baris lebar bar dan space bergantian)
const CODE128_PATTERNS = [
    '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
    '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
    '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
    '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
    '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
    '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
    '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
    '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
    '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
    '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
    '114131', '311141', '411131', '211412', '211214', '211232', '2331112',
];

const barcodeData = computed(() => {
    const raw = String(props.value ?? '').trim();
    if (!raw) return { bars: [], totalWidth: 0, text: '' };

    let sum = 104; // Start Code B
    const symbols = [104];

    for (let i = 0; i < raw.length; i++) {
        const code = raw.charCodeAt(i) - 32;
        const safeCode = code >= 0 && code <= 95 ? code : 0;
        symbols.push(safeCode);
        sum += safeCode * (i + 1);
    }

    const checksum = sum % 103;
    symbols.push(checksum);
    symbols.push(106); // Stop Code

    const bars = [];
    let currentX = 0;

    for (const sym of symbols) {
        const pattern = CODE128_PATTERNS[sym] || '212222';
        let isBar = true;
        for (let j = 0; j < pattern.length; j++) {
            const width = parseInt(pattern[j], 10);
            if (isBar) {
                bars.push({ x: currentX, width });
            }
            currentX += width;
            isBar = !isBar;
        }
    }

    return { bars, totalWidth: currentX, text: raw };
});
</script>

<template>
    <div v-if="barcodeData.bars.length > 0" class="inline-block w-full max-w-full text-center">
        <svg
            :viewBox="`0 0 ${barcodeData.totalWidth} ${height}`"
            preserveAspectRatio="none"
            class="block w-full"
            :style="{ height: `${height}px` }"
            aria-hidden="true"
        >
            <rect
                v-for="(bar, i) in barcodeData.bars"
                :key="i"
                :x="bar.x"
                y="0"
                :width="bar.width"
                :height="height"
                fill="#000000"
            />
        </svg>
        <span
            v-if="showText"
            class="mt-0.5 block truncate font-mono font-medium leading-tight tracking-wider text-neutral-800 print:text-black"
            :class="textClass"
        >
            {{ barcodeData.text }}
        </span>
    </div>
</template>

