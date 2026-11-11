<?php

namespace App\Services;

use Closure;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ImageCompressionService
{
    public const MAX_FILE_BYTES = 2 * 1024 * 1024; // 2 MB (2048 KB)

    public const TARGET_COMPRESSED_BYTES = 1_900_000; // Margin aman <= 2 MB (desimal maupun biner)

    public const MAX_RAW_UPLOAD_KB = 20480; // 20 MB batas mentah sebelum kompresi backend

    /**
     * @var array<int, string>
     */
    public const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /**
     * @var array<int, string>
     */
    public const ALLOWED_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'webp',
    ];

    /**
     * Aturan validasi bersama untuk seluruh endpoint upload gambar (Produk & Kategori).
     *
     * @return array<int, mixed>
     */
    public static function validationRules(): array
    {
        return [
            'nullable',
            'file',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'mimetypes:image/jpeg,image/png,image/webp',
            'max:'.self::MAX_RAW_UPLOAD_KB,
            function (string $attribute, mixed $value, Closure $fail): void {
                if (! $value instanceof UploadedFile) {
                    return;
                }

                $extension = strtolower($value->getClientOriginalExtension());
                if ($extension === '' || ! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                    $fail('Ekstensi file gambar harus jpg, jpeg, png, atau webp.');
                }
            },
        ];
    }

    public function store(UploadedFile $file, string $directory): string
    {
        return $this->processAndStore($file, $directory)['path'];
    }

    /**
     * Memeriksa ukuran file asli, validasi MIME/ekstensi, lalu menyimpan langsung
     * jika <= 2 MB atau menjalankan kompresi bertahap jika > 2 MB.
     *
     * @return array{
     *     path: string,
     *     original_size: int,
     *     final_size: int,
     *     compressed: bool,
     *     mime_type: string,
     *     extension: string
     * }
     */
    public function processAndStore(UploadedFile $file, string $directory): array
    {
        $realPath = $file->getRealPath();
        if (! $realPath || ! is_readable($realPath)) {
            throw ValidationException::withMessages([
                'gambar' => 'File gambar yang diunggah tidak dapat dibaca oleh server.',
            ]);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if ($extension === '' || ! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw ValidationException::withMessages([
                'gambar' => 'Ekstensi file gambar tidak didukung. Gunakan JPG, JPEG, PNG, atau WEBP.',
            ]);
        }

        $detectedMime = $this->detectMimeType($realPath, $file);
        if (! in_array($detectedMime, self::ALLOWED_MIMES, true)) {
            throw ValidationException::withMessages([
                'gambar' => 'Tipe MIME file tidak valid. Hanya gambar JPG, PNG, atau WEBP yang diizinkan.',
            ]);
        }

        $imageInfo = @getimagesize($realPath);
        if (! is_array($imageInfo) || ($imageInfo[0] ?? 0) < 1 || ($imageInfo[1] ?? 0) < 1) {
            throw ValidationException::withMessages([
                'gambar' => 'File gambar rusak atau tidak valid.',
            ]);
        }

        $originalSize = (int) ($file->getSize() ?: filesize($realPath) ?: 0);
        if ($originalSize < 1) {
            throw ValidationException::withMessages([
                'gambar' => 'Ukuran file gambar tidak valid.',
            ]);
        }

        // File <= 2 MB tidak perlu dikompres dan mempertahankan ekstensi aslinya.
        if ($originalSize <= self::MAX_FILE_BYTES) {
            $path = $this->generateUniquePath($directory, $extension);
            $rawBytes = @file_get_contents($realPath);

            if (! is_string($rawBytes) || $rawBytes === '') {
                throw ValidationException::withMessages([
                    'gambar' => 'Gagal membaca data gambar untuk disimpan.',
                ]);
            }

            if (! Storage::disk('public')->put($path, $rawBytes)) {
                throw ValidationException::withMessages([
                    'gambar' => 'Gagal menyimpan file gambar ke penyimpanan.',
                ]);
            }

            return [
                'path' => $path,
                'original_size' => $originalSize,
                'final_size' => $originalSize,
                'compressed' => false,
                'mime_type' => $detectedMime,
                'extension' => $extension,
            ];
        }

        // File > 2 MB wajib dikompres secara bertahap di backend hingga <= 2 MB.
        return $this->compressLargeImage($realPath, $detectedMime, $extension, $directory, $originalSize);
    }

    /**
     * @return array{
     *     path: string,
     *     original_size: int,
     *     final_size: int,
     *     compressed: bool,
     *     mime_type: string,
     *     extension: string
     * }
     */
    private function compressLargeImage(
        string $realPath,
        string $sourceMime,
        string $sourceExtension,
        string $directory,
        int $originalSize,
    ): array {
        if (! extension_loaded('gd')) {
            throw ValidationException::withMessages([
                'gambar' => 'Ekstensi GD server tidak tersedia untuk mengompres gambar di atas 2 MB.',
            ]);
        }

        $source = $this->loadGdImage($realPath, $sourceMime);
        if (! $source) {
            throw ValidationException::withMessages([
                'gambar' => 'Gambar gagal didekode untuk proses kompresi otomatis.',
            ]);
        }

        $tempVerifyPath = null;

        try {
            $source = $this->applyExifOrientation($source, $realPath, $sourceMime);

            if (! imageistruecolor($source)) {
                imagepalettetotruecolor($source);
            }

            imagealphablending($source, false);
            imagesavealpha($source, true);

            $encodedResult = $this->encodeProgressively($source, $sourceMime, $sourceExtension);

            if (! $encodedResult || $encodedResult['size'] > self::MAX_FILE_BYTES) {
                throw ValidationException::withMessages([
                    'gambar' => 'Hasil kompresi gambar masih melebihi batas maksimal 2 MB. Gunakan gambar dengan resolusi lebih kecil.',
                ]);
            }

            // Validasi hasil kompresi melalui file sementara sebelum disimpan permanen.
            $tempBase = @tempnam(sys_get_temp_dir(), 'toko_cmp_');
            if ($tempBase === false) {
                throw ValidationException::withMessages([
                    'gambar' => 'Gagal membuat berkas sementara saat verifikasi kompresi gambar.',
                ]);
            }
            $tempVerifyPath = $tempBase;

            if (@file_put_contents($tempVerifyPath, $encodedResult['binary']) === false) {
                throw ValidationException::withMessages([
                    'gambar' => 'Gagal menulis berkas sementara hasil kompresi gambar.',
                ]);
            }

            clearstatcache(true, $tempVerifyPath);
            $verifiedSize = @filesize($tempVerifyPath);
            $verifiedInfo = @getimagesize($tempVerifyPath);

            if (! is_int($verifiedSize) || $verifiedSize < 1 || $verifiedSize > self::MAX_FILE_BYTES || ! is_array($verifiedInfo) || ($verifiedInfo[0] ?? 0) < 1) {
                throw ValidationException::withMessages([
                    'gambar' => 'File hasil kompresi tidak valid atau masih melebihi batas maksimal 2 MB.',
                ]);
            }

            $finalPath = $this->generateUniquePath($directory, $encodedResult['extension']);
            if (! Storage::disk('public')->put($finalPath, $encodedResult['binary'])) {
                throw ValidationException::withMessages([
                    'gambar' => 'Gagal menyimpan gambar hasil kompresi.',
                ]);
            }

            return [
                'path' => $finalPath,
                'original_size' => $originalSize,
                'final_size' => $verifiedSize,
                'compressed' => true,
                'mime_type' => $encodedResult['mime'],
                'extension' => $encodedResult['extension'],
            ];
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'gambar' => 'Terjadi kesalahan saat mengompres gambar secara otomatis.',
            ]);
        } finally {
            imagedestroy($source);
            if ($tempVerifyPath && file_exists($tempVerifyPath)) {
                @unlink($tempVerifyPath);
            }
        }
    }

    /**
     * Strategi kompresi bertahap:
     * 1. Turunkan kualitas secara bertahap pada resolusi awal (mempertahankan ketajaman).
     * 2. Jika masih > 2 MB, turunkan resolusi secara proporsional (menjaga rasio aspek) + kualitas bertahap.
     * 3. Khusus PNG yang masih > 2 MB: cek transparansi; konversi ke WEBP (atau JPEG bila tanpa transparansi).
     *
     * @return array{binary: string, size: int, mime: string, extension: string}|null
     */
    private function encodeProgressively(GdImage $source, string $sourceMime, string $sourceExtension): ?array
    {
        $srcWidth = imagesx($source);
        $srcHeight = imagesy($source);

        if ($sourceMime === 'image/jpeg') {
            $outExt = in_array($sourceExtension, ['jpg', 'jpeg'], true) ? $sourceExtension : 'jpg';

            return $this->searchBestLossyCandidate($source, $srcWidth, $srcHeight, 'image/jpeg', $outExt, [88, 82, 76, 70, 64]);
        }

        if ($sourceMime === 'image/webp') {
            if (! function_exists('imagewebp')) {
                return null;
            }

            return $this->searchBestLossyCandidate($source, $srcWidth, $srcHeight, 'image/webp', 'webp', [88, 82, 76, 70, 64]);
        }

        // Khusus PNG: coba pertahankan PNG terlebih dahulu pada skala wajar agar ekstensi tidak berubah bila masih memungkinkan.
        $pngScales = [1.0, 0.85, 0.72];
        foreach ($pngScales as $scale) {
            $candidate = $this->encodeAtScale($source, $srcWidth, $srcHeight, $scale, 'image/png', 9);
            if ($candidate !== null && strlen($candidate) <= self::MAX_FILE_BYTES) {
                return [
                    'binary' => $candidate,
                    'size' => strlen($candidate),
                    'mime' => 'image/png',
                    'extension' => 'png',
                ];
            }
        }

        $needsAlpha = $this->hasAlphaTransparency($source, $srcWidth, $srcHeight);

        if ($needsAlpha) {
            if (function_exists('imagewebp')) {
                $webpAttempt = $this->searchBestLossyCandidate($source, $srcWidth, $srcHeight, 'image/webp', 'webp', [88, 82, 76, 70, 62]);
                if ($webpAttempt !== null) {
                    return $webpAttempt;
                }
            }

            // Fallback transparan PNG bila WebP tidak tersedia: turunkan skala proporsional lebih lanjut.
            foreach ([0.60, 0.50, 0.40, 0.30, 0.22] as $scale) {
                $candidate = $this->encodeAtScale($source, $srcWidth, $srcHeight, $scale, 'image/png', 9);
                if ($candidate !== null && strlen($candidate) <= self::MAX_FILE_BYTES) {
                    return [
                        'binary' => $candidate,
                        'size' => strlen($candidate),
                        'mime' => 'image/png',
                        'extension' => 'png',
                    ];
                }
            }

            return null;
        }

        // PNG tanpa kebutuhan transparansi yang masih > 2 MB: konversi ke WebP (atau JPEG sebagai fallback).
        if (function_exists('imagewebp')) {
            $webpAttempt = $this->searchBestLossyCandidate($source, $srcWidth, $srcHeight, 'image/webp', 'webp', [88, 82, 76, 70, 64]);
            if ($webpAttempt !== null) {
                return $webpAttempt;
            }
        }

        return $this->searchBestLossyCandidate($source, $srcWidth, $srcHeight, 'image/jpeg', 'jpg', [88, 82, 76, 70, 64]);
    }

    /**
     * @param  array<int, int>  $initialQualities
     * @return array{binary: string, size: int, mime: string, extension: string}|null
     */
    private function searchBestLossyCandidate(
        GdImage $source,
        int $srcWidth,
        int $srcHeight,
        string $targetMime,
        string $targetExtension,
        array $initialQualities,
    ): ?array {
        $bestUnderMax = null;

        // Fase 1: Kompres kualitas bertahap pada resolusi asli.
        foreach ($initialQualities as $quality) {
            $binary = $this->encodeAtScale($source, $srcWidth, $srcHeight, 1.0, $targetMime, $quality);
            if ($binary === null) {
                continue;
            }

            $len = strlen($binary);
            if ($len <= self::TARGET_COMPRESSED_BYTES) {
                return [
                    'binary' => $binary,
                    'size' => $len,
                    'mime' => $targetMime,
                    'extension' => $targetExtension,
                ];
            }

            if ($len <= self::MAX_FILE_BYTES && ($bestUnderMax === null || $len < $bestUnderMax['size'])) {
                $bestUnderMax = [
                    'binary' => $binary,
                    'size' => $len,
                    'mime' => $targetMime,
                    'extension' => $targetExtension,
                ];
            }
        }

        if ($bestUnderMax !== null) {
            return $bestUnderMax;
        }

        // Fase 2: Turunkan resolusi secara proporsional (menjaga rasio aspek) lalu kualitas bertahap.
        $scales = [0.85, 0.72, 0.60, 0.48, 0.38, 0.30, 0.22, 0.16];
        $scaledQualities = [84, 76, 68, 58, 48];

        foreach ($scales as $scale) {
            foreach ($scaledQualities as $quality) {
                $binary = $this->encodeAtScale($source, $srcWidth, $srcHeight, $scale, $targetMime, $quality);
                if ($binary === null) {
                    continue;
                }

                $len = strlen($binary);
                if ($len <= self::TARGET_COMPRESSED_BYTES) {
                    return [
                        'binary' => $binary,
                        'size' => $len,
                        'mime' => $targetMime,
                        'extension' => $targetExtension,
                    ];
                }

                if ($len <= self::MAX_FILE_BYTES && ($bestUnderMax === null || $len < $bestUnderMax['size'])) {
                    $bestUnderMax = [
                        'binary' => $binary,
                        'size' => $len,
                        'mime' => $targetMime,
                        'extension' => $targetExtension,
                    ];
                }
            }

            if ($bestUnderMax !== null) {
                return $bestUnderMax;
            }
        }

        return $bestUnderMax;
    }

    private function encodeAtScale(
        GdImage $source,
        int $srcWidth,
        int $srcHeight,
        float $scale,
        string $mime,
        int $quality,
    ): ?string {
        $targetWidth = max(1, (int) round($srcWidth * $scale));
        $targetHeight = max(1, (int) round($srcHeight * $scale));

        $needsNewCanvas = $scale < 0.999 || $mime === 'image/jpeg';
        $canvas = $source;
        $ownsCanvas = false;

        if ($needsNewCanvas) {
            $resized = imagecreatetruecolor($targetWidth, $targetHeight);
            if ($resized === false) {
                return null;
            }

            $ownsCanvas = true;
            $canvas = $resized;

            if ($mime === 'image/jpeg') {
                $white = imagecolorallocate($canvas, 255, 255, 255);
                imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $white);
                imagealphablending($canvas, true);
            } else {
                imagealphablending($canvas, false);
                imagesavealpha($canvas, true);
                $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
                imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $transparent);
            }

            imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $srcWidth, $srcHeight);
        }

        ob_start();
        $ok = match ($mime) {
            'image/jpeg' => imagejpeg($canvas, null, $quality),
            'image/webp' => function_exists('imagewebp') && imagewebp($canvas, null, $quality),
            'image/png' => imagepng($canvas, null, min(9, max(0, $quality))),
            default => false,
        };
        $binary = ob_get_clean();

        if ($ownsCanvas) {
            imagedestroy($canvas);
        }

        if (! $ok || ! is_string($binary) || $binary === '') {
            return null;
        }

        return $binary;
    }

    private function hasAlphaTransparency(GdImage $image, int $width, int $height): bool
    {
        $stepX = max(1, (int) floor($width / 120));
        $stepY = max(1, (int) floor($height / 120));

        for ($y = 0; $y < $height; $y += $stepY) {
            for ($x = 0; $x < $width; $x += $stepX) {
                $rgba = imagecolorat($image, $x, $y);
                $alpha = ($rgba >> 24) & 0x7F;
                if ($alpha > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    private function loadGdImage(string $realPath, string $mime): ?GdImage
    {
        $img = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($realPath),
            'image/png' => @imagecreatefrompng($realPath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($realPath) : false,
            default => false,
        };

        if ($img instanceof GdImage) {
            return $img;
        }

        $raw = @file_get_contents($realPath);
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $fallback = @imagecreatefromstring($raw);

        return $fallback instanceof GdImage ? $fallback : null;
    }

    private function applyExifOrientation(GdImage $source, string $realPath, string $mime): GdImage
    {
        if ($mime !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $source;
        }

        $exif = @exif_read_data($realPath);
        $orientation = (int) ($exif['Orientation'] ?? 1);

        $rotated = match ($orientation) {
            3 => imagerotate($source, 180, 0),
            6 => imagerotate($source, -90, 0),
            8 => imagerotate($source, 90, 0),
            default => false,
        };

        if ($rotated instanceof GdImage) {
            imagedestroy($source);

            return $rotated;
        }

        return $source;
    }

    private function detectMimeType(string $realPath, UploadedFile $file): string
    {
        if (function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = @finfo_file($finfo, $realPath);
                finfo_close($finfo);
                if (is_string($mime) && $mime !== '') {
                    return $mime;
                }
            }
        }

        return (string) ($file->getMimeType() ?: $file->getClientMimeType());
    }

    private function generateUniquePath(string $directory, string $extension): string
    {
        $cleanDir = trim($directory, '/');

        do {
            $path = $cleanDir.'/'.Str::uuid()->toString().'.'.$extension;
        } while (Storage::disk('public')->exists($path));

        return $path;
    }
}
