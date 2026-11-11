<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-neutral-50 px-4 py-12">
        <div class="mb-6 text-center">
            <Link :href="route('home')" class="inline-flex items-center gap-2.5">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-neutral-900 text-sm font-bold tracking-wider text-white">
                    TK
                </span>
                <span class="text-xl font-semibold tracking-tight text-neutral-900">TokoKita</span>
            </Link>
            <p class="mt-1.5 text-xs text-neutral-500">Portal Operasional Admin &amp; Kasir</p>
        </div>

        <div class="w-full max-w-md rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm sm:p-8">
            <slot />
        </div>

        <div class="mt-6 text-center">
            <Link :href="route('home')" class="text-xs font-medium text-neutral-500 transition hover:text-neutral-900">
                &larr; Kembali ke Halaman Storefront
            </Link>
        </div>
    </div>
</template>
