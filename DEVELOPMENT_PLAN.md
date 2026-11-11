# DEVELOPMENT PLAN — Aplikasi Toko (`app-toko`)

Dokumen ini merancang arsitektur, rekomendasi struktur kode, matriks keamanan, serta rencana implementasi bertahap berdasarkan `AGENTS.md`, `PROJECT_ANALYSIS.md`, dan `DATABASE_ANALYSIS.md`.

---

## 1. Rekomendasi Arsitektur Aplikasi

Mengikuti stack wajib pada `AGENTS.md` (Laravel 12 + Vue 3 Composition API + Inertia.js 2 + Tailwind CSS v3 + MariaDB/MySQL):

### 1.1 Arsitektur Backend (Layered & Role-Enforced)
- **Thin Controllers (`app/Http/Controllers/`)**: Hanya menerima request yang sudah divalidasi/diotorisasi, memanggil Service/Model, dan mengembalikan Inertia response atau redirect dengan flash message.
- **Form Requests (`app/Http/Requests/`)**: Menangani seluruh validasi input dan pesan error yang konsisten (mis. `StoreProdukRequest`, `UpdateProdukRequest`, `StorePelangganRequest`, `StoreTransaksiRequest`, `StoreUserRequest`).
- **Policies & Middleware (`app/Policies/`, `app/Http/Middleware/`)**:
  - `RoleMiddleware` (`role:admin` / `role:admin,kasir`) pada level route sebagai lapisan pertama.
  - Laravel Policy (`ProdukPolicy`, `PelangganPolicy`, `TransaksiPolicy`, `KategoriPolicy`, `CabangPolicy`, `UserPolicy`) pada level controller (`$this->authorize(...)` / middleware `can:`) untuk menjamin setiap aksi terlarang mengembalikan **HTTP 403**.
- **Services (`app/Services/`)**:
  - `TransaksiService`: Mengelola penyimpanan transaksi dalam `DB::transaction`, mengambil harga resmi dari tabel `tbl_produk` (mengabaikan total/harga kiriman client), menghitung subtotal dan `total_belanja` di server, dan menerapkan aturan stok setelah disepakati.
  - `LaporanService`: Mengambil data dari SQL views (`view_laporan_transaksi`, `view_total_belanja`, `view_transaksi_pelanggan`) maupun agregasi Eloquent dengan filter tanggal & cabang yang aman (query parameterized).
- **Models (`app/Models/`)**:
  - Seluruh model tabel `tbl_*` wajib mendefinisikan `$table`, `$primaryKey`, `$timestamps = false` (jika tabel tidak memiliki `created_at`/`updated_at`), `$casts` (decimal/date/boolean), dan relasi eksplisit.
  - Gunakan eager loading (`with(...)`) dan pagination (`paginate()`) untuk mencegah N+1 query.

### 1.2 Arsitektur Frontend (Vue 3 `<script setup>` + Inertia.js)
- **Tiga Konteks Layout (`resources/js/Layouts/`)**:
  1. `StoreLayout.vue`: Navbar minimalis (Logo, Home, Shop, Kategori, Pencarian, Login/Dashboard), konten storefront bersih berfokus pada foto produk, dan Footer informasi toko/cabang.
  2. `AdminLayout.vue` & `CashierLayout.vue` (atau `InternalLayout.vue` dengan navigasi berbasis permission dari backend): Sidebar jelas, header nama halaman & identitas/role user, area kerja yang memprioritaskan kecepatan input dan keterbacaan angka/tabel.
- **Pemisahan Komponen (`resources/js/Components/` & `Composables/`)**:
  - `Components/UI/`: `BaseButton.vue`, `BaseInput.vue`, `BaseSelect.vue`, `BaseBadge.vue`, `BaseModal.vue`, `BasePagination.vue` (konsolidasi dari komponen Breeze agar tidak duplikat).
  - `Components/Tables/`, `Components/Forms/`, `Components/Feedback/`: Komponen tabel, search/filter, toast, empty state, konfirmasi hapus.
  - `Composables/`: `usePermissions.js` (hanya membaca `page.props.auth.can` dari server untuk menyembunyikan tombol/menu UI), `useCurrency.js` (format Rupiah `Rp`), `useDate.js` (format tanggal Indonesia).

