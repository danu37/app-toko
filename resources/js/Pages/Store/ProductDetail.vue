<script setup>
import { Head, Link } from '@inertiajs/vue3';
import StoreLayout from '@/Layouts/StoreLayout.vue';
import BaseBadge from '@/Components/UI/BaseBadge.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import StoreMediaBox from '@/Pages/Store/Components/StoreMediaBox.vue';
import { useCurrency } from '@/Composables/useCurrency';

defineProps({
    product: {
        type: Object,
        required: true,
    },
    relatedProducts: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
    branches: {
        type: Array,
        default: () => [],
    },
});

const { formatCurrency } = useCurrency();
</script>

<template>
    <Head :title="product.nama_produk" />

    <StoreLayout :branches="branches" :categories="categories">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <nav class="animate-enter-soft mb-6 flex items-center gap-2 text-xs text-neutral-500">
                <Link :href="route('home')" class="hover:text-neutral-900">Home</Link>
                <span>/</span>
                <Link :href="route('store.shop')" class="hover:text-neutral-900">Shop</Link>
                <span>/</span>
                <span class="truncate font-medium text-neutral-900">{{ product.nama_produk }}</span>
            </nav>

            <div class="grid grid-cols-1 gap-10 lg:grid-cols-12">
                <div class="lg:col-span-6">
                    <div
                        class="animate-enter-hero overflow-hidden rounded-2xl border border-neutral-200 bg-white"
                        style="--enter-delay: 50ms"
                    >
                        <StoreMediaBox
                            :src="product.gambar_url"
                            :alt="product.nama_produk"
                            :label="product.kategori?.nama_kategori ?? 'Produk'"
                            aspect-class="aspect-square"
                            eager
                        />
                    </div>
                </div>

                <div
                    class="enter-stagger flex flex-col justify-center space-y-5 lg:col-span-6"
                    style="--stagger-base: 85ms"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        <BaseBadge variant="neutral">
                            {{ product.kategori?.nama_kategori ?? 'Umum' }}
                        </BaseBadge>
                        <BaseBadge v-if="product.is_featured" variant="dark">
                            Unggulan
                        </BaseBadge>
                        <BaseBadge :variant="product.stok > 0 ? 'success' : 'danger'">
                            {{ product.stok > 0 ? `Tersedia (${product.stok} unit)` : 'Stok Habis' }}
                        </BaseBadge>
                    </div>

                    <div>
                        <p v-if="product.kode_produk" class="text-xs font-medium text-neutral-500">
                            SKU: {{ product.kode_produk }}
                        </p>
                        <h1 class="mt-1 text-2xl font-bold tracking-tight text-neutral-900 sm:text-3xl">
                            {{ product.nama_produk }}
                        </h1>
                    </div>

                    <p class="text-3xl font-bold text-neutral-900">
                        {{ formatCurrency(product.harga) }}
                    </p>

                    <div class="border-t border-neutral-200 pt-4">
                        <h2 class="text-xs font-semibold uppercase tracking-wider text-neutral-500">
                            Deskripsi Produk
                        </h2>
                        <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-neutral-600">
                            {{ product.deskripsi || 'Belum ada deskripsi rinci untuk produk ini.' }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3 pt-2">
                        <BaseButton :href="route('store.shop')" variant="secondary">
                            &larr; Kembali ke Katalog
                        </BaseButton>
                    </div>
                </div>
            </div>

            <section v-if="relatedProducts.length > 0" class="mt-14 border-t border-neutral-200 pt-10">
                <h2 class="animate-enter mb-6 text-lg font-semibold text-neutral-900" style="--enter-delay: 180ms">
                    Produk Terkait
                </h2>
                <div
                    class="enter-stagger-grid grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4"
                    style="--stagger-base: 220ms"
                >
                    <Link
                        v-for="item in relatedProducts"
                        :key="item.id_produk"
                        :href="route('store.product', item.slug)"
                        class="group flex flex-col overflow-hidden rounded-xl border border-neutral-200 bg-white transition hover:border-neutral-400"
                    >
                        <StoreMediaBox
                            :src="item.gambar_url"
                            :alt="item.nama_produk"
                            :label="item.kategori?.nama_kategori ?? 'Produk'"
                            aspect-class="aspect-square"
                        />
                        <div class="p-4">
                            <h3 class="line-clamp-1 text-sm font-semibold text-neutral-900">{{ item.nama_produk }}</h3>
                            <p class="mt-1 text-sm font-bold text-neutral-900">{{ formatCurrency(item.harga) }}</p>
                        </div>
                    </Link>
                </div>
            </section>
        </div>
    </StoreLayout>
</template>

