import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function usePermissions() {
    const page = usePage();

    const user = computed(() => page.props.auth?.user ?? null);
    const can = computed(() => page.props.auth?.can ?? {});
    const isAdmin = computed(() => Boolean(can.value.isAdmin));
    const isKasir = computed(() => Boolean(can.value.isKasir));

    const hasPermission = (permission) => Boolean(can.value?.[permission]);

    return {
        user,
        can,
        isAdmin,
        isKasir,
        hasPermission,
    };
}

