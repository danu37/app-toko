<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import BaseButton from '@/Components/UI/BaseButton.vue';

const props = defineProps({
    status: {
        type: Number,
        required: true,
    },
    message: {
        type: String,
        default: '',
    },
});

const page = usePage();
const authUser = computed(() => page.props.auth?.user ?? null);

const meta = computed(() => {
    switch (props.status) {
        case 403:
            return {
                badge: 'HTTP 403',
                title: 'Akses Ditolak',
                description:
                    props.message ||
                    'Anda tidak memiliki hak akses untuk membuka halaman atau menjalankan tindakan ini.',
            };
        case 404:
            return {
                badge: 'HTTP 404',
                title: 'Halaman Tidak Ditemukan',
                description:
                    props.message ||
                    'Halaman atau data yang Anda cari tidak tersedia atau telah dipindahkan.',
            };
        case 503:
            return {
                badge: 'HTTP 503',
                title: 'Layanan Sedang Pemeliharaan',
                description:
                    props.message ||
                    'Sistem sedang menjalani pemeliharaan singkat. Silakan coba beberapa saat lagi.',
            };
        default:
            return {
                badge: `HTTP ${props.status}`,
                title: 'Terjadi Kesalahan Sistem',
                description:
                    props.message ||
                    'Maaf, terjadi gangguan tak terduga saat memproses permintaan Anda.',
            };
    }
});

const goBack = () => {
    if (typeof window !== 'undefined' && window.history.length > 1) {
        window.history.back();
        return;
    }
    window.location.href = authUser.value ? '/dashboard' : '/';
};
</script>

<template>
    <Head :title="`${status} - ${meta.title}`" />

    <div class="flex min-h-screen flex-col items-center justify-center bg-neutral-50 px-4 py-12 text-neutral-900">
        <div class="animate-enter w-full max-w-md rounded-2xl border border-neutral-200 bg-white p-8 text-center shadow-sm">
            <div class="enter-stagger" style="--stagger-base: 50ms">
                <Link href="/" class="mx-auto inline-flex items-center gap-2">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-neutral-900 text-xs font-bold tracking-wider text-white">
                        TK
                    </span>
                    <span class="text-sm font-semibold tracking-tight text-neutral-900">TokoKita</span>
                </Link>

                <div class="mt-6">
                    <span class="inline-flex rounded-full border border-neutral-200 bg-neutral-100 px-3 py-1 text-xs font-semibold tracking-wider text-neutral-700">
                        {{ meta.badge }}
                    </span>
                    <h1 class="mt-3 text-2xl font-bold tracking-tight text-neutral-900">
                        {{ meta.title }}
                    </h1>
                    <p class="mt-2 text-sm leading-relaxed text-neutral-500">
                        {{ meta.description }}
                    </p>
                </div>

                <div class="mt-7 flex flex-wrap items-center justify-center gap-3">
                    <BaseButton variant="secondary" @click="goBack">
                        &larr; Kembali
                    </BaseButton>
                    <BaseButton v-if="authUser" href="/dashboard">
                        Ke Dashboard
                    </BaseButton>
                    <BaseButton v-else href="/">
                        Ke Beranda Toko
                    </BaseButton>
                </div>
            </div>
        </div>
    </div>
</template>

