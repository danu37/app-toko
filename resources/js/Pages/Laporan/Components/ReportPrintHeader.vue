<script setup>
import { computed } from 'vue';
import { useDate } from '@/Composables/useDate';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    storeName: {
        type: String,
        default: 'TokoKita',
    },
    startDate: {
        type: String,
        default: '',
    },
    endDate: {
        type: String,
        default: '',
    },
    summaryParts: {
        type: Array,
        default: () => [],
    },
});

const { formatDate, formatDateTime } = useDate();

const periodLabel = computed(() => {
    if (props.startDate && props.endDate) {
        return `${formatDate(props.startDate)} s.d. ${formatDate(props.endDate)}`;
    }
    if (props.startDate) {
        return `Mulai ${formatDate(props.startDate)}`;
    }
    if (props.endDate) {
        return `Sampai ${formatDate(props.endDate)}`;
    }
    return 'Semua Periode';
});

const printedAt = computed(() => formatDateTime(new Date()));
</script>

<template>
    <div class="hidden border-b-2 border-neutral-900 pb-3 print:block">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-neutral-600">
                    {{ storeName }}
                </p>
                <h2 class="text-lg font-bold text-neutral-900">{{ title }}</h2>
            </div>
            <div class="text-right text-xs text-neutral-600">
                <p>Periode: {{ periodLabel }}</p>
                <p>Dicetak: {{ printedAt }}</p>
            </div>
        </div>

        <p v-if="summaryParts.length > 0" class="mt-2 text-xs text-neutral-700">
            Filter Aktif: {{ summaryParts.join(' • ') }}
        </p>
    </div>
</template>

