<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import BaseBadge from '@/Components/UI/BaseBadge.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BasePagination from '@/Components/UI/BasePagination.vue';
import EmptyState from '@/Components/Feedback/EmptyState.vue';
import AppFooter from '@/Layouts/Components/AppFooter.vue';
import { useCurrency } from '@/Composables/useCurrency';
import { useDate } from '@/Composables/useDate';

const props = defineProps({
    customer: {
        type: Object,
        default: null,
    },
    user: {
        type: Object,
        required: true,
    },
    rfidCards: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({ total_transaksi: 0, total_belanja: 0, transaksi_terakhir: null }),
    },
    transactions: {
        type: Object,
        required: true,
    },
});

const { formatCurrency } = useCurrency();
const { formatDate, formatDateTime } = useDate();

const activeCard = computed(() => {
    return props.rfidCards.find((c) => c.status === 'aktif') || props.rfidCards[0] || null;
});

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <Head title="Dashboard Pelanggan" />

    <div class="flex min-h-screen flex-col bg-neutral-50 text-neutral-900">
        <!-- Top Navigation Bar -->
        <header class="sticky top-0 z-40 border-b border-neutral-200 bg-white/95 backdrop-blur-sm">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-6">
                    <Link :href="route('home')" class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-neutral-900 text-xs font-bold tracking-wider text-white">
                            TK
                        </span>
                        <span class="text-base font-semibold tracking-tight text-neutral-900">TokoKita</span>
                    </Link>

                    <nav class="hidden md:flex items-center gap-5 text-sm font-medium text-neutral-600">
                        <Link :href="route('home')" class="hover:text-neutral-900 transition">Beranda</Link>
                        <Link :href="route('store.shop')" class="hover:text-neutral-900 transition">Katalog Produk</Link>
                        <span class="text-neutral-900 font-semibold">Area Pelanggan</span>
                    </nav>
                </div>

                <div class="flex items-center gap-3">
                    <div class="hidden text-right sm:block">
                        <div class="text-xs font-medium text-neutral-900">{{ user.name }}</div>
                        <div class="text-[11px] text-neutral-500">{{ user.email }}</div>
                    </div>
                    <BaseButton variant="secondary" size="sm" @click="logout">
                        Keluar
                    </BaseButton>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="mx-auto max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8 w-full space-y-6">
            <!-- Welcome Header -->
            <div class="flex flex-col justify-between gap-4 rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm md:flex-row md:items-center">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-xl font-bold tracking-tight text-neutral-900">
                            Selamat Datang, {{ customer?.nama_pelanggan || user.name }}!
                        </h1>
                        <BaseBadge variant="info">Pelanggan Toko</BaseBadge>
                    </div>
                    <p class="mt-1 text-xs text-neutral-500">
                        Akses ringkasan belanja, data keanggotaan toko, dan riwayat transaksi resmi Anda.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <BaseButton :href="route('pelanggan.order.create')" variant="emerald" size="sm">
                        + Beli Barang &amp; Upload Bukti
                    </BaseButton>
                    <BaseButton :href="route('store.shop')" variant="secondary" size="sm">
                        &larr; Katalog Produk
                    </BaseButton>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-medium uppercase tracking-wider text-neutral-500">Total Transaksi</div>
                    <div class="mt-2 text-2xl font-bold text-neutral-900">{{ stats.total_transaksi }}x</div>
                    <div class="mt-1 text-xs text-neutral-500">Transaksi selesai di toko</div>
                </div>

                <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-medium uppercase tracking-wider text-neutral-500">Total Pembelian</div>
                    <div class="mt-2 text-2xl font-bold text-emerald-600">{{ formatCurrency(stats.total_belanja) }}</div>
                    <div class="mt-1 text-xs text-neutral-500">Akumulasi seluruh belanjaan</div>
                </div>

                <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-medium uppercase tracking-wider text-neutral-500">Belanja Terakhir</div>
                    <div class="mt-2 text-lg font-bold text-neutral-900">
                        {{ stats.transaksi_terakhir ? formatDate(stats.transaksi_terakhir) : 'Belum Ada Transaksi' }}
                    </div>
                    <div class="mt-1 text-xs text-neutral-500">Kunjungan transaksi terbaru</div>
                </div>
            </div>

            <!-- Profile & RFID Grid -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Customer Profile Card -->
                <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm space-y-4">
                    <div class="border-b border-neutral-100 pb-3">
                        <h2 class="text-base font-semibold text-neutral-900">Informasi Akun &amp; Pelanggan</h2>
                        <p class="text-xs text-neutral-500">Data identitas pelanggan yang terdaftar di sistem toko.</p>
                    </div>

                    <dl class="grid grid-cols-1 gap-x-4 gap-y-3 sm:grid-cols-2 text-xs">
                        <div>
                            <dt class="font-medium text-neutral-500">Nama Lengkap</dt>
                            <dd class="mt-0.5 text-sm font-semibold text-neutral-900">{{ customer?.nama_pelanggan || user.name }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-neutral-500">Email Akun</dt>
                            <dd class="mt-0.5 text-sm text-neutral-800">{{ user.email }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-neutral-500">Nomor Telepon / WhatsApp</dt>
                            <dd class="mt-0.5 text-sm text-neutral-800">{{ customer?.no_hp || '-' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-neutral-500">Kota Asal</dt>
                            <dd class="mt-0.5 text-sm text-neutral-800">{{ customer?.kota || '-' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="font-medium text-neutral-500">Alamat Lengkap</dt>
                            <dd class="mt-0.5 text-sm text-neutral-800">{{ customer?.alamat || 'Belum diisi' }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- RFID Card Card -->
                <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-neutral-100 pb-3">
                        <div>
                            <h2 class="text-base font-semibold text-neutral-900">Kartu RFID Pelanggan</h2>
                            <p class="text-xs text-neutral-500">Kartu fisik untuk login instan &amp; identifikasi belanja.</p>
                        </div>
                        <BaseBadge v-if="activeCard?.is_active" variant="success">
                            Kartu Aktif
                        </BaseBadge>
                        <BaseBadge v-else-if="activeCard" variant="danger">
                            Kartu Dinonaktifkan
                        </BaseBadge>
                        <BaseBadge v-else variant="neutral">
                            Belum Ada Kartu
                        </BaseBadge>
                    </div>

                    <div v-if="activeCard" class="flex items-start gap-4 rounded-xl border border-neutral-100 bg-neutral-50 p-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-neutral-900 text-white shadow-sm">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <rect x="2" y="5" width="20" height="14" rx="2.5" stroke-width="1.8" />
                                <line x1="2" y1="10" x2="22" y2="10" stroke-width="1.8" />
                                <circle cx="6" cy="15" r="1.5" fill="currentColor" />
                                <path d="M14 13.5a2.5 2.5 0 0 1 0 3" stroke-linecap="round" stroke-width="1.8" />
                            </svg>
                        </div>
                        <div class="space-y-1 text-xs flex-1">
                            <div class="text-[11px] uppercase tracking-wider text-neutral-500">Nomor UID Kartu RFID</div>
                            <div class="font-mono text-base font-bold tracking-wider text-neutral-900">
                                {{ activeCard.uid }}
                            </div>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-neutral-500 pt-1">
                                <span>Terdaftar: {{ formatDate(activeCard.registered_at) }}</span>
                                <span v-if="activeCard.last_used_at">Terakhir dipakai: {{ formatDateTime(activeCard.last_used_at) }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-else class="rounded-xl border border-dashed border-neutral-200 bg-neutral-50/50 p-5 text-center text-xs text-neutral-500">
                        <svg class="mx-auto h-8 w-8 text-neutral-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <rect x="2" y="5" width="20" height="14" rx="2.5" stroke-width="1.5" />
                            <line x1="2" y1="10" x2="22" y2="10" stroke-width="1.5" />
                        </svg>
                        <p class="font-medium text-neutral-700">Kartu RFID belum didaftarkan</p>
                        <p class="mt-0.5 text-neutral-400">Hubungi petugas kasir atau admin toko kami untuk mendapatkan kartu fisik RFID akun Anda.</p>
                    </div>

                    <div class="rounded-lg border border-neutral-100 bg-neutral-50 px-3.5 py-2.5 text-[11px] text-neutral-500">
                        <strong>Tips:</strong> Kartu RFID dapat langsung ditempelkan pada mesin scanner RFID di halaman login untuk masuk ke dashboard ini tanpa memasukkan sandi.
                    </div>
                </div>
            </div>

            <!-- Transaction History Section -->
            <div class="rounded-xl border border-neutral-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-neutral-100 px-6 py-4">
                    <h2 class="text-base font-semibold text-neutral-900">Riwayat Transaksi Belanja</h2>
                    <p class="text-xs text-neutral-500">Daftar transaksi belanjaan Anda di TokoKita beserta bukti nota transaksi.</p>
                </div>

                <div v-if="(transactions.data || []).length === 0" class="p-8">
                    <EmptyState
                        title="Belum ada riwayat transaksi"
                        description="Anda belum memiliki transaksi belanja di toko. Silakan kunjungi kasir atau jelajahi katalog produk kami."
                    >
                        <template #action>
                            <BaseButton :href="route('store.shop')">Lihat Katalog Toko</BaseButton>
                        </template>
                    </EmptyState>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200 text-left text-xs">
                        <thead class="bg-neutral-50 text-neutral-600">
                            <tr>
                                <th class="px-5 py-3 font-semibold uppercase tracking-wider">No. Nota</th>
                                <th class="px-5 py-3 font-semibold uppercase tracking-wider">Waktu Transaksi</th>
                                <th class="px-5 py-3 font-semibold uppercase tracking-wider">Cabang</th>
                                <th class="px-5 py-3 font-semibold uppercase tracking-wider">Kasir</th>
                                <th class="px-5 py-3 font-semibold uppercase tracking-wider">Pembayaran</th>
                                <th class="px-5 py-3 font-semibold uppercase tracking-wider">Status</th>
                                <th class="px-5 py-3 font-semibold uppercase tracking-wider text-right">Total Belanja</th>
                                <th class="px-5 py-3 font-semibold uppercase tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            <tr
                                v-for="tx in transactions.data"
                                :key="tx.id_transaksi"
                                class="transition hover:bg-neutral-50/70"
                            >
                                <td class="whitespace-nowrap px-5 py-3 font-mono font-semibold text-neutral-900">
                                    {{ tx.kode_transaksi }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-neutral-600">
                                    {{ formatDateTime(tx.created_at || tx.tanggal) }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-neutral-700">
                                    {{ tx.cabang?.nama_cabang || 'Toko Utama' }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-neutral-700">
                                    {{ tx.nama_kasir || tx.user?.name || '-' }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-3">
                                    <BaseBadge variant="neutral">{{ String(tx.metode_pembayaran || 'Tunai').toUpperCase() }}</BaseBadge>
                                    <a
                                        v-if="tx.bukti_pembayaran_url"
                                        :href="tx.bukti_pembayaran_url"
                                        target="_blank"
                                        class="mt-1 block text-[11px] font-medium text-sky-700 hover:underline"
                                    >
                                        Lihat Bukti
                                    </a>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3">
                                    <BaseBadge
                                        :variant="
                                            tx.status === 'menunggu_verifikasi'
                                                ? 'warning'
                                                : tx.status === 'dibatalkan'
                                                  ? 'danger'
                                                  : 'success'
                                        "
                                    >
                                        {{
                                            tx.status === 'menunggu_verifikasi'
                                                ? 'Menunggu Verifikasi'
                                                : tx.status === 'dibatalkan'
                                                  ? 'Ditolak / Batal'
                                                  : 'Selesai'
                                        }}
                                    </BaseBadge>
                                    <p v-if="tx.catatan_verifikasi" class="mt-1 max-w-[180px] truncate text-[10px] text-neutral-500">
                                        {{ tx.catatan_verifikasi }}
                                    </p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-right font-semibold text-neutral-900">
                                    {{ formatCurrency(tx.total_belanja ?? tx.total ?? 0) }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-right">
                                    <a
                                        :href="route('pelanggan.transaksi.nota', tx.id_transaksi)"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-neutral-900 hover:underline"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                        </svg>
                                        <span>Cetak Struk</span>
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-if="transactions.meta" class="border-t border-neutral-100 p-4">
                        <BasePagination :meta="transactions.meta" />
                    </div>
                </div>
            </div>
        </main>

        <AppFooter />
    </div>
</template>

