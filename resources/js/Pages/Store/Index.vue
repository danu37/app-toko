<script setup>
import { onUnmounted, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import StoreLayout from '@/Layouts/StoreLayout.vue';
import BaseAlert from '@/Components/UI/BaseAlert.vue';
import BaseBadge from '@/Components/UI/BaseBadge.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BaseLoading from '@/Components/UI/BaseLoading.vue';
import EmptyState from '@/Components/Feedback/EmptyState.vue';
import StoreMediaBox from '@/Pages/Store/Components/StoreMediaBox.vue';
import { useCurrency } from '@/Composables/useCurrency';

defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
    featuredProducts: {
        type: Array,
        default: () => [],
    },
    branches: {
        type: Array,
        default: () => [],
    },
    storeStats: {
        type: Object,
        default: () => ({ total_products: 0, total_categories: 0, total_branches: 0 }),
    },
    errorMessage: {
        type: String,
        default: '',
    },
});

const { formatCurrency } = useCurrency();
const isTransitioning = ref(false);

const removeStart = router.on('start', () => {
    isTransitioning.value = true;
});
const removeFinish = router.on('finish', () => {
    isTransitioning.value = false;
});
onUnmounted(() => {
    removeStart();
    removeFinish();
});

const productLabel = (product) => {
    if (product.stok <= 0) return { text: 'Stok Habis', variant: 'danger' };
    if ((product.terjual_qty ?? 0) > 0) return { text: 'Terlaris', variant: 'dark' };
    if (product.is_featured) return { text: 'Unggulan', variant: 'neutral' };
    return null;
};
</script>

