<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CashierLayout from '@/Layouts/CashierLayout.vue';
import BaseBadge from '@/Components/UI/BaseBadge.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import DetailTransaksiTable from '@/Pages/Transaksi/Components/DetailTransaksiTable.vue';
import PrintReceipt from '@/Pages/Transaksi/Components/PrintReceipt.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { useCurrency } from '@/Composables/useCurrency';
import { useDate } from '@/Composables/useDate';

defineProps({
    transaction: {
        type: Object,
        required: true,
    },
    storeInfo: {
        type: Object,
        default: () => ({}),
    },
});

const { can, isAdmin } = usePermissions();
const { formatCurrency } = useCurrency();
const { formatDate } = useDate();

const paperSize = ref('58mm');
const paperOptions = ['40mm', '58mm', '80mm'];

const printNota = () => {
    window.print();
};
</script>

<template>
    <Head :title="`Transaksi ${transaction.kode_transaksi || `#${transaction.id_transaksi}`}`" />

    <component
        :is="isAdmin ? AdminLayout : CashierLayout"
        :title="`Detail Transaksi ${transaction.kode_transaksi || `#TRX-${transaction.id_transaksi}`}`"
    >
        <div class="mx-auto max-w-6xl space-y-6 print:max-w-none print:space-y-0">
            <!-- Action Bar (hidden when printing) -->
            <div class="no-print flex flex-wrap items-center justify-between gap-3 print:hidden">
                <div class="flex flex-wrap items-center gap-2">
                    <BaseButton :href="route('transaksi.index')" variant="secondary" size="sm">
                        &larr; Daftar Transaksi
                    </BaseButton>
                    <BaseButton
                        v-if="can.viewTransactionHistory"
                        :href="route('transaksi.riwayat')"
                        variant="ghost"
                        size="sm"
                    >
                        Riwayat Transaksi
                    </BaseButton>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <BaseButton
                        :href="route('transaksi.nota', { transaksi: transaction.id_transaksi, size: paperSize })"
                        variant="secondary"
                        size="sm"
                    >
                        Halaman Cetak Nota
                    </BaseButton>
                    <BaseButton variant="secondary" size="sm" @click="printNota">
                        Cetak Nota ({{ paperSize }})
                    </BaseButton>
                    <BaseButton v-if="can.createTransactions" :href="route('transaksi.create')" variant="emerald" size="sm">
                        + Transaksi Baru
                    </BaseButton>
                </div>
            </div>

            <div
                class="enter-stagger-grid grid grid-cols-1 gap-6 lg:grid-cols-12 print:block"
                style="--stagger-base: 55ms"
            >
                <!-- Detail Transaksi (Screen View) -->
                <div class="no-print space-y-6 lg:col-span-8 print:hidden">
                    <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-neutral-100 pb-4">
                            <div>
                                <p class="text-xs text-neutral-500">Kode / ID Transaksi</p>
                                <h2 class="text-xl font-bold text-neutral-900">
                                    {{ transaction.kode_transaksi || `#TRX-${transaction.id_transaksi}` }}
                                </h2>
                            </div>
                            <div class="text-right">
                                <BaseBadge :variant="transaction.status === 'dibatalkan' ? 'danger' : 'success'">
                                    {{ transaction.status ?? 'selesai' }}
                                </BaseBadge>
                                <p class="mt-1 text-xs text-neutral-500">{{ formatDate(transaction.tanggal) }}</p>
                            </div>
                        </div>

                        <dl class="mt-4 grid grid-cols-1 gap-4 text-sm sm:grid-cols-4">
                            <div>
                                <dt class="text-xs text-neutral-500">Pelanggan</dt>
                                <dd class="font-medium text-neutral-900">
                                    <Link
                                        v-if="transaction.pelanggan?.id_pelanggan"
                                        :href="route('pelanggan.show', transaction.pelanggan.id_pelanggan)"
                                        class="hover:underline"
                                    >
                                        {{ transaction.pelanggan.nama_pelanggan }}
                                    </Link>
                                    <span v-else>Pelanggan Umum</span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-neutral-500">Kasir</dt>
                                <dd class="font-medium text-neutral-900">{{ transaction.nama_kasir ?? transaction.kasir?.name ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-neutral-500">Cabang</dt>
                                <dd class="font-medium text-neutral-900">{{ transaction.cabang?.nama_cabang ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-neutral-500">Metode Bayar</dt>
                                <dd class="font-medium uppercase text-neutral-900">
                                    {{ transaction.metode_pembayaran ?? 'tunai' }}
                                </dd>
                            </div>
                        </dl>

                        <div v-if="transaction.catatan" class="mt-4 rounded-lg bg-neutral-50 px-4 py-2.5 text-xs text-neutral-600">
                            <strong>Catatan:</strong> {{ transaction.catatan }}
                        </div>
                    </div>

                    <DetailTransaksiTable :details="transaction.detail ?? []" />

                    <div class="space-y-2.5 rounded-xl border border-neutral-200 bg-white px-6 py-4 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-neutral-600">Total Belanja Tercatat</span>
                            <span class="text-2xl font-bold text-neutral-900">
                                {{ formatCurrency(transaction.total_belanja) }}
                            </span>
                        </div>
                        <template v-if="transaction.uang_bayar !== null && transaction.uang_bayar !== undefined">
                            <div class="flex items-center justify-between border-t border-neutral-100 pt-2.5 text-sm">
                                <span class="text-neutral-500">Uang Pembayaran</span>
                                <span class="font-semibold text-neutral-800">{{ formatCurrency(transaction.uang_bayar) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-neutral-500">Kembalian</span>
                                <span class="font-semibold text-emerald-700">{{ formatCurrency(transaction.kembalian ?? 0) }}</span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Pratinjau Nota Thermal (40mm / 58mm / 80mm) -->
                <div class="lg:col-span-4 print:w-full">
                    <div class="rounded-xl border border-neutral-200 bg-neutral-50/70 p-4 print:border-0 print:bg-white print:p-0">
                        <div class="no-print mb-3 space-y-2.5 print:hidden">
                            <div class="flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <h3 class="text-sm font-semibold text-neutral-900">Pratinjau Nota Kasir</h3>
                                    <p class="text-[11px] text-neutral-500">Pilih lebar kertas thermal</p>
                                </div>
                                <BaseButton variant="secondary" size="sm" class="shrink-0 whitespace-nowrap" @click="printNota">
                                    Cetak Nota
                                </BaseButton>
                            </div>

                            <div class="flex items-center gap-1 rounded-lg border border-neutral-200 bg-white p-1">
                                <button
                                    v-for="size in paperOptions"
                                    :key="size"
                                    type="button"
                                    class="flex-1 rounded-md px-2 py-1 text-xs font-medium transition"
                                    :class="
                                        paperSize === size
                                            ? 'bg-neutral-900 text-white'
                                            : 'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900'
                                    "
                                    @click="paperSize = size"
                                >
                                    {{ size }}
                                </button>
                            </div>
                        </div>

                        <PrintReceipt
                            :transaction="transaction"
                            :paper-size="paperSize"
                            :store-info="storeInfo"
                        />
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>
