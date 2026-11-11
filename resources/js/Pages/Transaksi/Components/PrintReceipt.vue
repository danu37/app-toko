<script setup>
import { computed, onUnmounted, watch } from 'vue';
import { useCurrency } from '@/Composables/useCurrency';
import { useDate } from '@/Composables/useDate';
import { usePermissions } from '@/Composables/usePermissions';

const props = defineProps({
    transaction: {
        type: Object,
        required: true,
    },
    paperSize: {
        type: String,
        default: '58mm',
        validator: (value) => ['40mm', '58mm', '80mm'].includes(value),
    },
    storeInfo: {
        type: Object,
        default: () => ({}),
    },
});

const PAGE_SIZE_RULES = {
    '40mm': '@media print { @page { size: 40mm auto; margin: 0; } }',
    '58mm': '@media print { @page { size: 58mm auto; margin: 0; } }',
    '80mm': '@media print { @page { size: 80mm auto; margin: 0; } }',
};

const SCREEN_CLASSES = {
    '40mm': 'max-w-[184px] p-2.5 text-[10px] leading-tight receipt--40mm',
    '58mm': 'max-w-[236px] p-3.5 text-[11px] leading-snug receipt--58mm',
    '80mm': 'max-w-[316px] p-4 text-[12px] leading-normal receipt--80mm',
};

let pageStyleEl = null;

watch(
    () => props.paperSize,
    (size) => {
        if (typeof document === 'undefined') return;
        if (!pageStyleEl) {
            pageStyleEl = document.createElement('style');
            pageStyleEl.setAttribute('data-thermal-page', 'true');
            document.head.appendChild(pageStyleEl);
        }
        pageStyleEl.textContent = PAGE_SIZE_RULES[size] || PAGE_SIZE_RULES['58mm'];
    },
    { immediate: true },
);

onUnmounted(() => {
    if (pageStyleEl && pageStyleEl.parentNode) {
        pageStyleEl.parentNode.removeChild(pageStyleEl);
        pageStyleEl = null;
    }
});

const { formatCurrency } = useCurrency();
const { formatDate, formatDateTime } = useDate();
const { user: currentUser } = usePermissions();

const details = computed(() => props.transaction.detail ?? []);

const totalItemQty = computed(() =>
    details.value.reduce((acc, row) => acc + Number(row.jumlah ?? 0), 0),
);

const itemsSubtotal = computed(() => {
    if (details.value.length === 0) {
        return Number(props.transaction.total_belanja ?? 0);
    }
    return details.value.reduce((acc, row) => acc + Number(row.subtotal ?? 0), 0);
});

const discountAmount = computed(() => Number(props.transaction.diskon ?? 0));
const taxAmount = computed(() => Number(props.transaction.pajak ?? 0));

const kodeNota = computed(
    () => props.transaction.kode_transaksi || `#TRX-${props.transaction.id_transaksi}`,
);

const printedAt = computed(() => formatDateTime(new Date()));

const sizeClass = computed(() => SCREEN_CLASSES[props.paperSize] || SCREEN_CLASSES['58mm']);

const branchLocation = computed(() => {
    const parts = [props.storeInfo?.address, props.transaction.cabang?.kota].filter(Boolean);
    return parts.length > 0 ? parts.join(' - ') : null;
});

const cashierName = computed(() => {
    const recorded = props.transaction.nama_kasir || props.transaction.kasir?.name;
    if (recorded && String(recorded).trim() !== '') {
        return String(recorded).trim();
    }
    if (currentUser.value?.name && String(currentUser.value.name).trim() !== '') {
        return String(currentUser.value.name).trim();
    }
    return '-';
});
</script>