<template>
    <Head title="Beranda Toko" />

    <StoreLayout :branches="branches" :categories="categories">
        <div v-if="errorMessage" class="animate-enter-soft mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
            <BaseAlert variant="danger" :message="errorMessage" />
        </div>

        <!-- 1. HERO SECTION -->
        <section class="border-b border-neutral-200/80 bg-stone-50">
            <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-10 px-4 py-12 sm:px-6 sm:py-16 lg:grid-cols-12 lg:gap-14 lg:px-8 lg:py-20">
                <div class="enter-stagger space-y-6 lg:col-span-6">
                    <div class="inline-flex items-center gap-2 rounded-full border border-neutral-200 bg-white px-3.5 py-1 text-[11px] font-semibold uppercase tracking-widest text-neutral-600">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-600" aria-hidden="true" />
                        Katalog Kurasi Harian
                    </div>

                    <h1 class="text-3xl font-semibold tracking-tight text-neutral-900 sm:text-4xl lg:text-5xl lg:leading-[1.12]">
                        Kebutuhan Harian Pilihan untuk Keseharian Modern.
                    </h1>

                    <p class="max-w-xl text-base leading-relaxed text-neutral-600">
                        Hadirkan bahan pangan, minuman, dan perlengkapan rumah tangga berkualitas dengan standar mutu dan harga yang konsisten di {{ branches.length > 0 ? `${branches.length} cabang` : 'seluruh jaringan' }} TokoKita.
                    </p>

                    <div class="flex flex-wrap items-center gap-3 pt-1">
                        <BaseButton :href="route('store.shop')" size="lg">
                            Shop Now
                        </BaseButton>
                        <BaseButton href="#kategori" variant="secondary" size="lg">
                            Jelajahi Kategori
                        </BaseButton>
                    </div>

                    <dl class="grid max-w-md grid-cols-3 gap-4 border-t border-neutral-200/80 pt-6">
                        <div>
                            <dt class="text-xs text-neutral-500">Produk Aktif</dt>
                            <dd class="mt-0.5 text-lg font-semibold text-neutral-900">
                                {{ storeStats.total_products || featuredProducts.length }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-neutral-500">Kategori</dt>
                            <dd class="mt-0.5 text-lg font-semibold text-neutral-900">
                                {{ storeStats.total_categories || categories.length }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-neutral-500">Cabang Toko</dt>
                            <dd class="mt-0.5 text-lg font-semibold text-neutral-900">
                                {{ storeStats.total_branches || branches.length }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="lg:col-span-6">
                    <div
                        class="animate-enter-hero overflow-hidden rounded-2xl border border-neutral-200/90 bg-white p-2.5 shadow-sm"
                        style="--enter-delay: 90ms"
                    >
                        <div class="overflow-hidden rounded-xl bg-stone-100">
                            <img
                                src="/images/store/hero-online-shopping.jpg"
                                alt="Ilustrasi 3D tas belanja dan troli Online Shopping TokoKita"
                                width="735"
                                height="684"
                                loading="eager"
                                fetchpriority="high"
                                decoding="async"
                                class="aspect-[735/684] w-full object-cover"
                            />
                        </div>
                        <div class="flex items-center justify-between px-3 py-3 text-xs text-neutral-500">
                            <span class="font-medium text-neutral-800">Koleksi Terpilih &bull; Siap Tersedia</span>
                            <span>Harga Transparan</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 2. SHOP BY CATEGORIES -->
        <section id="kategori" class="mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-16 lg:px-8">
            <div
                class="animate-enter mb-8 flex flex-col justify-between gap-3 sm:flex-row sm:items-end"
                style="--enter-delay: 110ms"
            >
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-neutral-400">
                        Kurasi Departemen
                    </p>
                    <h2 class="mt-1 text-2xl font-semibold tracking-tight text-neutral-900">
                        Shop by Categories
                    </h2>
                </div>
                <Link
                    :href="route('store.shop')"
                    class="text-sm font-medium text-neutral-700 transition hover:text-neutral-900 hover:underline"
                >
                    Lihat Semua Produk &rarr;
                </Link>
            </div>

            <BaseLoading v-if="isTransitioning" variant="skeleton" :rows="2" />

            <EmptyState
                v-else-if="categories.length === 0"
                title="Kategori produk belum tersedia"
                description="Kategori aktif yang ditambahkan oleh administrator akan ditampilkan secara otomatis di sini."
            />

            <div
                v-else
                class="enter-stagger-grid flex snap-x snap-mandatory gap-4 overflow-x-auto pb-3 pt-1 sm:grid sm:grid-cols-2 sm:gap-5 sm:overflow-visible sm:py-0 lg:grid-cols-4"
                style="--stagger-base: 140ms"
            >
                <Link
                    v-for="cat in categories"
                    :key="cat.id_kategori"
                    :href="route('store.shop', { kategori: cat.slug })"
                    class="group flex w-64 shrink-0 snap-start flex-col overflow-hidden rounded-xl border border-neutral-200 bg-white transition hover:border-neutral-400 hover:shadow-sm sm:w-auto"
                >
                    <StoreMediaBox
                        :src="cat.gambar_url"
                        :alt="cat.nama_kategori"
                        :label="cat.nama_kategori"
                        variant="category"
                        aspect-class="aspect-[4/3]"
                    />
                    <div class="flex flex-1 flex-col justify-between p-4">
                        <div>
                            <h3 class="text-sm font-semibold text-neutral-900 group-hover:underline">
                                {{ cat.nama_kategori }}
                            </h3>
                            <p v-if="cat.deskripsi" class="mt-1 line-clamp-1 text-xs text-neutral-500">
                                {{ cat.deskripsi }}
                            </p>
                        </div>
                        <div class="mt-3 flex items-center justify-between border-t border-neutral-100 pt-2.5 text-xs text-neutral-500">
                            <span>{{ cat.produk_count ?? 0 }} produk</span>
                            <span class="font-medium text-neutral-800">&rarr;</span>
                        </div>
                    </div>
                </Link>
            </div>
        </section>

        <!-- 3. OUR BESTSELLERS -->
        <section id="bestsellers" class="border-t border-neutral-200/80 bg-stone-50/70">
            <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-16 lg:px-8">
                <div
                    class="animate-enter mb-8 flex flex-col justify-between gap-3 sm:flex-row sm:items-end"
                    style="--enter-delay: 160ms"
                >
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-neutral-400">
                            Produk Pilihan &amp; Terlaris
                        </p>
                        <h2 class="mt-1 text-2xl font-semibold tracking-tight text-neutral-900">
                            Our Bestsellers
                        </h2>
                    </div>
                    <Link
                        :href="route('store.shop')"
                        class="text-sm font-medium text-neutral-700 transition hover:text-neutral-900 hover:underline"
                    >
                        Jelajahi Katalog Lengkap &rarr;
                    </Link>
                </div>

                <BaseLoading v-if="isTransitioning" variant="skeleton" :rows="4" />

                <EmptyState
                    v-else-if="featuredProducts.length === 0"
                    title="Belum ada produk aktif"
                    description="Produk unggulan dan terlaris yang aktif di toko akan muncul di etalase ini."
                >
                    <template #action>
                        <BaseButton :href="route('store.shop')" variant="secondary" size="sm">
                            Buka Halaman Shop
                        </BaseButton>
                    </template>
                </EmptyState>

                <div
                    v-else
                    class="enter-stagger-grid grid grid-cols-1 gap-x-5 gap-y-7 sm:grid-cols-2 lg:grid-cols-4"
                    style="--stagger-base: 185ms"
                >
                    <article
                        v-for="product in featuredProducts"
                        :key="product.id_produk"
                        class="group flex flex-col overflow-hidden rounded-xl border border-neutral-200 bg-white transition hover:border-neutral-300 hover:shadow-sm"
                    >
                        <Link :href="route('store.product', product.slug)" class="relative block">
                            <StoreMediaBox
                                :src="product.gambar_url"
                                :alt="product.nama_produk"
                                :label="product.kategori?.nama_kategori ?? 'Produk'"
                                aspect-class="aspect-[4/5]"
                            />
                            <div v-if="productLabel(product)" class="absolute left-3 top-3">
                                <BaseBadge :variant="productLabel(product).variant" size="sm">
                                    {{ productLabel(product).text }}
                                </BaseBadge>
                            </div>
                        </Link>

                        <div class="flex flex-1 flex-col justify-between p-4">
                            <div>
                                <p class="text-[11px] font-medium uppercase tracking-wider text-neutral-400">
                                    {{ product.kategori?.nama_kategori ?? 'Umum' }}
                                </p>
                                <Link
                                    :href="route('store.product', product.slug)"
                                    class="mt-1 block line-clamp-2 text-sm font-semibold text-neutral-900 transition hover:underline"
                                >
                                    {{ product.nama_produk }}
                                </Link>
                            </div>

                            <div class="mt-4 space-y-3">
                                <div class="flex items-baseline justify-between gap-2">
                                    <span class="text-base font-bold tracking-tight text-neutral-900">
                                        {{ formatCurrency(product.harga) }}
                                    </span>
                                    <span
                                        class="text-xs font-medium"
                                        :class="product.stok > 0 ? 'text-neutral-500' : 'text-red-600'"
                                    >
                                        {{ product.stok > 0 ? `Stok ${product.stok}` : 'Habis' }}
                                    </span>
                                </div>

                                <BaseButton
                                    :href="route('store.product', product.slug)"
                                    variant="outline"
                                    size="sm"
                                    block
                                >
                                    Lihat Detail
                                </BaseButton>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <!-- 4. PROMOTION SECTION -->
        <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-16 lg:px-8">
            <div
                class="animate-enter grid grid-cols-1 overflow-hidden rounded-2xl border border-neutral-200 bg-stone-50 lg:grid-cols-12"
                style="--enter-delay: 200ms"
            >
                <div class="lg:col-span-6">
                    <img
                        src="/images/store/promo-editorial.webp"
                        alt="Penataan paket kebutuhan pokok dan rumah tangga hemat di TokoKita"
                        width="1000"
                        height="747"
                        loading="lazy"
                        decoding="async"
                        class="h-full max-h-[420px] w-full object-cover"
                    />
                </div>

                <div class="flex flex-col justify-center space-y-5 p-7 sm:p-10 lg:col-span-6 lg:p-14">
                    <div>
                        <BaseBadge variant="success">Penawaran Hemat Toko</BaseBadge>
                    </div>
                    <h2 class="text-2xl font-semibold tracking-tight text-neutral-900 sm:text-3xl">
                        Belanja Kebutuhan Rutin Lebih Cermat &amp; Terencana.
                    </h2>
                    <p class="text-sm leading-relaxed text-neutral-600">
                        Manfaatkan penawaran paket hemat mingguan dan harga transparan langsung dari katalog resmi kami. Pantau stok produk pilihan sebelum berkunjung ke cabang terdekat.
                    </p>
                    <ul class="space-y-1.5 text-xs text-neutral-600">
                        <li class="flex items-center gap-2">
                            <span class="h-1.5 w-1.5 rounded-full bg-neutral-900" aria-hidden="true" />
                            <span>Harga resmi terstandar di seluruh cabang</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="h-1.5 w-1.5 rounded-full bg-neutral-900" aria-hidden="true" />
                            <span>Pembaruan stok dan ketersediaan secara langsung</span>
                        </li>
                    </ul>
                    <div class="pt-1">
                        <BaseButton :href="route('store.shop')" size="lg">
                            Belanja Sekarang &rarr;
                        </BaseButton>
                    </div>
                </div>
            </div>
        </section>

        <!-- 5. TENTANG KAMI -->
        <section id="tentang-kami" class="border-t border-neutral-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-16 lg:px-8">
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:items-start">
                    <div class="animate-enter lg:col-span-4" style="--enter-delay: 210ms">
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-neutral-400">
                            Tentang Kami
                        </p>
                        <h2 class="mt-1 text-2xl font-semibold tracking-tight text-neutral-900">
                            Toko Harian dengan Pendekatan Minimalis &amp; Andal.
                        </h2>
                    </div>

                    <div
                        class="enter-stagger-grid grid grid-cols-1 gap-5 sm:grid-cols-3 lg:col-span-8"
                        style="--stagger-base: 230ms"
                    >
                        <div class="rounded-xl border border-neutral-200 bg-stone-50/50 p-5">
                            <h3 class="text-sm font-semibold text-neutral-900">Kurasi Produk Harian</h3>
                            <p class="mt-2 text-xs leading-relaxed text-neutral-600">
                                Setiap barang dipilih untuk memenuhi kebutuhan pokok, minuman, hingga kebersihan rumah tangga dengan standar mutu terjaga.
                            </p>
                        </div>
                        <div class="rounded-xl border border-neutral-200 bg-stone-50/50 p-5">
                            <h3 class="text-sm font-semibold text-neutral-900">Layanan Kasir Cepat</h3>
                            <p class="mt-2 text-xs leading-relaxed text-neutral-600">
                                Sistem transaksi terintegrasi memastikan rincian harga, jumlah barang, dan pencatatan struk berjalan akurat di kasir.
                            </p>
                        </div>
                        <div class="rounded-xl border border-neutral-200 bg-stone-50/50 p-5">
                            <h3 class="text-sm font-semibold text-neutral-900">Jaringan Gerai</h3>
                            <p class="mt-2 text-xs leading-relaxed text-neutral-600">
                                Didukung oleh {{ branches.length }} gerai terdaftar dengan katalog harga yang seragam dan mudah diperiksa kapan saja.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </StoreLayout>
</template>

