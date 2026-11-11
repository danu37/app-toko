# TokoKita — Aplikasi Toko & Point of Sales (POS)

Aplikasi web manajemen toko, storefront katalog publik, sistem kasir (*Point of Sales*), cetak nota thermal (`40 mm`, `58 mm`, `80 mm`), serta laporan penjualan dan manajemen peran berbasis **Laravel 12**, **Vue 3** (`<script setup>`), **Inertia.js 2**, **Tailwind CSS**, dan **MariaDB / MySQL**.

---

## Ringkasan Fitur Utama

1. **Storefront Publik Minimalis (`/`, `/shop`, `/shop/{slug}`)**
   - Halaman beranda bergaya editorial dengan hero promosi, daftar kategori unggulan (*Shop by Categories*), produk terlaris (*Our Bestsellers*), banner promo, pencarian cepat, dan detail produk beserta informasi ketersediaan stok.
2. **Autentikasi & Otorisasi Berbasis Role (`admin` & `kasir`)**
   - Pemisahan hak akses berlapis di backend melalui middleware `EnsureUserHasRole`, Laravel Policy & Gate, serta otorisasi Form Request.
   - Registrasi mandiri publik dinonaktifkan; pembuatan akun dan penetapan role hanya dapat dilakukan oleh Administrator.
   - Halaman galat terstruktur ([`resources/js/Pages/Error.vue`](resources/js/Pages/Error.vue)) untuk status `403`, `404`, `500`, dan `503`, serta penanganan kedaluwarsa CSRF (`419`).
3. **Dashboard Admin & Dashboard Operasional Kasir**
   - **Admin**: Ringkasan total produk, kategori, pelanggan, transaksi, pendapatan, grafik tren penjualan harian/bulanan, produk terlaris, ringkasan stok menipis, dan transaksi terbaru.
   - **Kasir**: Ringkasan transaksi hari ini, pelanggan dilayani hari ini, omzet harian, serta pintasan cepat menuju transaksi baru, tambah produk, dan tambah pelanggan.
4. **Manajemen Master Data (`Produk`, `Kategori`, `Pelanggan`, `Cabang`, `Pengguna`)**
   - **Produk**: Pencarian, filter kategori/status, detail, soft delete aman bagi riwayat transaksi, serta fitur **Auto File Compression (maksimal 2 MB)** pada form Produk dan Kategori — file `<= 2 MB` disimpan apa adanya tanpa kompresi ulang, sedangkan gambar `> 2 MB` (JPG/JPEG, PNG, WEBP) dikompres otomatis secara bertahap (kualitas lalu resolusi proporsional menjaga rasio aspek, termasuk penanganan transparansi PNG) beserta tampilan ukuran *Original*, *Compressed*, *Saved (%)*, status, indikator loading, pratinjau, dan validasi ulang backend via `gd`/`exif`.
   - **Pelanggan**: Pencarian, filter kota, riwayat transaksi pelanggan, dan proteksi duplikasi nomor HP di backend.
   - **Kategori, Cabang, & Pengguna**: Pengelolaan master kategori/cabang serta CRUD akun pengguna dan perubahan role khusus Admin.
5. **Sistem Transaksi POS (`/transaksi/create`)**
   - Pemilihan pelanggan dan cabang, pencarian/klik kartu produk cepat, pengaturan qty dengan batas stok real-time, pemilihan metode pembayaran (`tunai`, `qris`, `transfer`, `debit`), perhitungan otomatis kembalian/kurang bayar, serta pemrosesan atomik `DB::transaction` dengan penguncian stok `lockForUpdate()`.
   - Harga satuan, subtotal, total akhir, dan kembalian **dihitung ulang secara otoritatif di server**; nominal dari browser tidak pernah dipercaya langsung.
