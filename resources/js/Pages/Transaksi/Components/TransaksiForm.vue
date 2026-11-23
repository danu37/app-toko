<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';
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

const barcodeInputRef = ref(null);
const barcodeQuery = ref('');
const scannerFeedback = ref({
    type: '',
    message: '',
});
let feedbackTimer = null;

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
            (p.barcode && p.barcode.toLowerCase().includes(q)) ||
            (p.kategori?.nama_kategori && p.kategori.nama_kategori.toLowerCase().includes(q))
        );
    });
});

const setScannerFeedback = (type, message) => {
    if (feedbackTimer) clearTimeout(feedbackTimer);
    scannerFeedback.value = { type, message };
    feedbackTimer = setTimeout(() => {
        scannerFeedback.value = { type: '', message: '' };
    }, 4000);
};

const focusScanner = () => {
    barcodeInputRef.value?.focus();
};

const processBarcodeScan = () => {
    const raw = barcodeQuery.value.trim();
    if (!raw) return;

    // 1. Prioritaskan pencarian barcode tepat
    let product = props.products.find(
        (p) => p.barcode && p.barcode.toLowerCase() === raw.toLowerCase(),
    );

    // 2. Fallback pencarian SKU / kode_produk tepat
    if (!product) {
        product = props.products.find(
            (p) => p.kode_produk && p.kode_produk.toLowerCase() === raw.toLowerCase(),
        );
    }

    // 3. Fallback pencarian nama produk tepat
    if (!product) {
        product = props.products.find(
            (p) => p.nama_produk && p.nama_produk.toLowerCase() === raw.toLowerCase(),
        );
    }

    if (!product) {
        setScannerFeedback('error', `Barcode "${raw}" tidak terdaftar pada produk aktif.`);
        barcodeQuery.value = '';
        nextTick(() => focusScanner());
        return;
    }

    // Validasi stok produk
    if (product.stok <= 0) {
        setScannerFeedback('warning', `Stok ${product.nama_produk} sedang habis (0 unit).`);
        barcodeQuery.value = '';
        nextTick(() => focusScanner());
        return;
    }

    const currentQtyInCart = cartQtyMap.value.get(product.id_produk) || 0;
    if (currentQtyInCart >= product.stok) {
        setScannerFeedback(
            'warning',
            `Stok maksimal untuk ${product.nama_produk} adalah ${product.stok} unit (sudah di keranjang).`,
        );
        barcodeQuery.value = '';
        nextTick(() => focusScanner());
        return;
    }

    // Tambah ke keranjang
    emit('add-product', product);
    const newQty = currentQtyInCart + 1;
    setScannerFeedback(
        'success',
        `✓ ${product.nama_produk} ditambahkan (${newQty}x) — ${formatCurrency(product.harga * newQty)}`,
    );

    barcodeQuery.value = '';
    nextTick(() => focusScanner());
};

const handleGlobalKeydown = (e) => {
    if (e.key === 'F2') {
        e.preventDefault();
        focusScanner();
    }
};

onMounted(() => {
    focusScanner();
    window.addEventListener('keydown', handleGlobalKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleGlobalKeydown);
    if (feedbackTimer) clearTimeout(feedbackTimer);
});

const addFirstMatchOnEnter = () => {
    const q = productSearch.value.trim().toLowerCase();
    if (!q) return;

    const exactSku = filteredProducts.value.find(
        (p) => (p.kode_produk && p.kode_produk.toLowerCase() === q) || (p.barcode && p.barcode.toLowerCase() === q),
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
        <!-- Barcode Scanner Card -->
        <div class="relative overflow-hidden rounded-xl border border-neutral-900 bg-neutral-900 p-4 text-white shadow-sm sm:p-5">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-neutral-800 text-white">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold tracking-wide text-white sm:text-base">Barcode Scanner Kasir</h2>
                        <p class="text-[11px] text-neutral-400">Mendukung mesin scanner USB / Bluetooth keyboard-wedge &amp; input manual.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-1 text-xs font-medium text-emerald-400 ring-1 ring-inset ring-emerald-500/20">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                        </span>
                        Scanner Siap
                    </span>
                    <button
                        type="button"
                        class="rounded-lg bg-neutral-800 px-2 py-1 text-[11px] font-medium text-neutral-300 transition hover:bg-neutral-700 hover:text-white"
                        title="Shortcut F2 untuk fokus scanner"
                        @click="focusScanner"
                    >
                        Fokus (F2)
                    </button>
                </div>
            </div>

            <!-- Input Barcode Row -->
            <div class="relative flex items-center gap-2">
                <div class="relative flex-1">
                    <input
                        ref="barcodeInputRef"
                        v-model="barcodeQuery"
                        type="text"
                        autocomplete="off"
                        placeholder="Scan barcode atau masukkan kode barang..."
                        class="block w-full rounded-lg border border-neutral-700 bg-neutral-800/90 py-2.5 pl-10 pr-4 font-mono text-sm text-white placeholder:text-neutral-400 transition focus:border-white focus:bg-neutral-800 focus:outline-none focus:ring-1 focus:ring-white"
                        @keydown.enter.prevent="processBarcodeScan"
                    />
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-neutral-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                        </svg>
                    </div>
                </div>

                <button
                    type="button"
                    class="shrink-0 rounded-lg bg-white px-4 py-2.5 text-xs font-semibold text-neutral-900 transition hover:bg-neutral-100 disabled:opacity-50"
                    :disabled="!barcodeQuery.trim()"
                    @click="processBarcodeScan"
                >
                    + Tambah
                </button>
            </div>

            <!-- Feedback Alert (Success / Error / Warning) -->
            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="transform opacity-0 -translate-y-1"
                enter-to-class="transform opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="transform opacity-100 translate-y-0"
                leave-to-class="transform opacity-0 -translate-y-1"
            >
                <div
                    v-if="scannerFeedback.message"
                    class="mt-3 flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium"
                    :class="{
                        'bg-emerald-950/80 text-emerald-200 border border-emerald-800': scannerFeedback.type === 'success',
                        'bg-red-950/80 text-red-200 border border-red-800': scannerFeedback.type === 'error',
                        'bg-amber-950/80 text-amber-200 border border-amber-800': scannerFeedback.type === 'warning',
                    }"
                >
                    <span v-if="scannerFeedback.type === 'success'">✓</span>
                    <span v-else-if="scannerFeedback.type === 'error'">✕</span>
                    <span v-else>⚠</span>
                    <span>{{ scannerFeedback.message }}</span>
                </div>
            </transition>
        </div>
        <!-- Cari & Tambah Produk -->
        <div class="space-y-4 rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-neutral-900">Cari &amp; Pilih Produk</h2>
                    <p class="text-xs text-neutral-500">Klik produk atau tekan Enter pada kode SKU untuk masuk keranjang.</p>
                </div>
                <div class="w-full shrink-0 sm:w-56" @keydown.enter.prevent="addFirstMatchOnEnter">
                    <SearchInput v-model="productSearch" placeholder="Cari produk, SKU, barcode..." />
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
                            <span v-if="product.barcode" class="font-mono text-neutral-600">{{ product.barcode }} &middot; </span>
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

