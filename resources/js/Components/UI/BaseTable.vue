<script setup>
import BaseLoading from '@/Components/UI/BaseLoading.vue';
import EmptyState from '@/Components/Feedback/EmptyState.vue';

defineProps({
    headers: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
    empty: {
        type: Boolean,
        default: false,
    },
    emptyTitle: {
        type: String,
        default: 'Belum ada data',
    },
    emptyDescription: {
        type: String,
        default: 'Data yang Anda cari belum tersedia.',
    },
});
</script>

<template>
    <div v-if="loading" class="rounded-xl border border-neutral-200 bg-white p-5">
        <BaseLoading variant="skeleton" :rows="5" />
    </div>

    <slot v-else-if="empty" name="empty">
        <EmptyState :title="emptyTitle" :description="emptyDescription" />
    </slot>

    <div v-else class="overflow-hidden rounded-xl border border-neutral-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-neutral-200 text-left text-sm">
                <thead class="bg-neutral-50">
                    <tr>
                        <slot name="head">
                            <th
                                v-for="header in headers"
                                :key="header.key || header.label"
                                scope="col"
                                class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-neutral-600"
                                :class="
                                    header.align === 'right'
                                        ? 'text-right'
                                        : header.align === 'center'
                                          ? 'text-center'
                                          : 'text-left'
                                "
                            >
                                {{ header.label }}
                            </th>
                        </slot>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 bg-white">
                    <slot />
                </tbody>
            </table>
        </div>
        <slot name="footer" />
    </div>
</template>

