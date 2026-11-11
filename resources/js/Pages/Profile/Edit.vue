<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CashierLayout from '@/Layouts/CashierLayout.vue';
import BaseBadge from '@/Components/UI/BaseBadge.vue';
import { usePermissions } from '@/Composables/usePermissions';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
        default: false,
    },
    status: {
        type: String,
        default: '',
    },
});

const { user, isAdmin } = usePermissions();
</script>

<template>
    <Head title="Profil Akun" />

    <component :is="isAdmin ? AdminLayout : CashierLayout" title="Profil Akun">
        <div class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
                <div>
                    <p class="text-sm font-semibold text-neutral-900">{{ user?.name }}</p>
                    <p class="text-xs text-neutral-500">
                        {{ user?.email }} &middot; {{ user?.cabang?.nama_cabang ?? 'Semua Cabang' }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <BaseBadge :variant="isAdmin ? 'dark' : 'success'">
                        Role: {{ isAdmin ? 'Administrator' : 'Kasir' }}
                    </BaseBadge>
                    <BaseBadge :variant="user?.is_active ? 'info' : 'danger'">
                        {{ user?.is_active ? 'Aktif' : 'Nonaktif' }}
                    </BaseBadge>
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-xl"
                />
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                <UpdatePasswordForm class="max-w-xl" />
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
                <DeleteUserForm class="max-w-xl" />
            </div>
        </div>
    </component>
</template>