6. **Cetak Nota Thermal (`/transaksi/{id}/nota`)**
   - Mendukung ukuran kertas **58 mm (Standar)**, **80 mm (Lebar)**, dan opsi efektif **40 mm (Mini)** dengan `@page` dinamis dan `@media print` yang menyembunyikan seluruh navigasi/toolbar aplikasi.
   - Layout monospasi anti-potong (*wrap* aman untuk nama produk/pelanggan/kasir yang panjang).
7. **Laporan & Rekap Penjualan (`/laporan`, `/laporan/*`, `/laporan/export`)**
   - Laporan ringkasan penjualan, rekap harian, rekap metode pembayaran, rekap per cabang, rekap per kasir, produk terlaris, performa belanja pelanggan, filter rentang tanggal/cabang/pelanggan/kasir, dan ekspor **CSV** yang aman dari formula injection.

---

## Arsitektur & Teknologi

| Lapisan | Teknologi |
| :--- | :--- |
| Backend | PHP ^8.2, Laravel 12, Sanctum, Ziggy |
| Frontend | Vue 3 (Composition API `<script setup>`), Inertia.js 2, Tailwind CSS v3, Vite 7 |
| Database | MariaDB / MySQL (`db-toko` + tabel/view/trigger legacy dipertahankan) |
| Testing & Audit | PHPUnit 11, Laravel Pint, Vite Production Build |

Struktur kode utama:
- Backend: [`app/Http/Controllers/`](app/Http/Controllers), [`app/Services/`](app/Services), [`app/Policies/`](app/Policies), [`app/Http/Requests/`](app/Http/Requests), [`app/Http/Resources/`](app/Http/Resources), [`app/Models/`](app/Models)
- Frontend: [`resources/js/Layouts/`](resources/js/Layouts), [`resources/js/Components/`](resources/js/Components), [`resources/js/Pages/`](resources/js/Pages), [`resources/js/Composables/`](resources/js/Composables)
- Database & Konfigurasi: [`database/migrations/`](database/migrations), [`database/seeders/`](database/seeders), [`config/store.php`](config/store.php)

---

## Panduan Instalasi

### Prasyarat Sistem
- **PHP >= 8.2** dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, dan disarankan **`gd`** (dengan dukungan WebP untuk optimasi unggahan foto produk) serta `exif`.
- **Composer**
- **Node.js >= 20** dan **NPM**
- **MariaDB / MySQL** (misalnya melalui XAMPP atau Laragon)

### Langkah Instalasi

1. **Clone atau masuk ke direktori proyek**
   ```bash
   cd /path/to/app-toko
   ```
2. **Instal dependensi PHP dan JavaScript**
   ```bash
   composer install
   npm install
   ```
3. **Siapkan file environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
4. **Buat symbolic link penyimpanan gambar publik**
   ```bash
   php artisan storage:link
   ```

---

## Panduan Konfigurasi Database

1. **Siapkan database MariaDB/MySQL**
   - Apabila Anda sudah memiliki database awal `db-toko` (beserta tabel legacy `tbl_cabang`, `tbl_pelanggan`, `tbl_transaksi`, `tbl_detail_transaksi`, view, procedure, function, dan trigger), pertahankan database tersebut dan pastikan nama databasenya cocok pada `.env`.
   - Jika memulai dari database kosong, buat database baru terlebih dahulu (contoh: `db-toko`).
2. **Sesuaikan konfigurasi `.env`**
   ```dotenv
   APP_NAME="TokoKita"
   APP_URL=http://127.0.0.1:8000
   APP_TIMEZONE=Asia/Jakarta

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=db-toko
   DB_USERNAME=root
   DB_PASSWORD=
   ```
3. **Konfigurasi identitas toko & header nota (opsional di `.env`)**
   ```dotenv
   STORE_NAME="TokoKita"
   STORE_TAGLINE="Layanan Kasir & Kebutuhan Harian"
   STORE_ADDRESS="Jl. Melati No. 12, Jakarta"
   STORE_PHONE="021-555-0199"
   STORE_HOURS="Buka Setiap Hari 08.00 - 21.00 WIB"
   STORE_LOGO_URL=
   ```