### 1.3 Matriks Hak Akses yang Akan Ditegakkan
| Modul / Aksi | Publik (Guest) | Kasir (`kasir`) | Admin (`admin`) |
|---|---|---|---|
| Storefront (Home, Shop, Detail Produk) | Ya | Ya | Ya |
| Dashboard | Tidak | Ya (Operasional) | Ya (Lengkap) |
| Kategori — Lihat | Ya (Storefront) | Ya | Ya |
| Kategori — Tambah / Edit / Hapus | Tidak (401/403) | Tidak (**403**) | Ya |
| Produk — Lihat & Tambah | Tidak | Ya | Ya |
| Produk — Edit & Hapus | Tidak | Tidak (**403**) | Ya |
| Pelanggan — Lihat & Tambah | Tidak | Ya | Ya |
| Pelanggan — Edit & Hapus | Tidak | Tidak (**403**) | Ya |
| Transaksi — Lihat & Buat | Tidak | Ya | Ya |
| Transaksi — Edit / Batalkan / Hapus | Tidak | Tidak (**403**, default) | Sesuai aturan bisnis |
| Cabang — Lihat / Kelola | Info publik (jika aktif) | Lihat saja / Tidak | Ya |
| Laporan Penjualan | Tidak | Default Tidak (**403**) | Ya |
| Manajemen Pengguna & Role | Tidak | Tidak (**403**) | Ya |

---

## 2. Hal yang Harus Disepakati Sebelum Implementasi (Keputusan Bisnis & Teknis)

Sebelum menulis kode migrasi atau fitur, poin-poin berikut perlu dikonfirmasi:

1. **Mekanisme Stok Produk**: Apakah stok pada `tbl_produk` otomatis berkurang saat transaksi berhasil disimpan, dan apakah transaksi ditolak jika `stok < jumlah`?
2. **Pembatalan/Void Transaksi**: Apakah transaksi bersifat permanen, atau Admin diperbolehkan membatalkan (status `dibatalkan`) tanpa menghapus record?
3. **Pajak (PPN 11%) & Diskon**: Database memiliki function `HitungPajak` (11%) dan `HitungDiskon`. Apakah UI transaksi perlu mendukung diskon/PPN, atau menggunakan harga neto per produk saja?
4. **Identifikasi Unik Pelanggan**: Apakah pelanggan perlu field `no_hp` (unik) dan `alamat` untuk mencegah duplikasi data seperti yang terjadi di data awal (`Andi`, `Budi`, `Citra`)?
5. **Integrasi Cabang (`tbl_cabang`)**: Apakah user (`admin`/`kasir`) dan `tbl_transaksi` terikat ke cabang tertentu, atau `tbl_cabang` dikelola sebagai master informasi cabang/lokasi toko?
6. **Registrasi Publik (`/register`)**: Apakah route `/register` Breeze harus dinonaktifkan agar akun internal (`admin`/`kasir`) hanya dapat dibuat oleh Admin, dan apakah Storefront hanya berupa katalog publik (tanpa checkout/login pembeli)?
7. **Akses Kasir pada Transaksi & Laporan**: Apakah Kasir boleh melihat seluruh daftar transaksi di toko/cabang atau hanya transaksi yang ia buat, serta apakah Kasir membutuhkan ringkasan transaksi harian?
8. **Environment Testing**: Apakah testing akan dijalankan menggunakan `pdo_sqlite` (perlu mengaktifkan ekstensi di `php.ini`) atau memakai database test MySQL/MariaDB terpisah (yang juga mendukung view/trigger/procedure `db-toko`)?

---

## 3. Rencana Pengembangan Bertahap (Roadmap)

