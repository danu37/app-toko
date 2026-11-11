<script setup>
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CashierLayout from '@/Layouts/CashierLayout.vue';
import TransaksiForm from '@/Pages/Transaksi/Components/TransaksiForm.vue';
import TransactionSummary from '@/Pages/Transaksi/Components/TransactionSummary.vue';
import { usePermissions } from '@/Composables/usePermissions';

const props = defineProps({
    products: {
        type: Array,
        default: () => [],
    },
    customers: {
        type: Array,
        default: () => [],
    },
    branches: {
        type: Array,
        default: () => [],
    },
    preselectedCustomerId: {
        type: Number,
        default: null,
    },
});

const { user, isAdmin } = usePermissions();

const cartItems = ref([]);
const stockNotice = ref('');

const form = useForm({
    id_pelanggan: props.preselectedCustomerId ?? '',
    id_cabang: user.value?.id_cabang ?? '',
    tanggal: new Date().toISOString().slice(0, 10),
    metode_pembayaran: 'tunai',
    uang_bayar: '',
    catatan: '',
    items: [],
});

const totalAmount = computed(() =>
    cartItems.value.reduce((sum, item) => sum + Number(item.harga) * Number(item.jumlah), 0),
);

const firstItemError = computed(() => {
    if (form.errors.items) return form.errors.items;
    const dynamicKey = Object.keys(form.errors).find((key) => key.startsWith('items.'));
    return dynamicKey ? form.errors[dynamicKey] : '';
});

const addProduct = (product) => {
    stockNotice.value = '';
    const availableStock = Number(product.stok ?? 0);

    if (availableStock <= 0) {
        stockNotice.value = `Stok ${product.nama_produk} sedang habis.`;
        return;
    }

    const existing = cartItems.value.find((i) => i.id_produk === product.id_produk);
    if (existing) {
        if (existing.jumlah >= availableStock) {
            stockNotice.value = `Stok maksimal untuk ${product.nama_produk} adalah ${availableStock} unit.`;
            return;
        }
        existing.jumlah += 1;
    } else {
        cartItems.value.push({
            id_produk: product.id_produk,
            nama_produk: product.nama_produk,
            harga: Number(product.harga),
            stok: availableStock,
            jumlah: 1,
        });
    }
};

const updateQty = (productId, newQty) => {
    stockNotice.value = '';
    if (!Number.isFinite(newQty) || newQty < 1) {
        removeItem(productId);
        return;
    }
    const target = cartItems.value.find((i) => i.id_produk === productId);
    if (target) {
        const clampedQty = Math.min(Math.floor(newQty), target.stok);
        if (Math.floor(newQty) > target.stok) {
            stockNotice.value = `Jumlah ${target.nama_produk} disesuaikan ke batas stok tersedia (${target.stok} unit).`;
        }
        target.jumlah = Math.max(1, clampedQty);
    }
};

const removeItem = (productId) => {
    stockNotice.value = '';
    cartItems.value = cartItems.value.filter((i) => i.id_produk !== productId);
};

const clearCart = () => {
    stockNotice.value = '';
    cartItems.value = [];
};

const submitTransaction = () => {
    stockNotice.value = '';
    form.items = cartItems.value.map((i) => ({
        id_produk: i.id_produk,
        jumlah: i.jumlah,
    }));
    form.post(route('transaksi.store'));
};
</script>

<template>
    <Head title="Kasir / POS" />

    <component :is="isAdmin ? AdminLayout : CashierLayout" title="Buat Transaksi Baru (POS)">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <div class="lg:col-span-7">
                <TransaksiForm
                    :products="products"
                    :cart-items="cartItems"
                    :error="firstItemError"
                    :notice="stockNotice"
                    @add-product="addProduct"
                    @update-qty="updateQty"
                    @remove-item="removeItem"
                    @clear-cart="clearCart"
                />
            </div>

            <div class="lg:col-span-5">
                <div class="lg:sticky lg:top-20">
                    <TransactionSummary
                        :items="cartItems"
                        :total-amount="totalAmount"
                        :customers="customers"
                        :branches="branches"
                        :form="form"
                        @submit="submitTransaction"
                    />
                </div>
            </div>
        </div>
    </component>
</template>

