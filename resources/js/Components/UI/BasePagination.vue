<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    meta: {
        type: Object,
        default: null,
    },
    links: {
        type: Array,
        default: () => [],
    },
});

const pageLinks = computed(() => {
    if (Array.isArray(props.links) && props.links.length > 0) {
        return props.links;
    }
    return props.meta?.links ?? [];
});

const summaryText = computed(() => {
    if (!props.meta || !props.meta.total) {
        return '';
    }
    return `Menampilkan ${props.meta.from ?? 0}–${props.meta.to ?? 0} dari ${props.meta.total} data`;
});

const formatLabel = (label) =>
    String(label)
        .replace(/&laquo;\s*Previous/i, '← Sebelumnya')
        .replace(/Next\s*&raquo;/i, 'Berikutnya →')
        .replace(/&laquo;/g, '←')
        .replace(/&raquo;/g, '→');
</script>

<template>
    <div
        v-if="pageLinks.length > 3 || summaryText"
        class="flex flex-col items-center justify-between gap-3 border-t border-neutral-200 bg-white px-4 py-3 sm:flex-row"
    >
        <p class="text-xs text-neutral-500">
            {{ summaryText }}
        </p>

        <nav v-if="pageLinks.length > 3" class="flex flex-wrap items-center justify-center gap-1" aria-label="Navigasi halaman">
            <template v-for="(link, idx) in pageLinks" :key="idx">
                <span
                    v-if="!link.url"
                    class="rounded-lg px-3 py-1.5 text-xs font-medium text-neutral-400"
                >
                    {{ formatLabel(link.label) }}
                </span>
                <Link
                    v-else
                    :href="link.url"
                    preserve-scroll
                    class="rounded-lg border px-3 py-1.5 text-xs font-medium transition focus:outline-none focus:ring-2 focus:ring-neutral-900"
                    :class="
                        link.active
                            ? 'border-neutral-900 bg-neutral-900 text-white'
                            : 'border-neutral-200 bg-white text-neutral-700 hover:bg-neutral-50'
                    "
                >
                    {{ formatLabel(link.label) }}
                </Link>
            </template>
        </nav>
    </div>
</template>
