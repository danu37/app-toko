export const MAX_IMAGE_BYTES = 2 * 1024 * 1024; // 2 MB
export const TARGET_IMAGE_BYTES = 1_900_000; // Margin aman <= 2 MB (desimal maupun biner)

export const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
export const ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

export function formatFileSize(bytes) {
    const value = Number(bytes ?? 0);
    if (value >= 1024 * 1024) {
        return `${(value / (1024 * 1024)).toFixed(1)} MB`;
    }
    if (value >= 1024) {
        return `${Math.round(value / 1024)} KB`;
    }
    return `${value} B`;
}

function getFileExtension(filename = '') {
    const parts = String(filename).toLowerCase().split('.');
    return parts.length > 1 ? parts.pop() : '';
}

function replaceFileExtension(filename, newExt) {
    const base = String(filename).replace(/\.[^/.]+$/, '') || 'gambar';
    return `${base}.${newExt}`;
}

function extensionForMime(mime, originalExt) {
    if (mime === 'image/jpeg') {
        return originalExt === 'jpeg' ? 'jpeg' : 'jpg';
    }
    if (mime === 'image/webp') {
        return 'webp';
    }
    if (mime === 'image/png') {
        return 'png';
    }
    return originalExt || 'jpg';
}

function loadDrawableImage(blob) {
    if (typeof createImageBitmap === 'function') {
        return createImageBitmap(blob, { imageOrientation: 'from-image' })
            .then((bitmap) => ({
                source: bitmap,
                width: bitmap.width,
                height: bitmap.height,
                release: () => {
                    if (typeof bitmap.close === 'function') {
                        bitmap.close();
                    }
                },
            }))
            .catch(() => loadViaHtmlImage(blob));
    }

    return loadViaHtmlImage(blob);
}

function loadViaHtmlImage(blob) {
    return new Promise((resolve, reject) => {
        const url = URL.createObjectURL(blob);
        const img = new Image();

        img.onload = () => {
            URL.revokeObjectURL(url);
            if (!img.naturalWidth || !img.naturalHeight) {
                reject(new Error('Dimensi gambar tidak valid atau rusak.'));
                return;
            }
            resolve({
                source: img,
                width: img.naturalWidth,
                height: img.naturalHeight,
                release: () => {},
            });
        };

        img.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new Error('File gambar rusak atau gagal dibuka untuk kompresi.'));
        };

        img.src = url;
    });
}

function canvasToBlob(canvas, mimeType, quality) {
    return new Promise((resolve) => {
        canvas.toBlob(
            (blob) => {
                resolve(blob);
            },
            mimeType,
            quality,
        );
    });
}

async function encodeCanvas(source, width, height, mimeType, quality) {
    const canvas = document.createElement('canvas');
    canvas.width = Math.max(1, Math.round(width));
    canvas.height = Math.max(1, Math.round(height));

    const ctx = canvas.getContext('2d');
    if (!ctx) {
        return null;
    }

    ctx.imageSmoothingEnabled = true;
    ctx.imageSmoothingQuality = 'high';

    if (mimeType === 'image/jpeg') {
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
    } else {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    }

    ctx.drawImage(source, 0, 0, canvas.width, canvas.height);

    const blob = await canvasToBlob(canvas, mimeType, quality);
    canvas.width = 0;
    canvas.height = 0;

    if (!blob || blob.size < 1 || blob.type !== mimeType) {
        return null;
    }

    return blob;
}

function detectAlphaTransparency(source, width, height) {
    const sampleMax = 128;
    const scale = Math.min(1, sampleMax / Math.max(width, height));
    const sw = Math.max(1, Math.round(width * scale));
    const sh = Math.max(1, Math.round(height * scale));

    const canvas = document.createElement('canvas');
    canvas.width = sw;
    canvas.height = sh;
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    if (!ctx) {
        return false;
    }

    ctx.drawImage(source, 0, 0, sw, sh);
    const data = ctx.getImageData(0, 0, sw, sh).data;
    canvas.width = 0;
    canvas.height = 0;

    for (let i = 3; i < data.length; i += 4) {
        if (data[i] < 250) {
            return true;
        }
    }

    return false;
}

async function searchBestLossyBlob({ source, width, height, targetMime, maxBytes, targetBytes, initialQualities }) {
    let bestUnderMax = null;

    // Langkah 1: Turunkan kualitas secara bertahap pada resolusi penuh.
    for (const quality of initialQualities) {
        const blob = await encodeCanvas(source, width, height, targetMime, quality);
        if (!blob) continue;

        if (blob.size <= targetBytes) {
            return { blob, finalWidth: width, finalHeight: height };
        }

        if (blob.size <= maxBytes && (!bestUnderMax || blob.size < bestUnderMax.blob.size)) {
            bestUnderMax = { blob, finalWidth: width, finalHeight: height };
        }
    }

    if (bestUnderMax) {
        return bestUnderMax;
    }

    // Langkah 2: Turunkan resolusi secara proporsional (menjaga rasio aspek) + kualitas bertahap.
    const scales = [0.85, 0.72, 0.60, 0.48, 0.38, 0.30, 0.22, 0.16];
    const scaledQualities = [0.84, 0.76, 0.68, 0.58, 0.48];

    for (const scale of scales) {
        const scaledWidth = Math.max(1, Math.round(width * scale));
        const scaledHeight = Math.max(1, Math.round(height * scale));

        for (const quality of scaledQualities) {
            const blob = await encodeCanvas(source, scaledWidth, scaledHeight, targetMime, quality);
            if (!blob) continue;

            if (blob.size <= targetBytes) {
                return { blob, finalWidth: scaledWidth, finalHeight: scaledHeight };
            }

            if (blob.size <= maxBytes && (!bestUnderMax || blob.size < bestUnderMax.blob.size)) {
                bestUnderMax = { blob, finalWidth: scaledWidth, finalHeight: scaledHeight };
            }
        }

        if (bestUnderMax) {
            return bestUnderMax;
        }
    }

    return bestUnderMax;
}

