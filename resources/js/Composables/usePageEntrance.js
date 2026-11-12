import { onMounted, ref } from 'vue';

let activeShell = null;

/**
 * Menjaga elemen chrome navigasi (navbar / sidebar) agar hanya beranimasi
 * saat pertama kali membuka portal terkait dan tetap tenang saat berpindah halaman.
 */
export function usePageEntrance(shellName = null) {
    const animateChrome = ref(shellName ? activeShell !== shellName : true);

    onMounted(() => {
        if (shellName) {
            activeShell = shellName;
        }
    });

    return {
        animateChrome,
    };
}

