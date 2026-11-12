<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import StoreLayout from '@/Layouts/StoreLayout.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BaseLoading from '@/Components/UI/BaseLoading.vue';
import BasePagination from '@/Components/UI/BasePagination.vue';
import BaseSelect from '@/Components/UI/BaseSelect.vue';
import SearchInput from '@/Components/Forms/SearchInput.vue';
import EmptyState from '@/Components/Feedback/EmptyState.vue';
import StoreMediaBox from '@/Pages/Store/Components/StoreMediaBox.vue';
import { useCurrency } from '@/Composables/useCurrency';

const props = defineProps({
    products: {
        type: Object,
        required: true,
    },
    categories: {
        type: Array,
        default: () => [],
    },
    branches: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', kategori: '', sort: '' }),
    },
});

const { formatCurrency } = useCurrency();

const search = ref(props.filters.search ?? '');
const selectedKategori = ref(props.filters.kategori ?? '');
const selectedSort = ref(props.filters.sort ?? '');
const loading = ref(false);

const applyFilters = () => {
    const query = {};
    if (search.value) query.search = search.value;
    if (selectedKategori.value) query.kategori = selectedKategori.value;
    if (selectedSort.value) query.sort = selectedSort.value;

    loading.value = true;
    router.get(route('store.shop'), query, {
        preserveState: true,
        replace: true,
        onFinish: () => {
            loading.value = false;
        },
    });
};

const selectCategory = (slug) => {
    selectedKategori.value = slug;
    applyFilters();
};

const sortOptions = [
    { value: 'termurah', label: 'Harga Terendah' },
    { value: 'termahal', label: 'Harga Tertinggi' },
];
</script>

<template>
    <Head title="Shop - Katalog Produk" />

    <StoreLayout :branches="branches" :categories="categories">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="animate-enter mb-8 flex flex-col justify-between gap-4 border-b border-neutral-200 pb-6 md:flex-row md:items-end">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-neutral-900">Katalog Produk</h1>
                    <p class="mt-1 text-sm text-neutral-500">
                        Telusuri produk aktif yang tersedia di toko kami.
                    </p>
                </div>

                <form class="flex flex-wrap items-center gap-3" @submit.prevent="applyFilters">
                    <div class="w-full sm:w-64">
                        <SearchInput v-model="search" placeholder="Cari produk..." @search="applyFilters" />
                    </div>
                    <div class="w-full sm:w-48">
                        <BaseSelect
                            v-model="selectedSort"
                            :options="sortOptions"
                            placeholder="Urutkan: Terbaru"
                            aria-label="Urutkan produk"
                            @change="applyFilters"
                        />
                    </div>
                    <BaseButton type="submit" variant="secondary">Terapkan</BaseButton>
                </form>
            </div>

            <!-- Category Chips -->
            <div
                class="animate-enter-soft mb-6 flex flex-wrap items-center gap-2"
                style="--enter-delay: 55ms"
            >
                <button
                    type="button"
                    class="rounded-full border px-4 py-1.5 text-xs font-medium transition"
                    :class="
                        !selectedKategori
                            ? 'border-neutral-900 bg-neutral-900 text-white'
                            : 'border-neutral-200 bg-white text-neutral-700 hover:bg-neutral-50'
                    "
                    @click="selectCategory('')"
                >
                    Semua Kategori
                </button>
                <button
                    v-for="cat in categories"
                    :key="cat.id_kategori"
                    type="button"
                    class="rounded-full border px-4 py-1.5 text-xs font-medium transition"
                    :class="
                        selectedKategori === cat.slug
                            ? 'border-neutral-900 bg-neutral-900 text-white'
                            : 'border-neutral-200 bg-white text-neutral-700 hover:bg-neutral-50'
                    "
                    @click="selectCategory(cat.slug)"
                >
                    {{ cat.nama_kategori }}
                </button>
            </div>

            <BaseLoading v-if="loading" variant="overlay" text="Memuat katalog produk..." />

            <EmptyState
                v-else-if="(products.data ?? []).length === 0"
                title="Produk tidak ditemukan"
                description="Coba ubah kata kunci pencarian atau pilih kategori lain."
            >
                <template #action>
                    <BaseButton variant="secondary" @click="search = ''; selectedKategori = ''; selectedSort = ''; applyFilters()">
                        Reset Filter
                    </BaseButton>
                </template>
            </EmptyState>

            <div v-else class="space-y-6">
                <div
                    class="enter-stagger-grid grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4"
                    style="--stagger-base: 95ms"
                >
                    <Link
                        v-for="product in products.data"
                        :key="product.id_produk"
                        :href="route('store.product', product.slug)"
                        class="group flex flex-col overflow-hidden rounded-xl border border-neutral-200 bg-white transition hover:border-neutral-400 hover:shadow-sm"
                    >
                        <StoreMediaBox
                            :src="product.gambar_url"
                            :alt="product.nama_produk"
                            :label="product.kategori?.nama_kategori ?? 'Produk'"
                            aspect-class="aspect-[4/5]"
                        />
                        <div class="flex flex-1 flex-col justify-between p-4">
                            <div>
                                <p class="text-[11px] font-medium uppercase tracking-wider text-neutral-400">
                                    {{ product.kategori?.nama_kategori ?? 'Umum' }}
                                </p>
                                <h2 class="mt-1 line-clamp-2 text-sm font-semibold text-neutral-900">
                                    {{ product.nama_produk }}
                                </h2>
                            </div>
                            <div class="mt-4 flex items-center justify-between">
                                <span class="text-base font-bold text-neutral-900">
                                    {{ formatCurrency(product.harga) }}
                                </span>
                                <span class="text-xs text-neutral-500">Stok: {{ product.stok }}</span>
                            </div>
                        </div>
                    </Link>
                </div>

                <div
                    class="animate-enter-soft overflow-hidden rounded-xl border border-neutral-200"
                    style="--enter-delay: 220ms"
                >
                    <BasePagination :meta="products.meta" />
                </div>
            </div>
        </div>
    </StoreLayout>
</template>

