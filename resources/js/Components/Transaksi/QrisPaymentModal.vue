<script setup>
import { ref } from 'vue';
import { useCurrency } from '@/Composables/useCurrency';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    totalAmount: {
        type: Number,
        default: 0,
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
});

const emit = defineEmits(['close', 'confirm-paid']);
const { formatCurrency } = useCurrency();

const copiedNmid = ref(false);
const copiedAmount = ref(false);

const copyNmid = async () => {
    try {
        await navigator.clipboard.writeText(props.qrisInfo?.nmid || 'ID1026645584610');
        copiedNmid.value = true;
        setTimeout(() => {
            copiedNmid.value = false;
        }, 2000);
    } catch {
        // fallback
    }
};

const copyAmount = async () => {
    try {
        await navigator.clipboard.writeText(String(props.totalAmount));
        copiedAmount.value = true;
        setTimeout(() => {
            copiedAmount.value = false;
        }, 2000);
    } catch {
        // fallback
    }
};
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-neutral-900/80 p-4 backdrop-blur-sm print:hidden"
        role="dialog"
        aria-modal="true"
        aria-labelledby="qris-modal-title"
    >
        <div class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl transition-all">
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-neutral-100 bg-neutral-50/80 px-6 py-4">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-600 text-xs font-black text-white">
                        QR
                    </span>
                    <div>
                        <h3 id="qris-modal-title" class="text-sm font-bold text-neutral-900">
                            Pembayaran QRIS Nasional (GPN)
                        </h3>
                        <p class="text-xs text-neutral-500">Tampilkan kode ini kepada pelanggan untuk dipindai.</p>
                    </div>
                </div>
                <button
                    type="button"
                    class="rounded-lg p-1.5 text-neutral-400 hover:bg-neutral-200 hover:text-neutral-700 focus:outline-none"
                    aria-label="Tutup jendela QRIS"
                    @click="$emit('close')"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Modal Content -->
            <div class="p-6">
                <!-- Tagihan Nominal Card -->
                <div class="mb-5 rounded-xl border border-red-100 bg-red-50/70 p-4 text-center">
                    <p class="text-xs font-semibold uppercase tracking-wider text-red-600">Total Nominal Pembayaran</p>
                    <div class="mt-1 flex items-center justify-center gap-2">
                        <span class="text-3xl font-extrabold tracking-tight text-neutral-900">
                            {{ formatCurrency(totalAmount) }}
                        </span>
                        <button
                            type="button"
                            class="rounded-md border border-neutral-200 bg-white px-2 py-1 text-[11px] font-medium text-neutral-700 shadow-xs hover:bg-neutral-50"
                            :title="copiedAmount ? 'Tersalin!' : 'Salin nominal'"
                            @click="copyAmount"
                        >
                            {{ copiedAmount ? 'Tersalin' : 'Salin' }}
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-neutral-600">Pastikan nominal yang dibayar pelanggan sesuai dengan total di atas.</p>
                </div>

                <!-- Gambar Standee QRIS Resmi -->
                <div class="mx-auto max-w-[340px] overflow-hidden rounded-2xl border-2 border-neutral-900 bg-white shadow-md">
                    <img
                        :src="qrisInfo?.image_url || '/images/qris/qris-danu-anggo.jpg'"
                        alt="QRIS Danu Anggo, Pakaian"
                        class="h-auto w-full object-contain"
                    />
                </div>

                <!-- Rincian Merchant & NMID -->
                <div class="mt-5 space-y-2 rounded-xl bg-neutral-50 p-3.5 text-xs text-neutral-700">
                    <div class="flex items-center justify-between">
                        <span class="text-neutral-500">Nama Merchant:</span>
                        <span class="font-bold text-neutral-900">{{ qrisInfo?.merchant_name || 'Danu Anggo, Pakaian' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-neutral-500">NMID:</span>
                        <div class="flex items-center gap-1.5">
                            <span class="font-mono font-semibold text-neutral-900">{{ qrisInfo?.nmid || 'ID1026645584610' }}</span>
                            <button
                                type="button"
                                class="text-[11px] font-medium text-red-600 underline hover:text-red-700"
                                @click="copyNmid"
                            >
                                {{ copiedNmid ? 'Tersalin' : 'Salin' }}
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-neutral-500">Kode Terminal:</span>
                        <span class="font-semibold text-neutral-900">{{ qrisInfo?.terminal_code || 'A01' }}</span>
                    </div>
                </div>

                <!-- Aplikasi Pembayaran yang Didukung -->
                <div class="mt-4 text-center">
                    <p class="text-[11px] font-medium text-neutral-500">Menerima Seluruh Dompet Digital &amp; Mobile Banking Nasional:</p>
                    <div class="mt-2 flex flex-wrap items-center justify-center gap-1.5 text-[10px]">
                        <span class="rounded-full bg-neutral-100 px-2.5 py-0.5 font-medium text-neutral-700">GoPay</span>
                        <span class="rounded-full bg-neutral-100 px-2.5 py-0.5 font-medium text-neutral-700">OVO</span>
                        <span class="rounded-full bg-neutral-100 px-2.5 py-0.5 font-medium text-neutral-700">DANA</span>
                        <span class="rounded-full bg-neutral-100 px-2.5 py-0.5 font-medium text-neutral-700">ShopeePay</span>
                        <span class="rounded-full bg-neutral-100 px-2.5 py-0.5 font-medium text-neutral-700">BCA</span>
                        <span class="rounded-full bg-neutral-100 px-2.5 py-0.5 font-medium text-neutral-700">Livin</span>
                        <span class="rounded-full bg-neutral-100 px-2.5 py-0.5 font-medium text-neutral-700">BRImo</span>
                        <span class="rounded-full bg-neutral-100 px-2.5 py-0.5 font-medium text-neutral-700">BNI</span>
                        <span class="rounded-full bg-neutral-100 px-2.5 py-0.5 font-medium text-neutral-700">LinkAja</span>
                    </div>
                </div>
            </div>

            <!-- Modal Footer Actions -->
            <div class="flex flex-col-reverse gap-2 border-t border-neutral-100 bg-neutral-50/80 px-6 py-4 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    class="rounded-xl border border-neutral-300 bg-white px-4 py-2.5 text-xs font-semibold text-neutral-700 hover:bg-neutral-50"
                    @click="$emit('close')"
                >
                    Tutup Layar
                </button>
                <button
                    type="button"
                    class="rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700"
                    @click="$emit('confirm-paid')"
                >
                    Konfirmasi Pembayaran QRIS Berhasil &rarr;
                </button>
            </div>
        </div>
    </div>
</template>