### Fase 0 — Pembersihan & Stabilisasi Fondasi
1. **Perbaikan Konfigurasi Lingkungan & Build**:
   - Sinkronkan `config/app.php` (`timezone` -> `'Asia/Jakarta'`, `locale` -> `'id'`).
   - Evaluasi dan hapus dependency yatim `@tailwindcss/vite` (`v4`) dari `package.json` agar konsisten dengan Tailwind CSS v3.4.
   - Siapkan environment testing (`pdo_sqlite` atau database MySQL testing `db_toko_test`) agar seluruh test dapat dieksekusi hijau.
2. **Pembersihan File Rusak & 0-Byte**:
   - Pulihkan halaman Profile (`Profile/Edit.vue` dan partials-nya) agar route `/profile` tidak rusak.
   - Rapikan struktur file 0-byte agar mengikuti struktur `AGENTS.md`:
     - Perbaiki typo nama file (`DataRangePicker.vue` -> `DateRangePicker.vue`, `ImageUploud.vue` -> `ImageUpload.vue`).
     - Pindahkan atau hapus `Pages/Cabang/Component/PelangganForm.vue` yang salah tempat; seragamkan casing folder/file (`Index.vue`, `Components/`).
     - Konsolidasikan komponen UI ke `Components/UI/` tanpa menduplikasi fungsi komponen Breeze yang sudah bekerja.
3. **Perbaikan Model & Blocker Database**:
   - Lengkapi `App\Models\Cabang` dan `App\Models\DetailTransaksi` (`$table`, `$primaryKey`, `$timestamps = false`, `$fillable`, relasi).
   - Buat migration `create_tbl_log_table` agar trigger `trg_insert_pelanggan` dan `trg_update_pelanggan` berjalan normal tanpa error SQL.

### Fase 1 — Autentikasi, Role (`admin` / `kasir`), & Keamanan Dasar
1. Buat migration penambahan kolom `role`, `id_cabang` (nullable), dan `is_active` pada tabel `users`.
2. Nonaktifkan route registrasi publik `/register` (atau amankan sesuai keputusan bisnis).
3. Tambahkan helper role pada `App\Models\User` (`isAdmin()`, `isKasir()`, `hasRole()`).
4. Perbarui `HandleInertiaRequests` untuk membagikan prop `auth.user` (hanya field aman: `id`, `name`, `email`, `role`, `id_cabang`), `auth.can` (map kemampuan untuk UI), serta `flash` messages (`success`, `error`).
5. Implementasikan `RoleMiddleware` dan daftarkan alias di `bootstrap/app.php`.
6. Buat Seeder akun awal yang aman (`UserSeeder` untuk minimal 1 Admin dan 1 Kasir untuk pengujian lokal).
7. Buat feature test keamanan autentikasi dan proteksi route berbasis role.

### Fase 2 — Master Data (Kategori, Produk, Cabang, Pelanggan)
1. Buat migration `tbl_kategori`, `tbl_produk` (dengan relasi kategori, harga, stok, gambar, status unggulan), serta tambahan kolom pelanggan bila disepakati.
2. Buat Model `Kategori` dan `Produk` lengkap dengan relasi dan scope pencarian/unggulan.
3. Implementasikan Policy (`KategoriPolicy`, `ProdukPolicy`, `PelangganPolicy`, `CabangPolicy`):
   - **Kasir**: `viewAny`, `view`, `create` = **Ya** (Produk & Pelanggan); `update`, `delete` = **Tidak (403)**.
   - **Admin**: Akses penuh CRUD.
4. Implementasikan Form Request dan Controller untuk `Kategori`, `Produk` (termasuk upload gambar ke disk `public` dan validasi file gambar), `Pelanggan`, dan `Cabang`.
5. Bangun komponen UI generik (`BaseButton`, `BaseInput`, `BaseSelect`, `BaseModal`, `BasePagination`, `DataTable`, `SearchInput`, `ToastNotification`, `ConfirmDialog`, `EmptyState`) dan halaman internalnya.
6. Verifikasi backend (termasuk HTTP 403 untuk Kasir yang mencoba `PUT/PATCH/DELETE` produk & pelanggan) dan verifikasi frontend (tombol edit/hapus tersembunyi bagi Kasir).

