<script setup>
import { computed, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BaseSelect from '@/Components/UI/BaseSelect.vue';
import BaseInput from '@/Components/UI/BaseInput.vue';
import QrisPaymentModal from '@/Components/Transaksi/QrisPaymentModal.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { useCurrency } from '@/Composables/useCurrency';

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
    subtotalAmount: {
        type: Number,
        default: 0,
    },
    ppnAmount: {
        type: Number,
        default: 0,
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
    qrisInfo: {
        type: Object,
        default: () => ({
            merchant_name: 'Danu Anggo, Pakaian',
            nmid: 'ID1026645584610',
            terminal_code: 'A01',
            image_url: '/images/qris/qris-danu-anggo.jpg',
            code_url: '/images/qris/qris-code-only.png',
        }),
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
const showQrisModal = ref(false);
const qrisVerified = ref(false);
const copiedNmid = ref(false);

const copyNmid = async () => {
    try {
        await navigator.clipboard.writeText(props.qrisInfo?.nmid || 'ID1026645584610');
        copiedNmid.value = true;
        setTimeout(() => {
            copiedNmid.value = false;
        }, 2000);
    } catch {}
};

const handleQrisConfirm = () => {
    props.form.uang_bayar = props.totalAmount;
    qrisVerified.value = true;
    showQrisModal.value = false;
};

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
                <span>{{ formatCurrency(subtotalAmount) }}</span>
            </div>
            <div class="mt-1 flex items-center justify-between text-xs text-neutral-300">
                <span>PPN (11%)</span>
                <span>{{ formatCurrency(ppnAmount) }}</span>
            </div>
            <div class="mt-1.5 flex items-baseline justify-between border-t border-neutral-700 pt-1.5">
                <span class="text-sm font-medium text-neutral-200">Total Pembayaran</span>
                <span class="text-2xl font-bold tracking-tight">{{ formatCurrency(totalAmount) }}</span>
            </div>
        </div>

        <!-- KOTAK KHUSUS PEMBAYARAN QRIS (JIKA METODE = QRIS) -->
        <div v-if="form.metode_pembayaran === 'qris'" class="space-y-3 rounded-xl border-2 border-red-200 bg-red-50/40 p-4">
            <div class="flex items-center justify-between border-b border-red-100 pb-2.5">
                <div class="flex items-center gap-2">
                    <span class="flex h-6 w-6 items-center justify-center rounded bg-red-600 text-[10px] font-black text-white">
                        QR
                    </span>
                    <div>
                        <h3 class="text-xs font-bold text-neutral-900">QRIS Standar Pembayaran Nasional</h3>
                        <p class="text-[11px] text-neutral-500">Mendukung BCA, Mandiri, BRI, BNI, GoPay, OVO, Dana, ShopeePay</p>
                    </div>
                </div>
                <span class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold tracking-wider text-red-700">
                    GPN
                </span>
            </div>

            <!-- Merchant & NMID Info -->
            <div class="space-y-1.5 rounded-lg bg-white p-3 text-xs shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-neutral-500">Merchant:</span>
                    <span class="font-bold text-neutral-900">{{ qrisInfo?.merchant_name || 'Danu Anggo, Pakaian' }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-neutral-500">NMID:</span>
                    <div class="flex items-center gap-1.5">
                        <span class="font-mono font-semibold text-neutral-800">{{ qrisInfo?.nmid || 'ID1026645584610' }}</span>
                        <button
                            type="button"
                            class="text-[11px] font-medium text-red-600 hover:underline"
                            @click="copyNmid"
                        >
                            {{ copiedNmid ? 'Tersalin' : 'Salin' }}
                        </button>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-neutral-500">Terminal:</span>
                    <span class="font-medium text-neutral-700">{{ qrisInfo?.terminal_code || 'A01' }}</span>
                </div>
            </div>

            <!-- QRIS Preview & Customer Display Action -->
            <div class="flex items-center gap-3 rounded-lg border border-neutral-200 bg-white p-3">
                <button
                    type="button"
                    class="group relative flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-neutral-200 bg-neutral-50 p-1 transition hover:border-neutral-900"
                    title="Klik untuk perbesar QRIS"
                    @click="showQrisModal = true"
                >
                    <img
                        :src="qrisInfo?.code_url || '/images/qris/qris-code-only.png'"
                        alt="QR Code QRIS"
                        class="h-full w-full object-contain"
                    />
                    <span class="absolute inset-0 flex items-center justify-center bg-black/40 text-[9px] font-bold text-white opacity-0 transition group-hover:opacity-100">
                        Perbesar
                    </span>
                </button>
                <div class="min-w-0 flex-1 space-y-2">
                    <div>
                        <p class="text-xs font-semibold text-neutral-900">Scan QRIS Pelanggan</p>
                        <p class="text-[11px] text-neutral-500 leading-snug">
                            Tunjukkan ke pelanggan atau buka layar penuh agar pelanggan mudah memindai.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-neutral-300 bg-white px-3 py-1.5 text-xs font-semibold text-neutral-800 shadow-2xs hover:bg-neutral-50"
                        @click="showQrisModal = true"
                    >
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5h-4m4 0v4m0 0l-5-5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                        </svg>
                        <span>Buka Layar Penuh QRIS</span>
                    </button>
                </div>
            </div>

            <!-- Konfirmasi Pembayaran QRIS Kasir -->
            <div class="pt-1">
                <button
                    v-if="!qrisVerified"
                    type="button"
                    class="flex w-full items-center justify-center gap-1.5 rounded-lg border border-emerald-600 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800 transition hover:bg-emerald-100"
                    @click="handleQrisConfirm"
                >
                    <svg class="h-4 w-4 text-emerald-600" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    <span>Konfirmasi Pembayaran QRIS Diterima</span>
                </button>
                <div v-else class="flex items-center justify-between rounded-lg bg-emerald-100/80 px-3 py-2 text-xs font-medium text-emerald-900">
                    <span class="flex items-center gap-1.5">
                        <svg class="h-4 w-4 text-emerald-600" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span>Pembayaran QRIS Terverifikasi (Pas)</span>
                    </span>
                    <button
                        type="button"
                        class="text-[11px] text-emerald-700 underline hover:text-emerald-900"
                        @click="qrisVerified = false"
                    >
                        Ubah
                    </button>
                </div>
            </div>
        </div>

        <!-- Input Uang Pembayaran & Hitung Kembalian (Untuk Tunai / Metode Lain) -->
        <div v-else class="space-y-2.5 rounded-xl border border-neutral-200 bg-neutral-50/70 p-3.5">
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

        <!-- Modal Layar Penuh QRIS untuk Pelanggan -->
        <QrisPaymentModal
            :show="showQrisModal"
            :total-amount="totalAmount"
            :qris-info="qrisInfo"
            @close="showQrisModal = false"
            @confirm-paid="handleQrisConfirm"
        />
    </div>
</template>

