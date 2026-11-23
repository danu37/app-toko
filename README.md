<div align="center">

# TokoKita — Aplikasi Toko, Storefront & Point of Sales (POS)

**Sistem manajemen retail modern berbasis web dengan storefront katalog minimalis, kasir POS real-time, cetak nota thermal (`40 mm`, `58 mm`, `80 mm`), auto file compression, serta otorisasi multi-role ketat.**

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com/)
[![Vue.js](https://img.shields.io/badge/Vue-3%20Composition%20API-4FC08D?style=flat-square&logo=vuedotjs&logoColor=white)](https://vuejs.org/)
[![Inertia.js](https://img.shields.io/badge/Inertia.js-2.0-9553E9?style=flat-square&logo=inertia&logoColor=white)](https://inertiajs.com/)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind%20CSS-v3-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white)](https://tailwindcss.com/)
[![MariaDB](https://img.shields.io/badge/MariaDB%20%2F%20MySQL-db--toko-003545?style=flat-square&logo=mariadb&logoColor=white)](https://mariadb.org/)
[![PHPUnit](https://img.shields.io/badge/Tests-102%20Passed-10B981?style=flat-square&logo=checkmarx&logoColor=white)](#pengujian-dan-verifikasi)

[Fitur Utama](#fitur-utama) &bull;
[Arsitektur](#arsitektur-sistem) &bull;
[Hak Akses](#role-dan-hak-akses) &bull;
[Instalasi](#instalasi-dan-konfigurasi) &bull;
[Panduan POS](#panduan-transaksi-pos) &bull;
[Printer Thermal](#konfigurasi-printer-thermal) &bull;
[Pengujian](#pengujian-dan-verifikasi)

<br />

<img src="public/images/store/hero-online-shopping.jpg" alt="TokoKita — Storefront & Retail POS" width="760" />

</div>

---

## Ringkasan Proyek

**TokoKita** menggabungkan etalase toko publik bergaya editorial yang bersih dengan panel operasional internal berkecepatan tinggi untuk **Administrator**, **Kasir**, serta portal mandiri bagi **Pelanggan**. Aplikasi dibangun di atas fondasi skema `db-toko` dengan migration non-destruktif sehingga data historis, *view*, *stored routine*, dan *trigger* legacy tetap terjaga aman.

> [!IMPORTANT]
> Seluruh pembatasan hak akses **Admin vs Kasir vs Pelanggan** ditegakkan penuh di sisi server melalui Middleware (`EnsureUserHasRole`), Laravel Policy, Gate, Form Request, dan perhitungan transaksi otoritatif—bukan sekadar menyembunyikan tombol pada antarmuka Vue.

---

## Fitur Utama

### 1. Storefront Publik Minimalis (`/`, `/shop`, `/shop/{slug}`)
- **Beranda Editorial**: Hero promosi ([`public/images/store/hero-online-shopping.jpg`](public/images/store/hero-online-shopping.jpg)), daftar kategori unggulan (*Shop by Categories*), produk terlaris (*Our Bestsellers*), banner promo ([`public/images/store/promo-editorial.webp`](public/images/store/promo-editorial.webp)), serta navigasi desktop & mobile yang responsif.
- **Katalog & Detail Produk**: Pencarian real-time, filter kategori, pengurutan harga, status stok, dan rekomendasi produk terkait (hanya menampilkan item aktif).

### 2. Dashboard Khusus Per Role (`/dashboard`)
- **Dashboard Administrator**: Total produk, kategori, pelanggan, transaksi, total pendapatan riil, grafik tren harian/bulanan, produk terlaris, ringkasan stok kritis/menipis, dan 5 transaksi terbaru.
- **Dashboard Operasional Kasir**: Ringkasan transaksi hari ini, jumlah pelanggan dilayani, transaksi yang diproses kasir terkait, omzet harian sesuai izin `view-daily-sales`, serta pintasan cepat POS, tambah produk, dan tambah pelanggan.

### 3. Sistem Transaksi POS Aman & Integrasi QRIS Nasional (`/transaksi/create`)
- **Integrasi Pembayaran QRIS Nasional (GPN)**:
  - Menggunakan QRIS resmi merchant **Danu Anggo, Pakaian** (NMID: `ID1026645584610`, Terminal: `A01`).
  - Saat kasir memilih metode bayar **QRIS**:
    - Nominal bayar otomatis tersinkronisasi pas dengan total belanja.
    - Menampilkan panel interaktif QRIS dengan kode QR, informasi NMID, dan tombol salin instan.
    - Fitur **Layar Penuh QRIS Pelanggan (*Customer Facing Modal*)**: Kasir dapat membuka tampilan poster QRIS penuh beresolusi tinggi dengan nominal belanja besar agar mudah dipindai oleh pelanggan menggunakan aplikasi e-wallet (GoPay, OVO, DANA, ShopeePay, LinkAja) maupun mobile banking (BCA, Livin Mandiri, BRImo, BNI).
    - Status verifikasi pembayaran terpadu sebelum transaksi disimpan.
- **Antarmuka Cepat Kasir**: Pencarian nama/SKU/barcode (mendukung scanner barcode USB/Bluetooth keyboard-wedge), filter kategori, keranjang belanja dengan batas stok real-time, tombol cepat nominal tunai, serta kalkulasi kembalian / kurang bayar.
- **Integritas Finansial & Stok Backend**:
  - Diproses atomik dalam `DB::transaction` menggunakan penguncian baris `lockForUpdate()`.
  - Harga satuan, subtotal, total akhir, dan kembalian **dihitung ulang dari database di server** (`TransaksiService`); manipulasi harga/total dari browser diabaikan atau ditolak.
  - Menyimpan snapshot **nama kasir (`nama_kasir`)** serta **nama & harga produk** sehingga nota lama tetap konsisten walau harga produk berubah atau akun pengguna dihapus.

### 4. Cetak Nota Thermal Multi-Ukuran (`/transaksi/{id}/nota`)
- Mendukung pilihan lebar roll **40 mm (Mini)**, **58 mm (Standar)**, dan **80 mm (Lebar)** lewat `@page` dinamis dan `@media print`.
- Menyembunyikan seluruh sidebar, header, dan tombol toolbar saat dicetak sehingga kertas thermal tidak terbuang.
- Menampilkan **No Nota**, **Tanggal**, **Kasir**, **Pelanggan**, rincian item dengan *word-wrapping* rapi, total akhir, pembayaran, dan kembalian.
- **Rincian Khusus QRIS pada Struk**: Untuk transaksi non-tunai QRIS, struk secara otomatis mencetak keterangan `BAYAR (QRIS GPN)`, nama merchant **Danu Anggo, Pakaian**, dan **NMID ID1026645584610**.

### 5. Auto File Compression (Maksimal 2 MB)
- Terintegrasi pada form **Produk** dan **Kategori** melalui [`resources/js/Utils/imageCompression.js`](resources/js/Utils/imageCompression.js) dan [`app/Services/ImageCompressionService.php`](app/Services/ImageCompressionService.php).
- **Otomatis & Cerdas**:
  - File `<= 2 MB` disimpan langsung tanpa kompresi ulang dan mempertahankan format aslinya.
  - Gambar `> 2 MB` (`JPG`/`JPEG`, `PNG`, `WEBP`) dikompres otomatis secara bertahap (penyesuaian kualitas lalu penurunan resolusi proporsional menjaga aspek rasio & transparansi PNG) hingga `<= 2 MB`.
  - UI menampilkan ukuran *Before*, *After*, persentase penghematan (*Saved %*), status proses, dan pratinjau langsung, dilengkapi validasi ulang MIME & ekstensi di server via ekstensi `gd`/`exif`.

### 6. Manajemen Master Data, Laporan & Export CSV
- **Master Data**: Produk (dengan *soft delete* aman bagi riwayat transaksi dan barcode scanner unik), Kategori, Pelanggan (validasi anti-duplikasi nomor HP), Cabang, dan Pengguna + Pengaturan Role.
- **Laporan & Rekap (`/laporan`)**: Penjualan, rekap harian, metode pembayaran, performa cabang & kasir, produk terlaris, performa pelanggan, filter rentang tanggal/kasir/pelanggan/cabang, tampilan cetak laporan, serta **Export CSV** ber-BOM UTF-8 yang aman dari *formula injection*.

### 7. Sistem Login RFID Multi-Role & Portal Dashboard Pelanggan
- **Tap Kartu RFID Langsung dari Beranda / Storefront (`/`)**:
  - Pengguna (Admin, Kasir, maupun Pelanggan) dapat menempelkan kartu RFID kapan saja langsung di halaman **Home** maupun katalog storefront tanpa harus membuka menu login terlebih dahulu.
  - Listener pintar keyboard-wedge global (`useRfidStoreScanner`) menangkap pembacaan RFID reader berkecepatan tinggi, memfilter pengetikan manual di kotak pencarian, menampilkan overlay animasi pemrosesan instan (*"Memverifikasi Kartu RFID..."*), dan langsung mengarahkan pengguna ke dashboard sesuai perannya.
- **Autentikasi Cepat Multi-Role (`/login`)**:
  - Sistem login RFID yang mendukung **Admin**, **Kasir**, maupun **Pelanggan** menggunakan mesin **RFID Reader USB / Bluetooth HID (*keyboard-wedge*)** atau input UID manual.
  - Pengalihan cerdas sesuai role:
    - **Admin** &rarr; Dashboard Eksekutif Admin (`/dashboard`).
    - **Kasir** &rarr; Dashboard Operasional Kasir (`/dashboard`).
    - **Pelanggan** &rarr; Dashboard Mandiri Pelanggan (`/pelanggan/dashboard`).
  - Proteksi server ketat: rate limiting (5 attempt/menit), session regeneration, pesan penolakan *"Kartu RFID tidak dikenali"* bila tidak terdaftar, serta blokir kartu/akun yang dinonaktifkan.
- **Portal Dashboard Pelanggan (`/pelanggan/dashboard`)**:
  - Terintegrasi langsung dengan data master `tbl_pelanggan` via relasi `users.id_pelanggan`.
  - Menampilkan profil pelanggan, status kartu RFID, ringkasan belanja (total transaksi & akumulasi nominal), serta tabel riwayat belanja dengan akses cetak struk belanjaan pribadi.
- **Manajemen Kartu RFID Terpusat (`/pengguna`)**:
  - Arsitektur tabel khusus `rfid_cards` (`user_id`, `uid`, `status`, `registered_at`, `last_used_at`).
  - Admin dapat mendaftarkan kartu RFID baru, mengubah nomor UID, mengaktifkan/menonaktifkan kartu secara instan, serta menghapus/mencabut kartu yang hilang dengan dialog konfirmasi aman.

---

## Arsitektur Sistem

```mermaid
flowchart LR
    Guest["Pengunjung Publik"] -->|"Storefront & Katalog"| StoreCtrl["StoreController"]
    Staff["Admin / Kasir (Auth)"] -->|"Middleware role & Policy"| Internal["Controllers (POS, Master, Laporan)"]
    Internal --> SvcTx["TransaksiService (DB::transaction + lockForUpdate)"]
    Internal --> SvcImg["ImageCompressionService (Max 2 MB + MIME Check)"]
    Internal --> SvcLap["LaporanService (Agregasi + CSV Aman)"]
    StoreCtrl --> DB[("MariaDB / MySQL - db-toko")]
    SvcTx --> DB
    SvcLap --> DB
```

| Lapisan | Teknologi & Paket Utama |
| :--- | :--- |
| **Backend** | PHP `^8.2`, Laravel `^12.0`, Sanctum `^4.0`, Ziggy `^2.0` |
| **Frontend** | Vue `^3.4` (`<script setup>`), Inertia.js `^2.0`, Tailwind CSS `^3.2`, Vite `^7.0` |
| **Database** | MariaDB / MySQL (`db-toko` + tabel/view/trigger legacy dipertahankan) |
| **Kualitas & Uji** | PHPUnit `^11.5`, Laravel Pint `^1.24`, Vite Production Build |

<details>
<summary><strong>Lihat Struktur Direktori Utama Proyek</strong></summary>

```text
app-toko/
├── app/
│   ├── Http/
│   │   ├── Controllers/        # Store, Dashboard, Produk, Kategori, Pelanggan, Transaksi, Cabang, Laporan, User, Pengaturan
│   │   ├── Middleware/         # EnsureUserHasRole, HandleInertiaRequests
│   │   ├── Requests/           # Form Request validasi & otorisasi per modul
│   │   └── Resources/          # API/Inertia JSON Resources tanpa wrapping
│   ├── Models/                 # User, Produk, Kategori, Pelanggan, Cabang, Transaksi, DetailTransaksi, LogAktivitas
│   ├── Policies/               # Kebijakan otorisasi (403 enforcement per model)
│   └── Services/               # TransaksiService, LaporanService, ImageCompressionService
├── config/store.php            # Identitas toko untuk storefront & header nota
├── database/
│   ├── migrations/             # 15 file migration non-destruktif
│   └── seeders/                # UserSeeder, KategoriSeeder, ProdukSeeder (local/testing)
├── resources/js/
│   ├── Components/             # UI base, Forms, Tables, Feedback (ToastNotification, ConfirmDialog)
│   ├── Composables/            # usePermissions, useCurrency, useDate
│   ├── Layouts/                # StoreLayout, AdminLayout, CashierLayout
│   ├── Pages/                  # Store, Dashboard, Produk, Kategori, Pelanggan, Cabang, Transaksi, Laporan, Pengguna, Pengaturan
│   └── Utils/                  # imageCompression.js
├── routes/web.php              # Peta route publik, operasional (admin/kasir), dan admin-only
└── tests/Feature/              # Suite pengujian PHPUnit lengkap
```

</details>

---

## Role dan Hak Akses

| Modul / Fitur | Admin | Kasir | Pelanggan | Penegakan Server |
| :--- | :---: | :---: | :---: | :--- |
| Storefront Publik (`/`, `/shop`) | Ya | Ya | Ya | Route publik |
| Dashboard Ringkasan Eksekutif & Grafik | Ya | Operasional | **Tidak (`403`)** | Middleware `role:admin,kasir` |
| Portal Dashboard Pelanggan & Riwayat Belanja | **Tidak (`403`)** | **Tidak (`403`)** | Ya | Middleware `role:pelanggan` |
| Lihat & Tambah Produk Baru | Ya | Ya | **Tidak (`403`)** | Middleware `role:admin,kasir` & `ProdukPolicy` |
| Edit & Hapus Produk | **Ya** | **Tidak (`403`)** | **Tidak (`403`)** | Middleware `role:admin` & `ProdukPolicy` |
| Lihat & Tambah Master Pelanggan | Ya | Ya | **Tidak (`403`)** | Middleware `role:admin,kasir` & `PelangganPolicy` |
| Edit & Hapus Master Pelanggan | **Ya** | **Tidak (`403`)** | **Tidak (`403`)** | Middleware `role:admin` & `PelangganPolicy` |
| POS & Pembuatan Transaksi Kasir | Ya | Ya | **Tidak (`403`)** | Middleware `role:admin,kasir` & `TransaksiPolicy` |
| Cetak Nota Transaksi Pribadi | Ya | Ya | Ya (Transaksi Milik Sendiri) | `TransaksiPolicy::view` |
| Kategori & Cabang (Lihat / Kelola) | Ya (CRUD) | Read-only (`403` mutasi) | **Tidak (`403`)** | Middleware `role:admin` |
| Laporan Penjualan & Export CSV | **Ya** | **Tidak (`403`)** | **Tidak (`403`)** | Middleware `role:admin` & Gate `view-reports` |
| Manajemen Pengguna & Pengaturan Sistem | **Ya** | **Tidak (`403`)** | **Tidak (`403`)** | Middleware `role:admin` & Gate `manage-settings` |
| Login Cepat Kartu RFID (Reader Mesin / Keyboard) | **Ya** | **Ya** | **Ya** | `RfidLoginRequest` & multi-role redirect |

> [!NOTE]
> Registrasi publik (`/register`) dinonaktifkan. Penambahan akun staf serta perubahan role hanya dapat dilakukan oleh **Administrator**, dan sistem menolak upaya admin menurunkan/menonaktifkan akunnya sendiri.

---

## Instalasi dan Konfigurasi

### Prasyarat Sistem
- **PHP `>= 8.2`** (ekstensi `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, plus `gd` dan `exif` untuk Auto File Compression)
- **Composer**
- **Node.js `>= 20`** & **NPM**
- **MariaDB / MySQL** (misalnya via XAMPP atau Laragon)

### Setup Cepat (Rekomendasi)

> [!NOTE]
> Pastikan layanan MariaDB/MySQL aktif dan database `db-toko` sudah dibuat terlebih dahulu (`root` tanpa password sesuai `.env.example`), karena `composer run setup` langsung menjalankan `php artisan migrate --force`.

```bash
# 1. Masuk ke direktori proyek
cd app-toko

# 2. Instal dependensi, salin .env, generate APP_KEY, migrate, dan build aset
composer run setup

# 3. Buat symbolic link agar foto produk & kategori dapat diakses publik
php artisan storage:link
```

<details>
<summary><strong>Atau Instalasi Langkah demi Langkah &amp; Konfigurasi <code>.env</code></strong></summary>

1. Instal paket PHP dan Node.js:
   ```bash
   composer install
   npm install
   ```
2. Salin konfigurasi environment dan buat `APP_KEY`:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
3. Sesuaikan koneksi database MariaDB/MySQL serta identitas toko pada `.env`:
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

   STORE_NAME="TokoKita"
   STORE_TAGLINE="Layanan Kasir & Kebutuhan Harian"
   STORE_ADDRESS="Jl. Melati No. 12, Ponorogo"
   STORE_PHONE="021-555-0199"
   STORE_HOURS="Buka Setiap Hari 08.00 - 21.00 WIB"
   STORE_LOGO_URL=
   ```
4. Buat database `db-toko` (atau impor `db-toko` awal jika tersedia), lalu jalankan seluruh migration:
   ```bash
   php artisan migrate
   php artisan storage:link
   ```

</details>

### Seeding Awal & Akun Uji (`local` / `testing`)

Untuk mengisi akun uji, kategori, dan produk contoh di environment lokal:

```bash
php artisan db:seed
```

| Role | Email | Password Default |
| :--- | :--- | :--- |
| **Admin** | `admin@toko.test` | `password` |
| **Kasir** | `kasir@toko.test` | `password` |

> [!TIP]
> Seeder bawaan (`UserSeeder`, `KategoriSeeder`, `ProdukSeeder`) otomatis melewati eksekusi bila `APP_ENV` bukan `local` atau `testing`. Segera ubah kata sandi sebelum deploy ke produksi.

### Menjalankan Aplikasi

```bash
# Mode Development (menjalankan Laravel server, queue listener, logs, & Vite HMR)
composer run dev

# Atau jalankan manual di 2 terminal terpisah:
php artisan serve
npm run dev
```

Untuk **Production Build**:

```bash
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Panduan Transaksi POS & Barcode Scanner

1. Login sebagai **Admin** atau **Kasir**, lalu buka menu **Transaksi Baru** (`/transaksi/create`).
2. **Scanner Barcode Otomatis**:
   - Kolom pemindaian barcode langsung aktif (`autofocus`) dengan status hijau **Scanner Siap**.
   - Arahkan mesin barcode scanner USB / Bluetooth ke barcode kemasan barang (scanner keyboard-wedge dengan akhiran `Enter`).
   - Tekan shortcut **F2** kapan pun dari mana saja di layar untuk mengembalikan fokus instan ke barcode scanner.
   - Jika barcode terdaftar dan stok mencukupi, barang otomatis masuk ke keranjang kasir (kuantitas bertambah +1 jika sudah ada di keranjang) disertai notifikasi sukses.
   - Jika barcode tidak terdaftar atau stok habis, sistem menampilkan notifikasi kesalahan/peringatan tanpa merusak keranjang belanja.
   - Kolom input scanner dibersihkan otomatis dan fokus langsung dipertahankan untuk scan barang berikutnya berturut-turut.
3. **Pencarian Manual & Keranjang**:
   - Kasir tetap dapat mencari barang lewat nama, kode SKU, atau kategori, lalu klik kartu produk untuk memasukkan ke keranjang.
4. Pilih **Pelanggan** (atau gunakan pintasan **+ Pelanggan Baru** jika belum terdaftar) dan **Cabang**.
5. Sesuaikan **Jumlah Barang** dengan tombol `-` / `+` atau ketik langsung sesuai batas stok fisik tersedia.
6. Pilih metode pembayaran (`Tunai`, `QRIS`, `Transfer`, atau `Debit`) dan masukkan **Uang Pembayaran** untuk melihat kembalian instan.
7. Klik **Proses Transaksi**. Server memvalidasi stok, menghitung ulang subtotal/total akhir secara atomik, menyimpan snapshot produk & `nama_kasir`, lalu membuka halaman detail transaksi siap cetak.

---

## Konfigurasi Printer Thermal

Halaman `/transaksi/{id}/nota` menyediakan isolasi cetak khusus via `@media print`:

1. **Pilih Lebar Kertas pada Toolbar Nota**:
   - **58 mm (Standar)**: Pilihan utama untuk printer thermal kasir `58 mm` (`width: 58mm`, padding `3mm`).
   - **80 mm (Lebar)**: Untuk printer kasir meja `80 mm` (`width: 80mm`, padding `4mm`).
   - **40 mm (Mini)**: Direkomendasikan bila driver printer `58 mm` memiliki area cetak efektif `40–44 mm` agar angka sisi kanan tidak terpotong.
2. **Pengaturan Dialog Print Browser (Chrome / Edge)**:
   - **Destination**: Pilih printer thermal yang terpasang.
   - **Paper size**: Sesuaikan dengan roll kertas (`58mm Receipt` / `80mm Receipt`).
   - **Margins**: Pilih **None / Tidak Ada**.
   - **Scale**: Atur ke **100% / Default**.
   - **Options**: **Matikan** *Headers and footers* agar URL dan tanggal browser tidak ikut tercetak.

---

## Pengujian dan Verifikasi

Aplikasi menggunakan database terisolasi `db_toko_test` (lihat [`phpunit.xml`](phpunit.xml)) untuk mencegah gangguan terhadap database operasional `db-toko`.

```bash
# Menjalankan seluruh test suite PHPUnit (95 tests)
php artisan test

# Memeriksa standar format kode PHP (Laravel Pint)
vendor/bin/pint --test

# Memvalidasi kompilasi aset frontend (Vite + Vue 3 + Tailwind CSS)
npm run build
```

<details>
<summary><strong>Daftar Cakupan Suite Pengujian Otomatis</strong></summary>

- `tests/Feature/Auth/*` — Login credential, logout, validasi, rate limit, registrasi publik nonaktif, profil & password
- [`tests/Feature/Auth/RfidAuthenticationTest.php`](tests/Feature/Auth/RfidAuthenticationTest.php) — Autentikasi Admin via RFID card reader, proteksi rate limit, penolakan kartu kasir/kartu nonaktif, normalisasi UID, serta manajemen & pencabutan UID staf
- [`tests/Feature/RoleAuthenticationAndInertiaTest.php`](tests/Feature/RoleAuthenticationAndInertiaTest.php) — Proteksi guest, shared Inertia permissions, RBAC admin vs kasir
- [`tests/Feature/ProdukAuthorizationTest.php`](tests/Feature/ProdukAuthorizationTest.php) — Akses produk kasir (`create`/`view`) vs penolakan `403` untuk `edit`/`delete`
- [`tests/Feature/PelangganAuthorizationTest.php`](tests/Feature/PelangganAuthorizationTest.php) — Akses pelanggan & penolakan duplikasi nomor HP
- [`tests/Feature/AdminOnlyModulesAuthorizationTest.php`](tests/Feature/AdminOnlyModulesAuthorizationTest.php) — Proteksi modul khusus admin & blokir user nonaktif
- [`tests/Feature/TransaksiFlowTest.php`](tests/Feature/TransaksiFlowTest.php) — Checkout atomik, anti-tampering harga/total, snapshot `nama_kasir`, validasi stok, cetak nota `40/58/80 mm`
- [`tests/Feature/ImageAutoCompressionTest.php`](tests/Feature/ImageAutoCompressionTest.php) — Auto kompresi gambar `> 2 MB`, skip `<= 2 MB`, aspek rasio, validasi MIME/ekstensi
- [`tests/Feature/AdminDashboardAndReportsTest.php`](tests/Feature/AdminDashboardAndReportsTest.php) — Akurasi agregasi dashboard, grafik penjualan, filter laporan, & sanitasi formula CSV
- [`tests/Feature/StorefrontAndRelationsTest.php`](tests/Feature/StorefrontAndRelationsTest.php) — Storefront publik, filter katalog, & relasi antar model
- [`tests/Feature/Prompt11SecurityAndAuditTest.php`](tests/Feature/Prompt11SecurityAndAuditTest.php) — Security audit end-to-end, anti-spoofing transaksi, produk soft-delete pada struk, & halaman error Inertia (`403`/`404`)

</details>
