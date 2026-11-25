<script setup>
import { computed, onMounted, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import BaseAlert from '@/Components/UI/BaseAlert.vue';
import BaseBadge from '@/Components/UI/BaseBadge.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BaseInput from '@/Components/UI/BaseInput.vue';
import BaseSelect from '@/Components/UI/BaseSelect.vue';
import SearchInput from '@/Components/Forms/SearchInput.vue';
import { useCurrency } from '@/Composables/useCurrency';
import { usePermissions } from '@/Composables/usePermissions';

const props = defineProps({
    products: {
        type: Array,
        default: () => [],
    },
    branches: {
        type: Array,
        default: () => [],
    },
    preselectedProductId: {
        type: Number,
        default: null,
    },
    qrisInfo: {
        type: Object,
        default: () => ({}),
    },
});

const { user } = usePermissions();
const { formatCurrency } = useCurrency();

const search = ref('');
const cartItems = ref([]);
const previewUrl = ref('');

const form = useForm({
    id_cabang: '',
    metode_pembayaran: 'qris',
    bukti_pembayaran: null,
    catatan: '',
    items: [],
});

const branchOptions = computed(() =>
    props.branches.map((b) => ({
        value: b.id_cabang,
        label: b.kota ? `${b.nama_cabang} (${b.kota})` : b.nama_cabang,
    })),
);

const paymentOptions = [
    { value: 'qris', label: 'QRIS (Scan Kode Toko)' },
    { value: 'transfer', label: 'Transfer Bank' },
];

const filteredProducts = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.products.slice(0, 12);
    return props.products
        .filter(
            (p) =>
                p.nama_produk.toLowerCase().includes(q) ||
                (p.kode_produk && p.kode_produk.toLowerCase().includes(q)),
        )
        .slice(0, 12);
});

const addToCart = (product) => {
    const stok = Number(product.stok ?? 0);
    if (stok <= 0) return;

    const existing = cartItems.value.find((item) => item.id_produk === product.id_produk);
    if (existing) {
        if (existing.jumlah < stok) {
            existing.jumlah += 1;
        }
        return;
    }

    cartItems.value.push({
        id_produk: product.id_produk,
        nama_produk: product.nama_produk,
        harga: Number(product.harga),
        stok,
        jumlah: 1,
    });
};

const updateQty = (productId, nextQty) => {
    if (!Number.isFinite(nextQty) || nextQty < 1) {
        cartItems.value = cartItems.value.filter((i) => i.id_produk !== productId);
        return;
    }
    const target = cartItems.value.find((i) => i.id_produk === productId);
    if (target) {
        target.jumlah = Math.min(Math.floor(nextQty), target.stok);
    }
};

const handleProofChange = (event) => {
    const file = event.target.files?.[0] ?? null;
    form.bukti_pembayaran = file;
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = '';
    }
    if (file) {
        previewUrl.value = URL.createObjectURL(file);
    }
};

const subtotalAmount = computed(() =>
    cartItems.value.reduce((sum, item) => sum + Number(item.harga) * Number(item.jumlah), 0),
);
const ppnAmount = computed(() => Math.round(subtotalAmount.value * 0.11 * 100) / 100);
const totalAmount = computed(() => Math.round((subtotalAmount.value + ppnAmount.value) * 100) / 100);

const canSubmit = computed(
    () => cartItems.value.length > 0 && Boolean(form.bukti_pembayaran) && !form.processing,
);

const submitOrder = () => {
    form.items = cartItems.value.map((i) => ({
        id_produk: i.id_produk,
        jumlah: i.jumlah,
    }));

    form.post(route('pelanggan.order.store'), {
        forceFormData: true,
    });
};

