<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import BaseButton from '@/Components/UI/BaseButton.vue';
import PrintReceipt from '@/Pages/Transaksi/Components/PrintReceipt.vue';
import { usePermissions } from '@/Composables/usePermissions';

const props = defineProps({
    transaction: {
        type: Object,
        required: true,
    },
    storeInfo: {
        type: Object,
        default: () => ({}),
    },
    initialSize: {
        type: String,
        default: '58mm',
    },
});

const { can } = usePermissions();
const paperSize = ref(['40mm', '58mm', '80mm'].includes(props.initialSize) ? props.initialSize : '58mm');

const paperOptions = [
    { value: '40mm', label: '40 mm (Mini)' },
    { value: '58mm', label: '58 mm (Standar)' },
    { value: '80mm', label: '80 mm (Lebar)' },
];

const triggerPrint = () => {
    window.print();
};
</script>

<template>
    <Head :title="`Cetak Nota ${transaction.kode_transaksi || `#${transaction.id_transaksi}`}`" />

    <div class="min-h-screen bg-neutral-100 text-neutral-900 print:min-h-0 print:bg-white">
        <!-- Toolbar Cetak (disembunyikan saat print) -->
        <header class="no-print sticky top-0 z-20 border-b border-neutral-200 bg-white/95 backdrop-blur">
            <div class="mx-auto flex max-w-4xl flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6">
                <div class="flex flex-wrap items-center gap-2">
                    <BaseButton
                        :href="route('transaksi.show', transaction.id_transaksi)"
                        variant="secondary"
                        size="sm"
                    >
                        &larr; Kembali ke Transaksi
                    </BaseButton>
                    <BaseButton
                        v-if="can.createTransactions"
                        :href="route('transaksi.create')"
                        variant="ghost"
                        size="sm"
                    >
                        + Transaksi Baru
                    </BaseButton>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div
                        role="group"
                        aria-label="Pilih ukuran kertas thermal"
                        class="inline-flex rounded-lg border border-neutral-200 bg-neutral-50 p-0.5"
                    >
                        <button
                            v-for="opt in paperOptions"
                            :key="opt.value"
                            type="button"
                            class="rounded-md px-2.5 py-1 text-xs font-medium transition"
                            :class="
                                paperSize === opt.value
                                    ? 'bg-neutral-900 text-white shadow-sm'
                                    : 'text-neutral-600 hover:text-neutral-900'
                            "
                            @click="paperSize = opt.value"
                        >
                            {{ opt.label }}
                        </button>
                    </div>

                    <BaseButton variant="emerald" size="sm" @click="triggerPrint">
                        Cetak Nota
                    </BaseButton>
                </div>
            </div>
        </header>

        <!-- Area Nota & Panduan Cetak -->
        <main class="mx-auto max-w-4xl px-4 py-8 print:m-0 print:max-w-none print:p-0">
            <div class="flex flex-col items-center gap-6 print:block">
                <PrintReceipt
                    :transaction="transaction"
                    :paper-size="paperSize"
                    :store-info="storeInfo"
                />

                <div class="no-print w-full max-w-lg rounded-xl border border-neutral-200 bg-white p-4 text-xs text-neutral-600">
                    <p class="font-semibold text-neutral-900">Panduan Pengaturan Printer Thermal ({{ paperSize }}):</p>
                    <ul class="mt-1.5 list-disc space-y-1 pl-4">
                        <li>Pada dialog cetak browser, atur <strong>Margins</strong> ke <strong>None / Tidak Ada</strong> dan matikan opsi <strong>Headers and footers</strong>.</li>
                        <li>Pilih <strong>Paper size</strong> di driver printer sesuai roll terpasang (mis. 58mm x 210mm / 58mm Receipt atau 80mm Receipt), dan pilih ukuran <strong>40 mm</strong> bila driver printer 58mm Anda memiliki area cetak efektif 40&ndash;44 mm agar sisi kanan angka tidak terpotong.</li>
                        <li>Atur <strong>Scale</strong> ke <strong>100% / Default</strong> agar lebar nota tepat dan tidak menyusut seperti halaman A4.</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</template>

