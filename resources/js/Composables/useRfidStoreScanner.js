import { onMounted, onUnmounted, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { usePermissions } from '@/Composables/usePermissions';

export function useRfidStoreScanner() {
    const { user } = usePermissions();

    const rfidState = reactive({
        processing: false,
        scannedUid: '',
        error: '',
        success: '',
        info: '',
    });

    let buffer = '';
    let lastKeyTime = 0;
    let keyIntervals = [];
    let resetTimer = null;
    let feedbackTimer = null;

    const clearFeedbackTimer = () => {
        if (feedbackTimer) {
            clearTimeout(feedbackTimer);
            feedbackTimer = null;
        }
    };

    const setFeedbackTimer = (fn, delay = 5000) => {
        clearFeedbackTimer();
        feedbackTimer = setTimeout(fn, delay);
    };

    const resetBuffer = () => {
        buffer = '';
        keyIntervals = [];
        lastKeyTime = 0;
        if (resetTimer) {
            clearTimeout(resetTimer);
            resetTimer = null;
        }
    };

    const dismissFeedback = () => {
        clearFeedbackTimer();
        rfidState.error = '';
        rfidState.info = '';
        rfidState.success = '';
    };

    const handleRfidScan = (rawUid) => {
        const uid = rawUid ? rawUid.trim() : '';
        if (!uid || uid.length < 4) return;

        rfidState.scannedUid = uid;
        dismissFeedback();

        // 1. Jika pengguna sudah login sebelumnya di sesi ini
        if (user.value) {
            rfidState.info = `Kartu RFID terdeteksi (${uid}). Anda sudah masuk sebagai ${user.value.name}. Mengarahkan ke Dashboard...`;
            const targetRoute = user.value.role === 'pelanggan'
                ? route('pelanggan.dashboard')
                : route('dashboard');

            setFeedbackTimer(() => {
                router.visit(targetRoute);
            }, 600);
            return;
        }

        // 2. Jika guest (belum login), autentikasi instan via RFID
        rfidState.processing = true;

        router.post(
            route('login.rfid'),
            {
                rfid_uid: uid,
                remember: true,
            },
            {
                preserveScroll: true,
                onError: (errors) => {
                    rfidState.processing = false;
                    const message =
                        errors.rfid_uid ||
                        Object.values(errors)[0] ||
                        'Kartu RFID tidak dikenali atau sedang dinonaktifkan.';
                    rfidState.error = message;

                    setFeedbackTimer(() => {
                        rfidState.error = '';
                    }, 6000);
                },
                onSuccess: () => {
                    rfidState.processing = false;
                    rfidState.success = 'Berhasil masuk via RFID! Mengalihkan ke dashboard...';
                },
                onFinish: () => {
                    rfidState.processing = false;
                },
            },
        );
    };

    const onKeyDown = (event) => {
        // Abaikan tombol modifier atau shortcut browser (Ctrl+C, Ctrl+R, Alt+F4, Cmd, dll)
        if (event.ctrlKey || event.metaKey || event.altKey) {
            return;
        }

        // Abaikan tombol kontrol non-karakter
        const ignoredKeys = [
            'Shift',
            'ShiftLeft',
            'ShiftRight',
            'CapsLock',
            'Tab',
            'Escape',
            'Control',
            'Alt',
            'Meta',
            'ArrowUp',
            'ArrowDown',
            'ArrowLeft',
            'ArrowRight',
        ];
        if (ignoredKeys.includes(event.key)) {
            return;
        }

        const now = performance.now();
        const activeEl = document.activeElement;
        const isInputFocused =
            activeEl &&
            (activeEl.tagName === 'INPUT' ||
                activeEl.tagName === 'TEXTAREA' ||
                activeEl.isContentEditable);

        // Jika tombol Enter ditekan -> evaluasi apakah scanner selesai membaca
        if (event.key === 'Enter') {
            const capturedBuffer = buffer;
            const capturedIntervals = [...keyIntervals];

            if (capturedBuffer.length >= 4) {
                let isHardwareScan = false;

                if (!isInputFocused) {
                    // Jika tidak sedang fokus di input apapun, pasti pemindaian kartu RFID / input UID
                    isHardwareScan = true;
                } else {
                    // Jika fokus di input (misal search bar), periksa kecepatan pengetikan
                    const avgInterval =
                        capturedIntervals.length > 0
                            ? capturedIntervals.reduce((a, b) => a + b, 0) / capturedIntervals.length
                            : 999;
                    const maxInterval =
                        capturedIntervals.length > 0 ? Math.max(...capturedIntervals) : 999;

                    // Scanner RFID mengetik sangat cepat (biasanya interval rata-rata < 65ms)
                    if (avgInterval <= 65 || maxInterval <= 110) {
                        isHardwareScan = true;
                    }
                }

                if (isHardwareScan) {
                    event.preventDefault();
                    event.stopPropagation();

                    // Jika input search sedang fokus, bersihkan agar tidak mencari UID sebagai kata kunci
                    if (isInputFocused && typeof activeEl.value === 'string') {
                        if (activeEl.value.includes(capturedBuffer)) {
                            activeEl.value = activeEl.value.replace(capturedBuffer, '').trim();
                            activeEl.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                        activeEl.blur();
                    }

                    resetBuffer();
                    handleRfidScan(capturedBuffer);
                    return;
                }
            }

            resetBuffer();
            return;
        }

        // Hanya tangkap karakter tunggal yang printable
        if (event.key.length === 1) {
            if (lastKeyTime > 0) {
                const interval = now - lastKeyTime;

                // Jika sedang fokus di input dan interval lambat (ketikan manusia > 90ms),
                // reset buffer scanner agar tidak salah deteksi ketikan teks manual
                if (isInputFocused && interval > 90) {
                    buffer = '';
                    keyIntervals = [];
                } else if (!isInputFocused && interval > 600) {
                    // Jika tidak fokus di input tapi jeda lebih dari 600ms, mulai buffer baru
                    buffer = '';
                    keyIntervals = [];
                }

                keyIntervals.push(interval);
            }

            buffer += event.key;
            lastKeyTime = now;

            // Timer pembersih otomatis jika pembacaan terputus
            if (resetTimer) clearTimeout(resetTimer);
            resetTimer = setTimeout(() => {
                resetBuffer();
            }, 800);
        }
    };

    onMounted(() => {
        window.addEventListener('keydown', onKeyDown, { capture: true });
    });

    onUnmounted(() => {
        window.removeEventListener('keydown', onKeyDown, { capture: true });
        resetBuffer();
        clearFeedbackTimer();
    });

    return {
        rfidState,
        dismissFeedback,
        handleRfidScan,
    };
}