onMounted(() => {
    if (props.preselectedProductId) {
        const found = props.products.find((p) => Number(p.id_produk) === Number(props.preselectedProductId));
        if (found) addToCart(found);
    }
});

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <Head title="Beli Barang &amp; Upload Bukti Pembayaran" />

    <div class="flex min-h-screen flex-col bg-neutral-50 text-neutral-900">
        <header class="sticky top-0 z-40 border-b border-neutral-200 bg-white/95 backdrop-blur-sm">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-6">
                    <Link :href="route('home')" class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-neutral-900 text-xs font-bold tracking-wider text-white">
                            TK
                        </span>
                        <span class="text-base font-semibold tracking-tight text-neutral-900">TokoKita</span>
                    </Link>
                    <nav class="hidden items-center gap-5 text-sm font-medium text-neutral-600 md:flex">
                        <Link :href="route('pelanggan.dashboard')" class="transition hover:text-neutral-900">Dashboard</Link>
                        <Link :href="route('store.shop')" class="transition hover:text-neutral-900">Katalog</Link>
                        <span class="font-semibold text-neutral-900">Beli Barang</span>
                    </nav>
                </div>
                <div class="flex items-center gap-3">
                    <span class="hidden text-xs font-medium text-neutral-700 sm:inline">{{ user?.name }}</span>
                    <BaseButton variant="secondary" size="sm" @click="logout">Keluar</BaseButton>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-7xl flex-1 space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-neutral-900">
                        Beli Barang &amp; Upload Bukti Pembayaran
                    </h1>
                    <p class="text-xs text-neutral-500">
                        Pilih produk, lakukan pembayaran QRIS/Transfer, lalu unggah bukti transfer untuk diverifikasi Kasir atau Admin.
                    </p>
                </div>
                <BaseButton :href="route('pelanggan.dashboard')" variant="secondary" size="sm">
                    &larr; Kembali ke Dashboard
                </BaseButton>
            </div>

            <BaseAlert
                v-if="Object.keys(form.errors).length > 0"
                variant="danger"
                :message="Object.values(form.errors)[0]"
            />

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <!-- Kiri: Pilih Produk & Keranjang -->
                <div class="space-y-5 lg:col-span-7">
                    <div class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
                        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <h2 class="text-sm font-semibold text-neutral-900">Pilih Barang</h2>
                            <div class="w-full sm:w-64">
                                <SearchInput v-model="search" placeholder="Cari nama atau SKU produk..." />
                            </div>
                        </div>

                        <div v-if="filteredProducts.length === 0" class="py-8 text-center text-xs text-neutral-500">
                            Produk tidak ditemukan.
                        </div>

                        <div v-else class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div
                                v-for="product in filteredProducts"
                                :key="product.id_produk"
                                class="flex items-center justify-between gap-3 rounded-xl border border-neutral-200 p-3 transition hover:border-neutral-400"
                            >
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-neutral-900">{{ product.nama_produk }}</p>
                                    <p class="text-xs font-medium text-neutral-700">{{ formatCurrency(product.harga) }}</p>
                                    <p class="text-[11px] text-neutral-400">Stok: {{ product.stok }}</p>
                                </div>
                                <BaseButton type="button" size="sm" variant="secondary" @click="addToCart(product)">
                                    + Pilih
                                </BaseButton>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
                        <h2 class="mb-3 text-sm font-semibold text-neutral-900">Keranjang Belanja</h2>
                        <div v-if="cartItems.length === 0" class="py-6 text-center text-xs text-neutral-500">
                            Belum ada barang dipilih. Klik <strong>+ Pilih</strong> pada daftar barang di atas.
                        </div>
                        <div v-else class="divide-y divide-neutral-100">
                            <div
                                v-for="item in cartItems"
                                :key="item.id_produk"
                                class="flex items-center justify-between gap-3 py-3"
                            >
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-neutral-900">{{ item.nama_produk }}</p>
                                    <p class="text-xs text-neutral-500">{{ formatCurrency(item.harga) }} / unit</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="h-7 w-7 rounded border border-neutral-300 text-xs font-bold hover:bg-neutral-100"
                                        @click="updateQty(item.id_produk, item.jumlah - 1)"
                                    >
                                        -
                                    </button>
                                    <span class="w-8 text-center text-sm font-semibold tabular-nums">{{ item.jumlah }}</span>
                                    <button
                                        type="button"
                                        class="h-7 w-7 rounded border border-neutral-300 text-xs font-bold hover:bg-neutral-100"
                                        @click="updateQty(item.id_produk, item.jumlah + 1)"
                                    >
                                        +
                                    </button>
                                    <span class="ml-2 w-24 text-right text-sm font-semibold tabular-nums text-neutral-900">
                                        {{ formatCurrency(item.harga * item.jumlah) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kanan: Ringkasan, QRIS & Upload Bukti -->
                <div class="space-y-5 lg:col-span-5">
                    <form class="space-y-4 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm" @submit.prevent="submitOrder">
                        <h2 class="border-b border-neutral-100 pb-3 text-sm font-semibold text-neutral-900">
                            Pembayaran &amp; Bukti Transfer
                        </h2>

                        <BaseSelect
                            id="order_cabang"
                            v-model="form.id_cabang"
                            label="Cabang Pengambilan (Opsional)"
                            :options="branchOptions"
                            placeholder="Pilih Cabang"
                            :error="form.errors.id_cabang"
                        />

                        <BaseSelect
                            id="order_metode"
                            v-model="form.metode_pembayaran"
                            label="Metode Pembayaran"
                            :options="paymentOptions"
                            placeholder=""
                            :error="form.errors.metode_pembayaran"
                        />

                        <div v-if="form.metode_pembayaran === 'qris'" class="rounded-xl border border-neutral-200 bg-stone-50 p-3.5 text-center">
                            <p class="text-xs font-semibold text-neutral-900">
                                {{ qrisInfo?.merchant_name || 'Danu Anggo, Pakaian' }}
                            </p>
                            <p class="text-[11px] text-neutral-500">NMID: {{ qrisInfo?.nmid || 'ID1026645584610' }}</p>
                            <img
                                v-if="qrisInfo?.code_url"
                                :src="qrisInfo.code_url"
                                alt="Kode QRIS TokoKita"
                                class="mx-auto mt-2.5 h-40 w-40 rounded-lg border border-neutral-200 bg-white object-contain p-1.5"
                            />
                        </div>

                        <div class="rounded-xl bg-neutral-900 p-4 text-white">
                            <div class="flex justify-between text-xs text-neutral-300">
                                <span>Subtotal</span>
                                <span>{{ formatCurrency(subtotalAmount) }}</span>
                            </div>
                            <div class="mt-1 flex justify-between text-xs text-neutral-300">
                                <span>PPN (11%)</span>
                                <span>{{ formatCurrency(ppnAmount) }}</span>
                            </div>
                            <div class="mt-2 flex items-baseline justify-between border-t border-neutral-700 pt-2">
                                <span class="text-sm font-medium text-neutral-200">Total Bayar</span>
                                <span class="text-xl font-bold tabular-nums">{{ formatCurrency(totalAmount) }}</span>
                            </div>
                        </div>

                        <div>
                            <label for="bukti_pembayaran" class="mb-1.5 block text-sm font-medium text-neutral-700">
                                Upload Bukti Pembayaran <span class="text-red-600">*</span>
                            </label>
                            <input
                                id="bukti_pembayaran"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                required
                                class="block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-xs text-neutral-700 file:mr-3 file:rounded-md file:border-0 file:bg-neutral-900 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white hover:file:bg-neutral-800"
                                @change="handleProofChange"
                            />
                            <p v-if="form.errors.bukti_pembayaran" class="mt-1 text-xs font-medium text-red-600">
                                {{ form.errors.bukti_pembayaran }}
                            </p>
                            <div v-if="previewUrl" class="mt-2 overflow-hidden rounded-lg border border-neutral-200">
                                <img :src="previewUrl" alt="Pratinjau bukti pembayaran" class="max-h-48 w-full object-contain bg-neutral-50" />
                            </div>
                        </div>

                        <BaseInput
                            id="order_catatan"
                            v-model="form.catatan"
                            label="Catatan Pesanan (Opsional)"
                            placeholder="Contoh: Transfer a.n. Budi"
                            :error="form.errors.catatan"
                        />

                        <BaseButton
                            type="submit"
                            variant="emerald"
                            size="lg"
                            class="w-full"
                            :disabled="!canSubmit"
                            :loading="form.processing"
                        >
                            {{ form.processing ? 'Mengirim Pesanan...' : 'Kirim Pesanan & Bukti Pembayaran' }}
                        </BaseButton>
                    </form>
                </div>
            </div>
        </main>
    </div>
</template>