4. **Jalankan migration (aman untuk database `db-toko` yang sudah berisi data)**
   ```bash
   php artisan migrate
   ```
   > Seluruh migration tambahan (`2026_11_11_*`) dirancang **non-destruktif** dan menjaga seluruh data transaksi serta objek SQL legacy tetap utuh.
5. **Seeding awal (hanya untuk environment `local` / `testing`)**
   ```bash
   php artisan db:seed
   ```
   Seeder bawaan (`UserSeeder`, `KategoriSeeder`, `ProdukSeeder`) hanya aktif saat `APP_ENV=local` atau `testing` untuk mencegah masuknya data contoh ke produksi.

   Akun uji lokal default yang dibuat oleh `UserSeeder`:
   | Role | Email | Password Default |
   | :--- | :--- | :--- |
   | **Admin** | `admin@toko.test` | `password` |
   | **Kasir** | `kasir@toko.test` | `password` |

   > Segera ganti password atau buat akun resmi melalui menu **Pengguna** sebelum aplikasi dipakai di lingkungan produksi.

---

## Panduan Menjalankan Project

### Mode Pengembangan (*Development*)
Jalankan server Laravel dan Vite dev server secara bersamaan:
```bash
composer run dev
```
Atau secara terpisah di dua terminal:
```bash
php artisan serve
npm run dev
```
Buka browser ke `http://127.0.0.1:8000`.