export async function autoCompressImage(file, maxBytes = MAX_IMAGE_BYTES) {
    if (!(file instanceof File)) {
        throw new Error('File tidak valid.');
    }

    const originalExt = getFileExtension(file.name);
    if (!ALLOWED_IMAGE_EXTENSIONS.includes(originalExt)) {
        throw new Error('Ekstensi file harus .jpg, .jpeg, .png, atau .webp.');
    }

    if (file.type && !ALLOWED_IMAGE_TYPES.includes(file.type)) {
        throw new Error('Tipe MIME file harus JPG, PNG, atau WEBP.');
    }

    const detectedMime = file.type || (originalExt === 'png' ? 'image/png' : originalExt === 'webp' ? 'image/webp' : 'image/jpeg');

    const drawable = await loadDrawableImage(file);

    try {
        const { source, width, height } = drawable;

        // Jika ukuran asli <= 2 MB, jangan kompres dan jangan ubah file/ekstensi.
        if (file.size <= maxBytes) {
            return {
                file,
                originalSize: file.size,
                compressedSize: file.size,
                savedPercent: 0,
                wasCompressed: false,
                convertedFormat: false,
                originalDimensions: { width, height },
                finalDimensions: { width, height },
                mimeType: detectedMime,
                status: 'unchanged',
                statusText: `Tidak perlu dikompres (<= ${Math.round(maxBytes / (1024 * 1024))} MB)`,
            };
        }

        const targetBytes = maxBytes === MAX_IMAGE_BYTES ? TARGET_IMAGE_BYTES : Math.floor(maxBytes * 0.94);
        let result = null;

        if (detectedMime === 'image/jpeg' || detectedMime === 'image/webp') {
            result = await searchBestLossyBlob({
                source,
                width,
                height,
                targetMime: detectedMime,
                maxBytes,
                targetBytes,
                initialQualities: [0.88, 0.82, 0.76, 0.70, 0.64],
            });
        } else if (detectedMime === 'image/png') {
            // Coba pertahankan format PNG pada resolusi wajar terlebih dahulu.
            const pngScales = file.size <= maxBytes * 1.8 ? [1.0, 0.85, 0.72] : [0.78];
            for (const scale of pngScales) {
                const tw = Math.max(1, Math.round(width * scale));
                const th = Math.max(1, Math.round(height * scale));
                const pngBlob = await encodeCanvas(source, tw, th, 'image/png', 1);
                if (pngBlob && pngBlob.size <= maxBytes) {
                    result = { blob: pngBlob, finalWidth: tw, finalHeight: th };
                    break;
                }
            }

            // Jika PNG tetap > 2 MB: konversi ke WebP (atau JPEG bila tidak membutuhkan transparansi).
            if (!result) {
                const needsAlpha = detectAlphaTransparency(source, width, height);

                result = await searchBestLossyBlob({
                    source,
                    width,
                    height,
                    targetMime: 'image/webp',
                    maxBytes,
                    targetBytes,
                    initialQualities: [0.88, 0.82, 0.76, 0.70, 0.64],
                });

                if (!result && !needsAlpha) {
                    result = await searchBestLossyBlob({
                        source,
                        width,
                        height,
                        targetMime: 'image/jpeg',
                        maxBytes,
                        targetBytes,
                        initialQualities: [0.88, 0.82, 0.76, 0.70, 0.64],
                    });
                }

                if (!result && needsAlpha) {
                    for (const scale of [0.60, 0.50, 0.40, 0.30, 0.22]) {
                        const tw = Math.max(1, Math.round(width * scale));
                        const th = Math.max(1, Math.round(height * scale));
                        const pngBlob = await encodeCanvas(source, tw, th, 'image/png', 1);
                        if (pngBlob && pngBlob.size <= maxBytes) {
                            result = { blob: pngBlob, finalWidth: tw, finalHeight: th };
                            break;
                        }
                    }
                }
            }
        }

        if (!result || !result.blob || result.blob.size > maxBytes) {
            throw new Error(
                `Kompresi gagal menurunkan ukuran di bawah ${Math.round(maxBytes / (1024 * 1024))} MB. Silakan gunakan gambar dengan dimensi lebih kecil.`,
            );
        }

        // Pastikan hasil kompresi benar-benar dapat dibuka kembali oleh browser.
        const verifyDrawable = await loadDrawableImage(result.blob);
        verifyDrawable.release();

        const finalExtension = extensionForMime(result.blob.type, originalExt);
        const convertedFormat = result.blob.type !== detectedMime;
        const outputFileName = convertedFormat
            ? replaceFileExtension(file.name, finalExtension)
            : file.name;

        const compressedFile = new File([result.blob], outputFileName, {
            type: result.blob.type,
            lastModified: Date.now(),
        });

        const savedPercent = file.size > 0
            ? Number(Math.max(0, ((file.size - compressedFile.size) / file.size) * 100).toFixed(1))
            : 0;

        return {
            file: compressedFile,
            originalSize: file.size,
            compressedSize: compressedFile.size,
            savedPercent,
            wasCompressed: true,
            convertedFormat,
            originalDimensions: { width, height },
            finalDimensions: { width: result.finalWidth, height: result.finalHeight },
            mimeType: compressedFile.type,
            status: 'compressed',
            statusText: convertedFormat
                ? `Berhasil dikompres & dikonversi ke ${finalExtension.toUpperCase()}`
                : 'Berhasil dikompres otomatis',
        };
    } finally {
        drawable.release();
    }
}
