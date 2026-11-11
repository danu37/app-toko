<script setup>
import { computed, ref } from 'vue';
import BaseAlert from '@/Components/UI/BaseAlert.vue';
import BaseBadge from '@/Components/UI/BaseBadge.vue';
import EmptyState from '@/Components/Feedback/EmptyState.vue';
import SearchInput from '@/Components/Forms/SearchInput.vue';
import { useCurrency } from '@/Composables/useCurrency';

const props = defineProps({
    products: {
        type: Array,
        default: () => [],
    },
    cartItems: {
        type: Array,
        default: () => [],
    },
    error: {
        type: String,
        default: '',
    },
    notice: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['add-product', 'update-qty', 'remove-item', 'clear-cart']);
const { formatCurrency } = useCurrency();

const productSearch = ref('');
const selectedCategory = ref('');

const categories = computed(() => {
    const map = new Map();
    props.products.forEach((p) => {
        if (p.kategori?.id_kategori && !map.has(p.kategori.id_kategori)) {
            map.set(p.kategori.id_kategori, p.kategori.nama_kategori);
        }
    });
    return Array.from(map.entries()).map(([id, nama]) => ({ id, nama }));
});

const cartQtyMap = computed(() => {
    const map = new Map();
    props.cartItems.forEach((item) => map.set(item.id_produk, item.jumlah));
    return map;
});

const subtotal = computed(() =>
    props.cartItems.reduce((sum, item) => sum + Number(item.harga) * Number(item.jumlah), 0),
);

const filteredProducts = computed(() => {
    const q = productSearch.value.trim().toLowerCase();
    return props.products.filter((p) => {
        if (selectedCategory.value && p.kategori?.id_kategori !== selectedCategory.value) {
            return false;
        }
        if (!q) return true;
        return (
            p.nama_produk.toLowerCase().includes(q) ||
            (p.kode_produk && p.kode_produk.toLowerCase().includes(q)) ||
            (p.kategori?.nama_kategori && p.kategori.nama_kategori.toLowerCase().includes(q))
        );
    });
});

const addFirstMatchOnEnter = () => {
    const q = productSearch.value.trim().toLowerCase();
    if (!q) return;

    const exactSku = filteredProducts.value.find(
        (p) => p.kode_produk && p.kode_produk.toLowerCase() === q,
    );
    const target = exactSku || (filteredProducts.value.length === 1 ? filteredProducts.value[0] : null);
    if (target && target.stok > 0) {
        emit('add-product', target);
        productSearch.value = '';
    }
};
</script>

<template>
    <div class="space-y-5">
        <!-- Cari & Tambah Produk -->
        <div class="space-y-4 rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-neutral-900">Cari &amp; Pilih Produk</h2>
                    <p class="text-xs text-neutral-500">Klik produk atau tekan Enter pada kode SKU untuk masuk keranjang.</p>
                </div>
                <div class="w-full shrink-0 sm:w-56" @keydown.enter.prevent="addFirstMatchOnEnter">
                    <SearchInput v-model="productSearch" placeholder="Cari produk / SKU..." />
                </div>
            </div>

            <div v-if="categories.length > 0" class="flex flex-wrap items-center gap-1.5">
                <button
                    type="button"
                    class="rounded-lg border px-2.5 py-1 text-xs font-medium transition"
                    :class="
                        selectedCategory === ''
                            ? 'border-neutral-900 bg-neutral-900 text-white'
                            : 'border-neutral-200 bg-white text-neutral-600 hover:bg-neutral-50'
                    "
                    @click="selectedCategory = ''"
                >
                    Semua ({{ products.length }})
                </button>
                <button
                    v-for="cat in categories"
                    :key="cat.id"
                    type="button"
                    class="rounded-lg border px-2.5 py-1 text-xs font-medium transition"
                    :class="
                        selectedCategory === cat.id
                            ? 'border-neutral-900 bg-neutral-900 text-white'
                            : 'border-neutral-200 bg-white text-neutral-600 hover:bg-neutral-50'
                    "
                    @click="selectedCategory = cat.id"
                >
                    {{ cat.nama }}
                </button>
            </div>

            <BaseAlert v-if="notice" variant="warning" :message="notice" />

            <EmptyState
                v-if="filteredProducts.length === 0"
                title="Produk tidak ditemukan"
                description="Coba kata kunci nama produk, SKU, atau kategori lainnya."
            />

            <div v-else class="grid max-h-80 grid-cols-1 gap-2.5 overflow-y-auto pr-1 sm:grid-cols-2">
                <button
                    v-for="product in filteredProducts"
                    :key="product.id_produk"
                    type="button"
                    :disabled="product.stok <= 0"
                    class="flex items-center justify-between rounded-xl border p-3 text-left transition"
                    :class="
                        product.stok <= 0
                            ? 'cursor-not-allowed border-neutral-200 bg-neutral-50 opacity-60'
                            : cartQtyMap.get(product.id_produk)
                              ? 'border-neutral-900 bg-neutral-50/80 ring-1 ring-neutral-900/10 hover:bg-neutral-100'
                              : 'border-neutral-200 bg-white hover:border-neutral-900 hover:bg-neutral-50'
                    "
                    @click="emit('add-product', product)"
                >
                    <div class="min-w-0 pr-3">
                        <p class="truncate text-sm font-semibold text-neutral-900">{{ product.nama_produk }}</p>
                        <p
                            class="mt-0.5 text-xs"
                            :class="product.stok <= 0 ? 'font-medium text-red-600' : product.stok <= 5 ? 'font-medium text-amber-700' : 'text-neutral-500'"
                        >
                            {{ product.kategori?.nama_kategori ?? 'Umum' }} &middot;
                            {{ product.stok <= 0 ? 'Stok Habis' : `Stok: ${product.stok}` }}
                        </p>
                    </div>
                    <div class="shrink-0 text-right">
                        <span class="block text-sm font-bold text-neutral-900">
                            {{ formatCurrency(product.harga) }}
                        </span>
                        <BaseBadge v-if="product.stok <= 0" variant="danger" class="mt-1">
                            Habis
                        </BaseBadge>
                        <BaseBadge v-else-if="cartQtyMap.get(product.id_produk)" variant="dark" class="mt-1">
                            {{ cartQtyMap.get(product.id_produk) }}x
                        </BaseBadge>
                    </div>
                </button>
            </div>
        </div>

        <!-- Keranjang Belanja -->
        <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-2 border-b border-neutral-100 pb-3">
                <div>
                    <h2 class="text-base font-semibold text-neutral-900">Keranjang Belanja</h2>
                    <p class="text-xs text-neutral-500">Atur jumlah barang dan periksa subtotal sebelum diproses.</p>
                </div>
                <button
                    v-if="cartItems.length > 0"
                    type="button"
                    class="rounded-lg px-2.5 py-1 text-xs font-medium text-red-600 transition hover:bg-red-50"
                    @click="emit('clear-cart')"
                >
                    Kosongkan
                </button>
            </div>

            <BaseAlert v-if="error" variant="danger" :message="error" class="mt-3" />

            <EmptyState
                v-if="cartItems.length === 0"
                title="Keranjang masih kosong"
                description="Pilih produk di atas untuk mulai mencatat transaksi."
                class="mt-4"
            />

            <div v-else class="mt-3 divide-y divide-neutral-100">
                <div
                    v-for="item in cartItems"
                    :key="item.id_produk"
                    class="flex flex-col justify-between gap-3 py-3 sm:flex-row sm:items-center"
                >
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-neutral-900">{{ item.nama_produk }}</p>
                        <p class="text-xs text-neutral-500">
                            {{ formatCurrency(item.harga) }} / unit &middot; Maks {{ item.stok }}
                        </p>
                    </div>

                    <div class="flex items-center justify-between gap-3 sm:justify-end">
                        <div class="inline-flex items-center rounded-lg border border-neutral-300 bg-white">
                            <button
                                type="button"
                                aria-label="Kurangi jumlah"
                                class="px-2.5 py-1 text-sm font-medium text-neutral-700 hover:bg-neutral-100"
                                @click="emit('update-qty', item.id_produk, item.jumlah - 1)"
                            >
                                -
                            </button>
                            <input
                                type="number"
                                min="1"
                                :max="item.stok"
                                :aria-label="`Jumlah ${item.nama_produk}`"
                                :value="item.jumlah"
                                class="w-14 border-0 py-1 text-center text-sm font-semibold text-neutral-900 focus:ring-0"
                                @change="emit('update-qty', item.id_produk, Number($event.target.value))"
                            />
                            <button
                                type="button"
                                aria-label="Tambah jumlah"
                                :disabled="item.jumlah >= item.stok"
                                class="px-2.5 py-1 text-sm font-medium text-neutral-700 hover:bg-neutral-100 disabled:cursor-not-allowed disabled:opacity-40"
                                @click="emit('update-qty', item.id_produk, item.jumlah + 1)"
                            >
                                +
                            </button>
                        </div>

                        <div class="w-28 text-right">
                            <span class="block text-[10px] uppercase tracking-wider text-neutral-400">Subtotal</span>
                            <span class="text-sm font-semibold text-neutral-900">
                                {{ formatCurrency(item.harga * item.jumlah) }}
                            </span>
                        </div>

                        <button
                            type="button"
                            class="rounded-md px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50"
                            @click="emit('remove-item', item.id_produk)"
                        >
                            Hapus
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-3 text-sm">
                    <span class="font-medium text-neutral-600">Subtotal Barang</span>
                    <span class="text-base font-bold text-neutral-900">{{ formatCurrency(subtotal) }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