### Mode Produksi (*Production Build*)
```bash
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
Lalu jalankan web server pilihan Anda (Apache/Nginx/Laravel Herd/PHP-FPM) yang mengarah ke direktori `public/`.

---

## Panduan Role Admin dan Kasir

| Fitur / Modul | Admin | Kasir | Penegakan Backend |
| :--- | :---: | :---: | :--- |
| Storefront Publik (`/`, `/shop`) | Ya | Ya | Publik (tanpa login) |
| Dashboard Ringkasan Eksekutif & Grafik | Ya | Terbatas | Dashboard operasional kasir & Gate `view-daily-sales` |
| Lihat Daftar & Detail Produk | Ya | Ya | `ProdukPolicy::viewAny` / `view` |
| Tambah Produk Baru | Ya | Ya | `ProdukPolicy::create` |
| Edit & Hapus Produk | **Ya** | **Tidak (`403`)** | Middleware `role:admin` & `ProdukPolicy::update` / `delete` |
| Lihat & Tambah Pelanggan | Ya | Ya | `PelangganPolicy::viewAny` / `create` |
| Edit & Hapus Pelanggan | **Ya** | **Tidak (`403`)** | Middleware `role:admin` & `PelangganPolicy::update` / `delete` |
| Lihat & Buat Transaksi POS | Ya | Ya | `TransaksiPolicy::viewAny` / `create` |
| Riwayat Transaksi | Ya | Ya | Gate `view-transaction-history` |
| Cetak Nota Thermal (`40/58/80 mm`) | Ya | Ya | `TransaksiPolicy::view` |
| Lihat Kategori & Cabang | Ya (CRUD) | Read-only | Middleware `role:admin` pada endpoint tulis |
| Laporan Penjualan & Export CSV | **Ya** | **Tidak (`403`)** | Middleware `role:admin` & Gate `view-reports` |
| Manajemen Pengguna & Role | **Ya** | **Tidak (`403`)** | Middleware `role:admin` & Gate `manage-users` |
| Pengaturan Aplikasi | **Ya** | **Tidak (`403`)** | Middleware `role:admin` & Gate `manage-settings` |

> **Catatan Keamanan:** Pembatasan pada role Kasir tidak hanya disembunyikan di UI Vue, melainkan ditegakkan penuh di lapisan route (`role:admin`), Form Request, Policy, dan Gate sehingga akses langsung via URL/API mengembalikan **HTTP 403**.

---

## Panduan Transaksi (POS)

1. Masuk sebagai **Admin** atau **Kasir**, lalu buka menu **Transaksi Baru** (`/transaksi/create`).
2. **Pilih Pelanggan** melalui dropdown (atau klik pintasan **+ Tambah Pelanggan** jika pembeli baru). Pilih **Cabang** asal transaksi bila tersedia.
3. **Tambahkan Barang**: cari produk berdasarkan nama atau kode SKU, lalu klik kartu produk pada katalog kiri. Barang otomatis masuk ke keranjang kanan.
4. **Atur Jumlah Barang**: gunakan tombol `-` / `+` atau ketik langsung kuantitasnya (sistem membatasi maksimal sesuai stok aktif).
5. **Pilih Metode Pembayaran** (`Tunai`, `QRIS`, `Transfer`, atau `Debit`):
   - Untuk metode non-tunai, tersedia tombol isi pas sesuai total belanja.
   - Untuk tunai, masukkan nominal pada kolom **Uang Bayar** (atau pakai tombol cepat `Uang Pas`, `+50rb`, `+100rb`). Sistem menampilkan indikator **Kembalian** atau **Kurang Bayar** secara langsung.
6. Klik **Proses Transaksi**. Server memvalidasi ulang stok dengan `lockForUpdate()`, mengambil harga dari database, menghitung subtotal, total akhir, dan kembalian, lalu menyimpan header dan detail secara atomik ke `tbl_transaksi` dan `tbl_detail_transaksi`.
7. Setelah tersimpan, aplikasi mengarahkan ke halaman detail transaksi lengkap dengan tombol **Cetak Nota** dan pilih ukuran kertas.

---

## Panduan Konfigurasi Printer Thermal

Halaman cetak nota (`/transaksi/{id}/nota`) menyiapkan layout khusus thermal yang terisolasi via `@media print`:

1. **Pilih Ukuran Roll pada Toolbar Nota**:
   - **58 mm (Standar)**: Pilihan utama untuk printer thermal kasir 58 mm (`width: 58mm`, padding `3mm`, monospasi `10px`).
   - **80 mm (Lebar)**: Untuk printer kasir meja 80 mm (`width: 80mm`, padding `4mm`, monospasi `11px`).
   - **40 mm (Mini)**: Gunakan opsi ini apabila driver printer 58 mm Anda memiliki area cetak efektif hanya `40–44 mm` sehingga angka sisi kanan tidak terpotong.
2. **Pengaturan Dialog Print Browser (Chrome / Edge)**:
   - **Destination**: Pilih printer thermal yang terpasang di sistem operasi.
   - **Paper size**: Pilih ukuran roll kertas di driver printer (misalnya `58mm x 210mm` / `58mm Receipt` atau `80mm Receipt`).
   - **Margins**: Pilih **None / Tidak Ada**.
   - **Scale**: Atur ke **100% / Default** (hindari *Fit to printable area* bila membuat font terlalu kecil).
   - **Options**: **Matikan** opsi *Headers and footers* agar URL halaman dan nomor halaman browser tidak ikut tercetak.
   - **(Opsional Kasir Cepat)**: Pada Google Chrome/Edge di komputer kasir khusus, flag `--kiosk-printing` pada shortcut browser dapat diaktifkan bila ingin mencetak langsung tanpa konfirmasi dialog berulang.

---

## Pengujian & Verifikasi Kualitas

Menjalankan seluruh suite test PHPUnit (menggunakan database terisolasi `db_toko_test` sesuai [`phpunit.xml`](phpunit.xml)):
```bash
php artisan test
```
Memeriksa standar format kode PHP (Laravel Pint):
```bash
vendor/bin/pint --test
```
Membuat bundle produksi frontend (Vite):
```bash
npm run build
```