<template>
    <div
        id="printable-receipt"
        class="receipt mx-auto w-full rounded-lg border border-dashed border-neutral-300 bg-white font-mono text-neutral-900 shadow-sm"
        :class="sizeClass"
    >
        <!-- HEADER NOTA -->
        <div class="border-b border-dashed border-neutral-400 pb-2 text-center print:border-black">
            <img
                v-if="storeInfo?.logo_url"
                :src="storeInfo.logo_url"
                :alt="storeInfo?.name || 'Logo Toko'"
                class="mx-auto mb-1.5 h-8 w-auto object-contain grayscale"
            />
            <div
                v-else
                class="mx-auto mb-1 inline-flex h-6 w-6 items-center justify-center border border-neutral-900 text-[10px] font-bold print:border-black"
            >
                TK
            </div>

            <p class="font-bold uppercase tracking-wide">
                {{ storeInfo?.name || 'TokoKita' }}
            </p>
            <p v-if="transaction.cabang?.nama_cabang" class="text-[0.92em]">
                {{ transaction.cabang.nama_cabang }}
            </p>
            <p v-if="branchLocation" class="break-words text-[0.88em] text-neutral-600 print:text-black">
                {{ branchLocation }}
            </p>
            <p v-if="storeInfo?.phone" class="text-[0.88em] text-neutral-600 print:text-black">
                Telp: {{ storeInfo.phone }}
            </p>
        </div>

        <!-- INFORMASI TRANSAKSI -->
        <div class="space-y-0.5 border-b border-dashed border-neutral-400 py-2 text-[0.92em] print:border-black">
            <div class="flex justify-between gap-2">
                <span class="shrink-0">No Nota</span>
                <span class="min-w-0 break-all text-right font-semibold">{{ kodeNota }}</span>
            </div>
            <div class="flex justify-between gap-2">
                <span class="shrink-0">Tanggal</span>
                <span class="text-right">{{ formatDate(transaction.tanggal) }}</span>
            </div>
            <div class="flex justify-between gap-2">
                <span class="shrink-0">Kasir</span>
                <span class="min-w-0 break-words text-right">{{ cashierName }}</span>
            </div>
            <div class="flex justify-between gap-2">
                <span class="shrink-0">Pelanggan</span>
                <span class="min-w-0 break-words text-right">{{ transaction.pelanggan?.nama_pelanggan ?? 'Umum' }}</span>
            </div>
        </div>

        <!-- DETAIL BARANG -->
        <div class="border-b border-dashed border-neutral-400 py-2 print:border-black">
            <p v-if="details.length === 0" class="py-1 text-center text-[0.9em] text-neutral-500 print:text-black">
                (Rincian item historis tidak tersedia)
            </p>
            <div v-else class="space-y-1.5">
                <div v-for="row in details" :key="row.id_detail">
                    <p class="break-words font-semibold">{{ row.nama_produk }}</p>
                    <div class="flex flex-wrap items-baseline justify-between gap-x-2 text-[0.92em] tabular-nums">
                        <span class="whitespace-nowrap">{{ row.jumlah }} x {{ formatCurrency(row.harga) }}</span>
                        <span class="ml-auto whitespace-nowrap font-semibold">{{ formatCurrency(row.subtotal) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- TOTAL & PEMBAYARAN -->
        <div class="space-y-0.5 border-b border-dashed border-neutral-400 py-2 tabular-nums print:border-black">
            <div v-if="details.length > 0" class="flex flex-wrap items-baseline justify-between gap-x-2 text-[0.9em]">
                <span>Item ({{ details.length }})</span>
                <span class="ml-auto whitespace-nowrap">{{ totalItemQty }} unit</span>
            </div>
            <div class="flex flex-wrap items-baseline justify-between gap-x-2 text-[0.92em]">
                <span>Total Belanja</span>
                <span class="ml-auto whitespace-nowrap">{{ formatCurrency(itemsSubtotal) }}</span>
            </div>
            <div v-if="discountAmount > 0" class="flex flex-wrap items-baseline justify-between gap-x-2 text-[0.92em]">
                <span>Diskon</span>
                <span class="ml-auto whitespace-nowrap">-{{ formatCurrency(discountAmount) }}</span>
            </div>
            <div v-if="taxAmount > 0" class="flex flex-wrap items-baseline justify-between gap-x-2 text-[0.92em]">
                <span>Pajak</span>
                <span class="ml-auto whitespace-nowrap">{{ formatCurrency(taxAmount) }}</span>
            </div>
            <div class="flex flex-wrap items-baseline justify-between gap-x-2 border-t border-dotted border-neutral-300 pt-1 text-[1.08em] font-bold print:border-black">
                <span>TOTAL AKHIR</span>
                <span class="ml-auto whitespace-nowrap">{{ formatCurrency(transaction.total_belanja) }}</span>
            </div>

            <template v-if="transaction.uang_bayar !== null && transaction.uang_bayar !== undefined">
                <div class="flex flex-wrap items-baseline justify-between gap-x-2 pt-0.5 text-[0.94em]">
                    <span class="uppercase">Bayar ({{ transaction.metode_pembayaran ?? 'TUNAI' }})</span>
                    <span class="ml-auto whitespace-nowrap">{{ formatCurrency(transaction.uang_bayar) }}</span>
                </div>
                <div class="flex flex-wrap items-baseline justify-between gap-x-2 text-[0.96em] font-semibold">
                    <span>Kembalian</span>
                    <span class="ml-auto whitespace-nowrap">{{ formatCurrency(transaction.kembalian ?? 0) }}</span>
                </div>
            </template>
            <div v-else class="flex flex-wrap items-baseline justify-between gap-x-2 pt-0.5 text-[0.94em]">
                <span>Metode</span>
                <span class="ml-auto uppercase">{{ transaction.metode_pembayaran ?? 'TUNAI' }}</span>
            </div>
        </div>

        <!-- FOOTER NOTA -->
        <div class="space-y-0.5 pt-2 text-center text-[0.88em] text-neutral-600 print:text-black">
            <p v-if="transaction.catatan" class="mb-1 break-words italic">
                Catatan: "{{ transaction.catatan }}"
            </p>
            <p class="font-semibold">Terima kasih telah berbelanja!</p>
            <p v-if="storeInfo?.hours">{{ storeInfo.hours }}</p>
            <p v-if="storeInfo?.tagline">{{ storeInfo.tagline }}</p>
            <p class="pt-0.5 text-[0.82em] opacity-80">Dicetak: {{ printedAt }}</p>
        </div>
    </div>
</template>

