<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CashierLayout from '@/Layouts/CashierLayout.vue';
import BaseBadge from '@/Components/UI/BaseBadge.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import ConfirmDialog from '@/Components/Feedback/ConfirmDialog.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { useCurrency } from '@/Composables/useCurrency';
import { useDate } from '@/Composables/useDate';

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
    salesStats: {
        type: Object,
        default: null,
    },
});

const { can, isAdmin } = usePermissions();
const { formatCurrency } = useCurrency();
const { formatDate } = useDate();

const confirmDeleteOpen = ref(false);
const deleting = ref(false);

const executeDelete = () => {
    deleting.value = true;
    router.delete(route('produk.destroy', props.product.id_produk), {
        onFinish: () => {
            deleting.value = false;
            confirmDeleteOpen.value = false;
        },
    });
};
</script>

<template>
    <Head :title="product.nama_produk" />

    <component :is="isAdmin ? AdminLayout : CashierLayout" :title="`Detail Produk: ${product.nama_produk}`">
        <div class="mx-auto max-w-4xl space-y-6">
            <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                <div class="mb-6 flex flex-wrap items-center justify-between gap-3 border-b border-neutral-100 pb-4">
                    <BaseButton :href="route('produk.index')" variant="secondary" size="sm">
                        &larr; Kembali ke Daftar
                    </BaseButton>
                    <div class="flex flex-wrap items-center gap-2">
                        <BaseButton v-if="can.editProducts" :href="route('produk.edit', product.id_produk)" size="sm">
                            Edit Produk
                        </BaseButton>
                        <BaseButton
                            v-if="can.deleteProducts"
                            variant="danger"
                            size="sm"
                            @click="confirmDeleteOpen = true"
                        >
                            Hapus Produk
                        </BaseButton>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-12">
                    <div class="sm:col-span-5">
                        <div class="aspect-square overflow-hidden rounded-xl border border-neutral-200 bg-neutral-100">
                            <img
                                v-if="product.gambar_url"
                                :src="product.gambar_url"
                                :alt="product.nama_produk"
                                class="h-full w-full object-cover"
                            />
                            <div v-else class="flex h-full w-full items-center justify-center text-neutral-300">
                                <svg class="h-14 w-14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 sm:col-span-7">
                        <div class="flex flex-wrap gap-2">
                            <BaseBadge variant="neutral">{{ product.kategori?.nama_kategori ?? 'Umum' }}</BaseBadge>
                            <BaseBadge :variant="product.is_active ? 'success' : 'neutral'">
                                {{ product.is_active ? 'Aktif' : 'Nonaktif' }}
                            </BaseBadge>
                            <BaseBadge v-if="product.is_featured" variant="dark">Unggulan</BaseBadge>
                            <BaseBadge
                                :variant="product.stok <= 0 ? 'danger' : product.stok <= 5 ? 'warning' : 'info'"
                            >
                                {{ product.stok <= 0 ? 'Stok Habis' : product.stok <= 5 ? 'Stok Menipis' : 'Stok Aman' }}
                            </BaseBadge>
                        </div>

                        <div>
                            <p class="text-xs text-neutral-500">Kode / SKU: {{ product.kode_produk || '-' }} &bull; Slug: {{ product.slug || '-' }}</p>
                            <h2 class="mt-0.5 text-xl font-semibold text-neutral-900">{{ product.nama_produk }}</h2>
                        </div>

                        <div class="grid grid-cols-2 gap-3 rounded-xl bg-neutral-50 p-4">
                            <div>
                                <p class="text-xs text-neutral-500">Harga Jual</p>
                                <p class="text-lg font-bold text-neutral-900">{{ formatCurrency(product.harga) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-neutral-500">Stok Tersedia</p>
                                <p class="text-lg font-bold text-neutral-900">{{ product.stok }} unit</p>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Deskripsi</h3>
                            <p class="mt-1 whitespace-pre-line text-sm text-neutral-600">
                                {{ product.deskripsi || 'Tidak ada deskripsi tambahan.' }}
                            </p>
                        </div>

                        <div v-if="product.created_at" class="flex flex-wrap gap-4 border-t border-neutral-100 pt-3 text-xs text-neutral-400">
                            <span>Dibuat: {{ formatDate(product.created_at) }}</span>
                            <span v-if="product.updated_at">Diperbarui: {{ formatDate(product.updated_at) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Admin Sales Analytics for this product -->
            <div v-if="isAdmin && salesStats" class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-semibold text-neutral-900">Ringkasan Penjualan Produk (Admin)</h3>
                <p class="mt-0.5 text-xs text-neutral-500">Dihitung dari detail transaksi non-batal yang tercatat di database.</p>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="rounded-lg bg-neutral-50 p-4">
                        <p class="text-xs text-neutral-500">Total Unit Terjual</p>
                        <p class="mt-1 text-xl font-bold text-neutral-900">{{ salesStats.total_terjual }} unit</p>
                    </div>
                    <div class="rounded-lg bg-neutral-50 p-4">
                        <p class="text-xs text-neutral-500">Akumulasi Pendapatan</p>
                        <p class="mt-1 text-xl font-bold text-emerald-700">{{ formatCurrency(salesStats.total_pendapatan) }}</p>
                    </div>
                    <div class="rounded-lg bg-neutral-50 p-4">
                        <p class="text-xs text-neutral-500">Muncul di Transaksi</p>
                        <p class="mt-1 text-xl font-bold text-neutral-900">{{ salesStats.total_transaksi }}x</p>
                    </div>
                </div>
            </div>

            <ConfirmDialog
                v-if="can.deleteProducts"
                :show="confirmDeleteOpen"
                title="Hapus Produk"
                :message="`Yakin ingin menghapus produk ${product.nama_produk}?`"
                :loading="deleting"
                @close="confirmDeleteOpen = false"
                @confirm="executeDelete"
            />
        </div>
    </component>
</template>