### Fase 3 — Sistem Transaksi (POS Kasir) & Integritas Data
1. Buat migration penambahan kolom `id_produk` pada `tbl_detail_transaksi` serta metadata (`kode_transaksi`, `id_cabang`, `user_id`, `status`, `metode_pembayaran`, `timestamps`) pada `tbl_transaksi` secara nullable agar 24 transaksi lama tetap utuh.
2. Buat `TransaksiService` menggunakan `DB::transaction`:
   - Validasi item produk dan ambil harga resmi dari database (tidak mempercayai harga/total kiriman browser).
   - Hitung subtotal dan total belanja di server (termasuk pajak/diskon jika diaktifkan).
   - Simpan header `tbl_transaksi` dan baris `tbl_detail_transaksi` beserta relasi `id_produk` + snapshot `nama_produk` dan `harga`.
3. Implementasikan `TransaksiController`, `StoreTransaksiRequest`, dan `TransaksiPolicy` (Admin & Kasir dapat membuat/melihat; pembatalan/penghapusan dibatasi ketat).
4. Bangun halaman `Pages/Transaksi/`:
   - `Create.vue`: Antarmuka POS cepat (pencarian produk, keranjang item dinamis, pilih/tambah cepat pelanggan, ringkasan total belanja yang sangat jelas).
   - `Index.vue`: Daftar transaksi dengan pencarian, filter tanggal, pagination.
   - `Show.vue`: Detail transaksi / tampilan struk (menangani aman transaksi historis ID 1–24 yang tidak memiliki detail atau selisih nominal).
5. Tulis feature test transaksi (perhitungan total server-side, rollback jika gagal, penolakan manipulasi harga/total, akses kasir vs admin).

### Fase 4 — Storefront Modern (`AGENTS.md` Bagian 3)
1. Bangun `StoreLayout.vue` beserta `AppHeader`, `AppFooter`, dan navigasi mobile responsif.
2. Bangun halaman publik di `Pages/Store/`:
   - `Index.vue` (route `/`): Hero/Banner promosi (memakai aset lokal/placeholder layak, tanpa URL eksternal acak), grid/carousel **Kategori**, grid **Produk Unggulan**, state loading/kosong/error, desain minimalis dominan putih/abu sangat muda sesuai arahan UI.
   - `Shop.vue` (route `/shop`): Katalog produk dengan pencarian, filter kategori, pengurutan harga, dan pagination.
   - `ProductDetail.vue` (route `/produk/{slug}`): Detail foto produk, kategori, harga, deskripsi, stok, dan produk terkait.
3. Implementasikan `StoreController` yang efisien (eager loading, pagination, hanya menampilkan produk `is_active`).

### Fase 5 — Dashboard, Laporan, & Manajemen Pengguna
1. Bangun `DashboardController` dan `Pages/Dashboard/Index.vue`:
   - **Admin**: Ringkasan penjualan, total produk, total pelanggan, transaksi terbaru, performa cabang/produk.
   - **Kasir**: Ringkasan operasional harian & pintasan cepat ke transaksi baru / tambah produk / tambah pelanggan.
2. Bangun `LaporanController` dan `Pages/Laporan/` (khusus Admin, HTTP 403 untuk Kasir kecuali disepakati lain):
   - Memanfaatkan `view_laporan_transaksi`, `view_total_belanja`, `view_transaksi_pelanggan`, serta filter rentang tanggal, cabang, dan kategori belanja (`KategoriBelanja`).
3. Bangun `UserController` dan `Pages/Pengguna/` (khusus Admin, HTTP 403 untuk Kasir):
   - CRUD akun `admin` & `kasir`, reset password oleh admin, status aktif/nonaktif, cegah admin menghapus/menonaktifkan dirinya sendiri.
4. Uji seluruh fitur end-to-end (`php artisan test`, `npm run build`, dan inspeksi tampilan desktop + mobile untuk role Guest, Kasir, dan Admin).

