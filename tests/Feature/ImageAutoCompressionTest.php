<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Produk;
use App\Models\User;
use App\Services\ImageCompressionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ImageAutoCompressionTest extends TestCase
{
    use RefreshDatabase;

    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'toko_img_tests_'.uniqid();
        if (! is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            foreach (glob($this->tempDir.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($this->tempDir);
        }

        parent::tearDown();
    }

    public function test_image_at_or_below_500kb_is_not_compressed_and_keeps_extension(): void
    {
        Storage::fake('public');

        $kasir = User::factory()->kasir()->create();
        $sourcePath = $this->createPngWithExactMinimumBytes('small_500kb.png', 450, 300, 500 * 1024, false);
        $originalBytes = file_get_contents($sourcePath);
        $this->assertGreaterThanOrEqual(490 * 1024, strlen($originalBytes));
        $this->assertLessThanOrEqual(520 * 1024, strlen($originalBytes));

        $upload = new UploadedFile($sourcePath, 'small-product.png', 'image/png', null, true);

        $this->actingAs($kasir)
            ->post(route('produk.store'), [
                'nama_produk' => 'Produk Gambar 500KB',
                'harga' => 25000,
                'stok' => 10,
                'gambar' => $upload,
            ])
            ->assertRedirect(route('produk.index'));

        $produk = Produk::query()->where('nama_produk', 'Produk Gambar 500KB')->firstOrFail();

        $this->assertNotNull($produk->gambar);
        $this->assertStringEndsWith('.png', $produk->gambar);
        Storage::disk('public')->assertExists($produk->gambar);

        $storedBytes = Storage::disk('public')->get($produk->gambar);
        $this->assertSame($originalBytes, $storedBytes, 'File <= 2 MB tidak boleh dikompres ulang atau diubah ekstensinya.');
    }

    public function test_3mb_5mb_and_10mb_jpegs_are_auto_compressed_below_2mb_preserving_aspect_ratio_and_extension(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension required.');
        }

        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $cases = [
            ['label' => '3MB', 'minBytes' => 3 * 1024 * 1024, 'width' => 2400, 'height' => 1600, 'ext' => 'jpg'],
            ['label' => '5MB', 'minBytes' => 5 * 1024 * 1024, 'width' => 3000, 'height' => 2000, 'ext' => 'jpeg'],
            ['label' => '10MB', 'minBytes' => 10 * 1024 * 1024, 'width' => 3600, 'height' => 2400, 'ext' => 'jpg'],
        ];

        foreach ($cases as $idx => $case) {
            $filePath = $this->createLargeNoisyJpeg(
                "case_{$case['label']}.{$case['ext']}",
                $case['width'],
                $case['height'],
                $case['minBytes']
            );

            $rawSize = filesize($filePath);
            $this->assertGreaterThanOrEqual($case['minBytes'], $rawSize, "Fixture {$case['label']} harus >= {$case['minBytes']} bytes");

            $upload = new UploadedFile($filePath, "foto-{$case['label']}.{$case['ext']}", 'image/jpeg', null, true);

            $this->actingAs($admin)
                ->post(route('produk.store'), [
                    'nama_produk' => "Produk Uji {$case['label']}",
                    'harga' => 50000 + ($idx * 1000),
                    'stok' => 15,
                    'gambar' => $upload,
                ])
                ->assertRedirect(route('produk.index'));

            $produk = Produk::query()->where('nama_produk', "Produk Uji {$case['label']}")->firstOrFail();
            $this->assertNotNull($produk->gambar);
            $this->assertStringEndsWith(".{$case['ext']}", $produk->gambar);
            Storage::disk('public')->assertExists($produk->gambar);

            $storedBytes = Storage::disk('public')->get($produk->gambar);
            $storedSize = strlen($storedBytes);
            $this->assertGreaterThan(0, $storedSize);
            $this->assertLessThanOrEqual(2 * 1024 * 1024, $storedSize, "Hasil kompresi {$case['label']} melebihi 2 MB.");

            $info = getimagesizefromstring($storedBytes);
            $this->assertIsArray($info, "Hasil kompresi {$case['label']} harus dapat dibuka sebagai gambar valid.");
            $expectedRatio = $case['width'] / $case['height'];
            $actualRatio = $info[0] / $info[1];
            $this->assertEqualsWithDelta($expectedRatio, $actualRatio, 0.02, "Rasio aspek gambar {$case['label']} berubah.");
        }
    }

    public function test_large_opaque_and_transparent_pngs_over_2mb_compress_safely_within_2mb(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension required.');
        }

        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        // 1. Opaque PNG > 3.2 MB -> dikonversi secara sah jika masih > 2 MB, hasil <= 2 MB & rasio terjaga
        $opaquePath = $this->createLargeNoisyPng('opaque_3mb.png', 1300, 910, 3_200_000, false);
        $this->assertGreaterThan(2 * 1024 * 1024, filesize($opaquePath));

        $this->actingAs($admin)
            ->post(route('produk.store'), [
                'nama_produk' => 'Produk PNG Opaque Besar',
                'harga' => 30000,
                'stok' => 8,
                'gambar' => new UploadedFile($opaquePath, 'opaque.png', 'image/png', null, true),
            ])
            ->assertRedirect(route('produk.index'));

        $opaqueProduct = Produk::query()->where('nama_produk', 'Produk PNG Opaque Besar')->firstOrFail();
        $opaqueBinary = Storage::disk('public')->get($opaqueProduct->gambar);
        $this->assertLessThanOrEqual(2 * 1024 * 1024, strlen($opaqueBinary));
        $opaqueInfo = getimagesizefromstring($opaqueBinary);
        $this->assertIsArray($opaqueInfo);
        $this->assertEqualsWithDelta(1300 / 910, $opaqueInfo[0] / $opaqueInfo[1], 0.02);

        // 2. Transparent PNG > 3.2 MB pada upload Kategori -> tetap valid, <= 2 MB, mempertahankan format alpha (.webp/.png)
        $alphaPath = $this->createLargeNoisyPng('alpha_3mb.png', 1000, 1000, 3_200_000, true);
        $this->assertGreaterThan(2 * 1024 * 1024, filesize($alphaPath));

        $this->actingAs($admin)
            ->post(route('kategori.store'), [
                'nama_kategori' => 'Kategori PNG Transparan',
                'gambar' => new UploadedFile($alphaPath, 'alpha.png', 'image/png', null, true),
                'is_active' => true,
            ])
            ->assertRedirect(route('kategori.index'));

        $kategori = Kategori::query()->where('nama_kategori', 'Kategori PNG Transparan')->firstOrFail();
        $this->assertNotNull($kategori->gambar);
        $this->assertMatchesRegularExpression('/\.(webp|png)$/', $kategori->gambar);
        $alphaBinary = Storage::disk('public')->get($kategori->gambar);
        $this->assertLessThanOrEqual(2 * 1024 * 1024, strlen($alphaBinary));
        $this->assertIsArray(getimagesizefromstring($alphaBinary));
    }

    public function test_backend_rejects_invalid_mime_extension_corrupt_image_and_preserves_original_on_compression_failure(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        // 1. Spoofed extension / invalid MIME rejected at backend
        $fakeExe = UploadedFile::fake()->createWithContent('payload.exe', 'MZ-not-an-image');
        $this->actingAs($admin)
            ->post(route('produk.store'), [
                'nama_produk' => 'Produk Invalid EXE',
                'harga' => 10000,
                'stok' => 5,
                'gambar' => $fakeExe,
            ])
            ->assertSessionHasErrors('gambar');

        // 2. Text file renamed to .jpg rejected by backend image/MIME verification
        $fakeRenamed = UploadedFile::fake()->createWithContent('fake-image.jpg', str_repeat('NOT_A_REAL_JPEG_CONTENT', 500));
        $this->actingAs($admin)
            ->post(route('produk.store'), [
                'nama_produk' => 'Produk Fake JPG',
                'harga' => 10000,
                'stok' => 5,
                'gambar' => $fakeRenamed,
            ])
            ->assertSessionHasErrors('gambar');

        // 3. Jika update gambar gagal dikompres / hasil kompresi > 2 MB, error ditampilkan dan gambar lama TETAP AMAN
        $existingPath = $this->createPngWithExactMinimumBytes('existing.png', 400, 400, 100 * 1024, false);
        $this->actingAs($admin)
            ->post(route('produk.store'), [
                'nama_produk' => 'Produk Update Gagal Kompres',
                'harga' => 45000,
                'stok' => 7,
                'gambar' => new UploadedFile($existingPath, 'existing.png', 'image/png', null, true),
            ])
            ->assertRedirect(route('produk.index'));

        $produk = Produk::query()->where('nama_produk', 'Produk Update Gagal Kompres')->firstOrFail();
        $oldImagePath = $produk->gambar;
        Storage::disk('public')->assertExists($oldImagePath);

        $this->app->bind(ImageCompressionService::class, function () {
            return new class extends ImageCompressionService
            {
                public function processAndStore(UploadedFile $file, string $directory): array
                {
                    throw ValidationException::withMessages([
                        'gambar' => 'Hasil kompresi gambar masih melebihi batas maksimal 2 MB. Gunakan gambar dengan resolusi lebih kecil.',
                    ]);
                }
            };
        });

        $validReplacement = UploadedFile::fake()->image('replacement.jpg', 800, 800);
        $this->actingAs($admin)
            ->put(route('produk.update', $produk), [
                'nama_produk' => 'Produk Update Gagal Kompres',
                'harga' => 45000,
                'stok' => 7,
                'gambar' => $validReplacement,
            ])
            ->assertSessionHasErrors('gambar');

        // Gambar asli sebelum pembaruan tidak boleh terhapus ketika kompresi baru gagal.
        Storage::disk('public')->assertExists($oldImagePath);
        $this->assertSame($oldImagePath, $produk->fresh()->gambar);
    }

    /**
     * Membuat PNG standar valid menggunakan chunk zlib uncompressed / raw PNG scanlines
     * sehingga ukuran byte terkontrol presisi dan 100% dapat dibuka GD & browser.
     */
    private function createPngWithExactMinimumBytes(string $filename, int $width, int $height, int $minBytes, bool $withAlpha): string
    {
        $path = $this->tempDir.DIRECTORY_SEPARATOR.$filename;
        $img = imagecreatetruecolor($width, $height);
        if ($withAlpha) {
            imagealphablending($img, false);
            imagesavealpha($img, true);
            $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
            imagefilledrectangle($img, 0, 0, $width, $height, $transparent);
        }

        // Isi pola deterministik cepat untuk gambar <= 2 MB lalu simpan PNG tanpa kompresi atau padded comment chunk jika diperlukan.
        $c1 = $withAlpha ? imagecolorallocatealpha($img, 32, 120, 90, 20) : imagecolorallocate($img, 32, 120, 90);
        $c2 = $withAlpha ? imagecolorallocatealpha($img, 220, 190, 150, 0) : imagecolorallocate($img, 220, 190, 150);
        imagefilledrectangle($img, 0, 0, (int) ($width / 2), $height, $c1);
        imagefilledrectangle($img, (int) ($width / 2), 0, $width, $height, $c2);

        imagepng($img, $path, 0);
        imagedestroy($img);

        clearstatcache(true, $path);
        $currentSize = filesize($path);
        if ($currentSize < $minBytes) {
            // Sisipkan chunk tEXt standar PNG sebelum IEND agar file tetap 100% valid PNG dengan ukuran target tepat.
            $raw = file_get_contents($path);
            $iendPos = strrpos($raw, 'IEND');
            if ($iendPos !== false && $iendPos >= 4) {
                $padLen = max(16, $minBytes - $currentSize);
                $textData = 'AuditPad'."\0".str_repeat('X', $padLen);
                $chunkLen = pack('N', strlen($textData));
                $chunkType = 'tEXt';
                $crc = pack('N', crc32($chunkType.$textData));
                $beforeIend = substr($raw, 0, $iendPos - 4);
                $iendChunk = substr($raw, $iendPos - 4);
                file_put_contents($path, $beforeIend.$chunkLen.$chunkType.$textData.$crc.$iendChunk);
            }
        }

        return $path;
    }

    /**
     * Membuat PNG noisy acak sejati sehingga ukuran PNG > minBytes tidak hanya berasal dari metadata,
     * melainkan pixel nyata yang memaksa encoder PNG & WebP bekerja realistis.
     */
    private function createLargeNoisyPng(string $filename, int $width, int $height, int $minBytes, bool $withAlpha): string
    {
        $path = $this->tempDir.DIRECTORY_SEPARATOR.$filename;
        $img = imagecreatetruecolor($width, $height);
        if ($withAlpha) {
            imagealphablending($img, false);
            imagesavealpha($img, true);
        }

        // Buat ubin noise kecil lalu salin berkali-kali dengan pergeseran offset supaya sangat cepat (<50ms) dan berentropi tinggi.
        $tileW = 160;
        $tileH = 160;
        $tile = imagecreatetruecolor($tileW, $tileH);
        if ($withAlpha) {
            imagealphablending($tile, false);
            imagesavealpha($tile, true);
        }

        mt_srand(4242);
        for ($y = 0; $y < $tileH; $y++) {
            for ($x = 0; $x < $tileW; $x++) {
                $r = ($x * 7 + mt_rand(0, 90)) & 255;
                $g = ($y * 5 + mt_rand(0, 90)) & 255;
                $b = (($x + $y) * 3 + mt_rand(0, 90)) & 255;
                $a = $withAlpha && ($x + $y) % 5 === 0 ? 60 : 0;
                $color = $withAlpha
                    ? imagecolorallocatealpha($tile, $r, $g, $b, $a)
                    : imagecolorallocate($tile, $r, $g, $b);
                imagesetpixel($tile, $x, $y, $color);
            }
        }

        for ($ty = 0; $ty < $height; $ty += $tileH) {
            for ($tx = 0; $tx < $width; $tx += $tileW) {
                imagecopy($img, $tile, $tx, $ty, 0, 0, min($tileW, $width - $tx), min($tileH, $height - $ty));
            }
        }

        imagedestroy($tile);
        imagepng($img, $path, 0);
        imagedestroy($img);

        clearstatcache(true, $path);
        if (filesize($path) < $minBytes) {
            $raw = file_get_contents($path);
            $iendPos = strrpos($raw, 'IEND');
            if ($iendPos !== false && $iendPos >= 4) {
                $padLen = $minBytes - filesize($path) + 1024;
                $textData = 'AuditPad'."\0".str_repeat('Z', $padLen);
                $chunkLen = pack('N', strlen($textData));
                $chunkType = 'tEXt';
                $crc = pack('N', crc32($chunkType.$textData));
                file_put_contents($path, substr($raw, 0, $iendPos - 4).$chunkLen.$chunkType.$textData.$crc.substr($raw, $iendPos - 4));
            }
        }

        return $path;
    }

    /**
     * Membuat JPEG beresolusi besar dengan detail tinggi (noisy photo-like texture) agar file mentah >= 3/5/10 MB.
     */
    private function createLargeNoisyJpeg(string $filename, int $width, int $height, int $minBytes): string
    {
        $path = $this->tempDir.DIRECTORY_SEPARATOR.$filename;
        $img = imagecreatetruecolor($width, $height);

        $tileW = 180;
        $tileH = 180;
        $tile = imagecreatetruecolor($tileW, $tileH);
        mt_srand(9001);

        for ($y = 0; $y < $tileH; $y++) {
            for ($x = 0; $x < $tileW; $x++) {
                $r = mt_rand(20, 235);
                $g = mt_rand(20, 235);
                $b = mt_rand(20, 235);
                $col = ($r << 16) | ($g << 8) | $b;
                imagesetpixel($tile, $x, $y, $col);
            }
        }

        for ($ty = 0; $ty < $height; $ty += $tileH) {
            for ($tx = 0; $tx < $width; $tx += $tileW) {
                imagecopy($img, $tile, $tx, $ty, 0, 0, min($tileW, $width - $tx), min($tileH, $height - $ty));
            }
        }

        imagedestroy($tile);
        imagejpeg($img, $path, 100);
        imagedestroy($img);

        clearstatcache(true, $path);
        if (filesize($path) < $minBytes) {
            // Sisipkan JPEG COM marker standar jika hasil Q=100 sedikit di bawah target byte eksak.
            $raw = file_get_contents($path);
            $padNeeded = $minBytes - strlen($raw) + 4096;
            $blocks = '';
            while ($padNeeded > 0) {
                $chunk = min(65000, $padNeeded);
                $blocks .= "\xFF\xFE".pack('n', $chunk + 2).str_repeat('A', $chunk);
                $padNeeded -= $chunk;
            }
            if (substr($raw, 0, 2) === "\xFF\xD8") {
                file_put_contents($path, "\xFF\xD8".$blocks.substr($raw, 2));
            }
        }

        return $path;
    }
}
