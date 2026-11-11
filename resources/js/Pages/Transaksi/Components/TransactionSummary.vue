<script setup>
import { computed, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BaseSelect from '@/Components/UI/BaseSelect.vue';
import BaseInput from '@/Components/UI/BaseInput.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { useCurrency } from '@/Composables/useCurrency';

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
    totalAmount: {
        type: Number,
        default: 0,
    },
    customers: {
        type: Array,
        default: () => [],
    },
    branches: {
        type: Array,
        default: () => [],
    },
    form: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['submit']);
const { can } = usePermissions();
const { formatCurrency } = useCurrency();

const customerQuery = ref('');

const filteredCustomerOptions = computed(() => {
    const q = customerQuery.value.trim().toLowerCase();
    return props.customers
        .filter((c) => {
            if (!q) return true;
            return (
                c.nama_pelanggan.toLowerCase().includes(q) ||
                (c.kota && c.kota.toLowerCase().includes(q)) ||
                (c.no_hp && c.no_hp.toLowerCase().includes(q))
            );
        })
        .map((c) => ({
            value: c.id_pelanggan,
            label: c.kota ? `${c.nama_pelanggan} (${c.kota})` : c.nama_pelanggan,
        }));
});

const paymentOptions = [
    { value: 'tunai', label: 'Tunai' },
    { value: 'qris', label: 'QRIS' },
    { value: 'transfer', label: 'Transfer Bank' },
    { value: 'debit', label: 'Kartu Debit' },
];

const totalItemQty = computed(() =>
    props.items.reduce((acc, i) => acc + Number(i.jumlah), 0),
);

const hasEnteredPayment = computed(
    () => props.form.uang_bayar !== '' && props.form.uang_bayar !== null && props.form.uang_bayar !== undefined,
);

const paidAmount = computed(() => {
    if (!hasEnteredPayment.value) return 0;
    const num = Number(props.form.uang_bayar);
    return Number.isFinite(num) ? num : 0;
});

const changeAmount = computed(() => paidAmount.value - props.totalAmount);

const isPaymentInsufficient = computed(
    () =>
        props.totalAmount > 0 &&
        ((props.form.metode_pembayaran === 'tunai' && (!hasEnteredPayment.value || paidAmount.value < props.totalAmount)) ||
            (hasEnteredPayment.value && paidAmount.value < props.totalAmount)),
);

const canSubmit = computed(
    () =>
        props.items.length > 0 &&
        Boolean(props.form.id_pelanggan) &&
        !isPaymentInsufficient.value,
);

const quickCashOptions = computed(() => {
    const presets = [];
    if (props.totalAmount > 0) {
        presets.push({ label: 'Uang Pas', value: props.totalAmount });
    }
    [20000, 50000, 100000, 200000].forEach((nominal) => {
        if (nominal > props.totalAmount) {
            presets.push({ label: formatCurrency(nominal), value: nominal });
        }
    });
    return presets.slice(0, 4);
});

const setPaymentAmount = (amount) => {
    props.form.uang_bayar = amount;
};

watch(
    () => [props.form.metode_pembayaran, props.totalAmount],
    ([metode, total]) => {
        if (metode !== 'tunai' && total > 0) {
            props.form.uang_bayar = total;
        }
    },
);
</script>

