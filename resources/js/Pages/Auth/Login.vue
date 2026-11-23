<script setup>
import { nextTick, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Checkbox from '@/Components/Checkbox.vue';
import BaseAlert from '@/Components/UI/BaseAlert.vue';
import BaseButton from '@/Components/UI/BaseButton.vue';
import BaseInput from '@/Components/UI/BaseInput.vue';

defineProps({
    canResetPassword: {
        type: Boolean,
        default: false,
    },
    status: {
        type: String,
        default: '',
    },
});

const loginMode = ref('credentials'); // 'credentials' | 'rfid'
const showPassword = ref(false);
const rfidInputRef = ref(null);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const rfidForm = useForm({
    rfid_uid: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const submitRfid = () => {
    if (!rfidForm.rfid_uid || rfidForm.processing) {
        return;
    }

    rfidForm.post(route('login.rfid'), {
        onError: () => {
            rfidForm.reset('rfid_uid');
            focusRfidInput();
        },
    });
};

const focusRfidInput = () => {
    nextTick(() => {
        rfidInputRef.value?.focus();
    });
};

const setLoginMode = (mode) => {
    loginMode.value = mode;
    if (mode === 'rfid') {
        focusRfidInput();
    }
};

watch(loginMode, (newVal) => {
    if (newVal === 'rfid') {
        focusRfidInput();
    }
});
</script>

<template>
    <GuestLayout>
        <Head title="Masuk" />

        <div class="mb-5">
            <h1 class="text-lg font-semibold text-neutral-900">Masuk ke Akun Anda</h1>
            <p class="mt-1 text-sm text-neutral-500">
                Akses sistem untuk Admin, Kasir, dan Pelanggan.
            </p>
        </div>

        <!-- Mode Selector Tabs -->
        <div class="mb-5 grid grid-cols-2 gap-1 rounded-xl bg-neutral-100 p-1 text-xs font-medium">
            <button
                type="button"
                class="flex items-center justify-center gap-1.5 rounded-lg py-2 transition"
                :class="loginMode === 'credentials'
                    ? 'bg-white text-neutral-900 shadow-sm font-semibold'
                    : 'text-neutral-500 hover:text-neutral-800'"
                @click="setLoginMode('credentials')"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span>Email &amp; Sandi</span>
            </button>
            <button
                type="button"
                class="flex items-center justify-center gap-1.5 rounded-lg py-2 transition"
                :class="loginMode === 'rfid'
                    ? 'bg-white text-neutral-900 shadow-sm font-semibold'
                    : 'text-neutral-500 hover:text-neutral-800'"
                @click="setLoginMode('rfid')"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <rect x="2" y="5" width="20" height="14" rx="2" stroke-width="1.8" />
                    <line x1="2" y1="10" x2="22" y2="10" stroke-width="1.8" />
                    <circle cx="6" cy="15" r="1.5" fill="currentColor" />
                    <path d="M14 13.5a2.5 2.5 0 0 1 0 3" stroke-linecap="round" stroke-width="1.8" />
                </svg>
                <span>Kartu RFID</span>
            </button>
        </div>

        <!-- Status / Flash Alert -->
        <BaseAlert
            v-if="status"
            variant="success"
            :message="status"
            class="mb-4"
        />

        <!-- MODE: CREDENTIALS (EMAIL & PASSWORD) -->
        <template v-if="loginMode === 'credentials'">
            <BaseAlert
                v-if="form.errors.email || form.errors.password"
                variant="danger"
                :message="form.errors.email || form.errors.password"
                class="mb-4"
            />

            <form class="space-y-4" @submit.prevent="submit">
                <BaseInput
                    id="email"
                    v-model="form.email"
                    label="Alamat Email"
                    type="email"
                    placeholder="nama@toko.test"
                    required
                    autofocus
                    autocomplete="username"
                    :error="form.errors.email"
                />

                <div>
                    <div class="mb-1.5 flex items-center justify-between">
                        <label for="password" class="block text-sm font-medium text-neutral-700">
                            Kata Sandi <span class="text-red-600" aria-hidden="true">*</span>
                        </label>
                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-xs font-medium text-neutral-600 transition hover:text-neutral-900 hover:underline"
                        >
                            Lupa kata sandi?
                        </Link>
                    </div>

                    <div class="relative">
                        <input
                            id="password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            autocomplete="current-password"
                            placeholder="Masukkan kata sandi"
                            class="block w-full rounded-lg border border-neutral-300 bg-white py-2 pl-3 pr-10 text-sm text-neutral-900 shadow-sm placeholder:text-neutral-400 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900"
                            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.password }"
                        />
                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-xs font-medium text-neutral-500 hover:text-neutral-900 focus:outline-none"
                            :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Lihat kata sandi'"
                            @click="showPassword = !showPassword"
                        >
                            {{ showPassword ? 'Tutup' : 'Lihat' }}
                        </button>
                    </div>
                    <p v-if="form.errors.password" class="mt-1 text-xs font-medium text-red-600">
                        {{ form.errors.password }}
                    </p>
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label class="inline-flex cursor-pointer items-center gap-2">
                        <Checkbox v-model:checked="form.remember" name="remember" />
                        <span class="text-sm text-neutral-600">Ingat sesi saya</span>
                    </label>
                </div>

                <BaseButton
                    type="submit"
                    size="lg"
                    class="w-full"
                    :loading="form.processing"
                >
                    {{ form.processing ? 'Memproses...' : 'Masuk' }}
                </BaseButton>
            </form>
        </template>

        <!-- MODE: RFID LOGIN (ADMIN) -->
        <template v-else>
            <BaseAlert
                v-if="rfidForm.errors.rfid_uid"
                variant="danger"
                :message="rfidForm.errors.rfid_uid"
                class="mb-4"
            />

            <div
                class="group relative mb-5 cursor-pointer rounded-2xl border-2 border-dashed border-neutral-300 bg-neutral-50/70 p-6 text-center transition hover:border-neutral-400 hover:bg-neutral-50"
                @click="focusRfidInput"
            >
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-neutral-900 text-white shadow-md transition group-hover:scale-105">
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <rect x="2" y="5" width="20" height="14" rx="2.5" stroke-width="1.8" />
                        <line x1="2" y1="10" x2="22" y2="10" stroke-width="1.8" />
                        <circle cx="6" cy="15" r="1.5" fill="currentColor" />
                        <path d="M14 13.5a2.5 2.5 0 0 1 0 3" stroke-linecap="round" stroke-width="1.8" />
                        <path d="M17 12a5.5 5.5 0 0 1 0 6" stroke-linecap="round" stroke-width="1.8" />
                    </svg>
                </div>

                <div class="mt-4 flex items-center justify-center gap-2">
                    <span
                        class="h-2.5 w-2.5 rounded-full"
                        :class="rfidForm.processing ? 'bg-amber-500 animate-ping' : 'bg-emerald-500 animate-pulse'"
                    />
                    <span class="text-xs font-semibold uppercase tracking-wider text-neutral-700">
                        {{ rfidForm.processing ? 'Sedang Memverifikasi Kartu...' : 'Menunggu Kartu RFID...' }}
                    </span>
                </div>

                <p class="mt-2 text-sm text-neutral-600">
                    Tempelkan kartu RFID Anda pada mesin reader untuk login instan.
                </p>
                <p class="mt-0.5 text-xs text-neutral-400">
                    Mendukung akun Admin, Kasir, dan Pelanggan via RFID Reader USB / Bluetooth atau UID manual.
                </p>
            </div>

            <form class="space-y-4" @submit.prevent="submitRfid">
                <div>
                    <label for="rfid_uid_input" class="mb-1.5 block text-sm font-medium text-neutral-700">
                        Nomor UID Kartu RFID
                    </label>
                    <div class="relative">
                        <input
                            id="rfid_uid_input"
                            ref="rfidInputRef"
                            v-model="rfidForm.rfid_uid"
                            type="text"
                            autocomplete="off"
                            autocorrect="off"
                            spellcheck="false"
                            placeholder="Scan kartu atau ketik UID..."
                            class="block w-full rounded-lg border border-neutral-300 bg-white py-2.5 pl-3 pr-10 font-mono text-sm tracking-wider text-neutral-900 shadow-sm placeholder:text-neutral-400 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900"
                            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': rfidForm.errors.rfid_uid }"
                            @keydown.enter.prevent="submitRfid"
                        />
                        <button
                            v-if="rfidForm.rfid_uid"
                            type="button"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-xs text-neutral-400 hover:text-neutral-600"
                            @click="rfidForm.rfid_uid = ''; focusRfidInput()"
                        >
                            Bersihkan
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-neutral-500">
                        Reader akan mengirim enter otomatis. Jika memasukkan manual, tekan Enter atau tombol di bawah.
                    </p>
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label class="inline-flex cursor-pointer items-center gap-2">
                        <Checkbox v-model:checked="rfidForm.remember" name="remember_rfid" />
                        <span class="text-sm text-neutral-600">Ingat sesi saya</span>
                    </label>
                </div>

                <BaseButton
                    type="submit"
                    size="lg"
                    class="w-full"
                    :disabled="!rfidForm.rfid_uid || rfidForm.processing"
                    :loading="rfidForm.processing"
                >
                    {{ rfidForm.processing ? 'Memverifikasi...' : 'Masuk via Kartu RFID' }}
                </BaseButton>

                <div class="text-center pt-2">
                    <button
                        type="button"
                        class="text-xs font-medium text-neutral-600 hover:text-neutral-900 hover:underline"
                        @click="setLoginMode('credentials')"
                    >
                        &larr; Masuk dengan Email &amp; Kata Sandi
                    </button>
                </div>
            </form>
        </template>

        <div class="mt-6 rounded-xl border border-neutral-100 bg-neutral-50 px-4 py-3 text-xs text-neutral-500">
            Akses akun internal dikelola oleh Administrator. Kartu RFID Admin didaftarkan melalui menu Manajemen Pengguna.
        </div>
    </GuestLayout>
</template>