<template>
    <div class="space-y-3 rounded-xl border border-neutral-200 bg-white p-4 shadow-sm">
        <div class="border-b border-neutral-100 pb-2.5">
            <h2 class="text-base font-semibold text-neutral-900">Pembayaran &amp; Ringkasan</h2>
            <p class="text-xs text-neutral-500">Pilih pelanggan, masukkan uang pembayaran, lalu simpan transaksi.</p>
        </div>

        <!-- Pilih Pelanggan + Cari Cepat + Tambah Pelanggan -->
        <div class="space-y-1.5">
            <div class="flex items-center justify-between gap-2">
                <label for="trx_pelanggan" class="block text-sm font-medium text-neutral-700">
                    Pelanggan <span class="text-red-500">*</span>
                </label>
                <Link
                    v-if="can.createCustomers"
                    :href="route('pelanggan.create', { from: 'pos' })"
                    class="text-xs font-medium text-neutral-700 hover:text-neutral-900 hover:underline"
                >
                    + Pelanggan Baru
                </Link>
            </div>
            <input
                v-if="customers.length > 8"
                v-model="customerQuery"
                type="search"
                aria-label="Filter cepat daftar pelanggan"
                placeholder="Ketik nama/kota untuk saring pelanggan..."
                class="block w-full rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-1.5 text-xs text-neutral-900 placeholder-neutral-400 focus:border-neutral-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-neutral-900"
            />
            <BaseSelect
                id="trx_pelanggan"
                v-model="form.id_pelanggan"
                :options="filteredCustomerOptions"
                placeholder="Pilih pelanggan..."
                required
                :error="form.errors.id_pelanggan"
            />
        </div>

        <BaseSelect
            id="trx_cabang"
            v-model="form.id_cabang"
            label="Cabang"
            :options="branches.map((b) => ({ value: b.id_cabang, label: `${b.nama_cabang} (${b.kota})` }))"
            placeholder="Sesuai cabang akun"
            :error="form.errors.id_cabang"
        />

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <BaseSelect
                id="trx_metode"
                v-model="form.metode_pembayaran"
                label="Metode Bayar"
                :options="paymentOptions"
                placeholder=""
                :error="form.errors.metode_pembayaran"
            />
            <BaseInput
                id="trx_tanggal"
                v-model="form.tanggal"
                label="Tanggal"
                type="date"
                :error="form.errors.tanggal"
            />
        </div>

        <!-- Ringkasan Subtotal & Total -->
        <div class="rounded-xl bg-neutral-900 p-3.5 text-white">
            <div class="flex items-center justify-between text-xs text-neutral-300">
                <span>Subtotal ({{ totalItemQty }} unit / {{ items.length }} produk)</span>
                <span>{{ formatCurrency(totalAmount) }}</span>
            </div>
            <div class="mt-1.5 flex items-baseline justify-between border-t border-neutral-700 pt-1.5">
                <span class="text-sm font-medium text-neutral-200">Total Pembayaran</span>
                <span class="text-2xl font-bold tracking-tight">{{ formatCurrency(totalAmount) }}</span>
            </div>
        </div>

        <!-- Input Uang Pembayaran & Hitung Kembalian -->
        <div class="space-y-2.5 rounded-xl border border-neutral-200 bg-neutral-50/70 p-3.5">
            <BaseInput
                id="trx_uang_bayar"
                v-model="form.uang_bayar"
                label="Uang Pembayaran (Rp)"
                type="number"
                min="0"
                step="500"
                placeholder="Masukkan nominal bayar..."
                :required="form.metode_pembayaran === 'tunai'"
                :error="form.errors.uang_bayar"
            />

            <div v-if="form.metode_pembayaran === 'tunai' && quickCashOptions.length > 0" class="flex flex-wrap gap-1.5">
                <button
                    v-for="preset in quickCashOptions"
                    :key="preset.label"
                    type="button"
                    class="rounded-lg border border-neutral-200 bg-white px-2.5 py-1 text-xs font-medium text-neutral-700 transition hover:border-neutral-900 hover:text-neutral-900"
                    @click="setPaymentAmount(preset.value)"
                >
                    {{ preset.label }}
                </button>
            </div>

            <div
                class="flex items-center justify-between rounded-lg px-3 py-2 text-sm"
                :class="
                    totalAmount === 0 || !hasEnteredPayment
                        ? 'border border-neutral-200 bg-white text-neutral-600'
                        : changeAmount >= 0
                          ? 'border border-emerald-200 bg-emerald-50 text-emerald-800'
                          : 'border border-red-200 bg-red-50 text-red-700'
                "
            >
                <span class="font-medium">
                    {{
                        totalAmount > 0 && hasEnteredPayment && changeAmount < 0
                            ? 'Kurang Bayar'
                            : 'Kembalian'
                    }}
                </span>
                <span class="text-base font-bold">
                    {{
                        totalAmount === 0 || !hasEnteredPayment
                            ? formatCurrency(0)
                            : changeAmount < 0
                              ? formatCurrency(Math.abs(changeAmount))
                              : formatCurrency(changeAmount)
                    }}
                </span>
            </div>
        </div>

        <BaseInput
            id="trx_catatan"
            v-model="form.catatan"
            label="Catatan (Opsional)"
            placeholder="Catatan singkat transaksi..."
            :error="form.errors.catatan"
        />

        <BaseButton
            type="button"
            variant="emerald"
            size="lg"
            class="w-full"
            :disabled="!canSubmit"
            :loading="form.processing"
            @click="emit('submit')"
        >
            {{ form.processing ? 'Memproses Transaksi...' : 'Proses Transaksi' }}
        </BaseButton>
    </div>
</template>

