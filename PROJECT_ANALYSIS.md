# PROJECT ANALYSIS — Aplikasi Toko (`app-toko`)

Dokumen ini merangkum hasil audit menyeluruh terhadap kondisi aktual repositori `app-toko` sebelum implementasi fitur atau perubahan database dilakukan, mengacu pada pedoman di `AGENTS.md`.

---

## 1. Ringkasan Eksekutif

Proyek `app-toko` berada pada fase **fondasi awal (starter template)**. Repositori telah memiliki instalasi **Laravel 12 + Laravel Breeze (Inertia.js + Vue 3 + Tailwind CSS)** serta file dump database awal `db-toko.sql` yang sudah terimpor di MariaDB lokal (`db-toko`). Namun:

1. **Fitur bisnis aplikasi toko belum diimplementasikan** (storefront, master kategori, master produk, manajemen pelanggan, transaksi/POS kasir, manajemen cabang, laporan, maupun manajemen pengguna).
2. **Sistem role (`admin` dan `kasir`) & otorisasi backend belum ada sama sekali** (tabel `users` masih kosong dan belum memiliki kolom `role`; tidak ada Policy, Gate, maupun middleware role).
3. **Terdapat kerusakan pada modul bawaan Profile & puluhan file placeholder 0-byte** akibat inisialisasi struktur manual yang belum diisi dan penghapusan parsial halaman Profile Breeze.
4. **Test suite bawaan gagal dijalankan** karena konfigurasi `phpunit.xml` memakai `sqlite :memory:`, sedangkan ekstensi `pdo_sqlite` tidak aktif di PHP CLI lokal.

---

## 2. Versi Runtime, Framework, dan Dependency

Seluruh versi diverifikasi dari `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, dan CLI lokal:

### 2.1 Lingkungan Lokal
| Komponen | Versi Terpasang | Keterangan |
|---|---|---|
| PHP (CLI) | `8.2.31` (NTS Win32 VS16 x64) | Lokasi `php.ini`: `C:\laragon\bin\php\php-8.2.31-nts-Win32-vs16-x64\php.ini` |
| Ekstensi PDO PHP | `PDO`, `pdo_mysql`, `mysqli` | **`pdo_sqlite` tidak terpasang/tidak aktif** |
| Database Server | `10.4.32-MariaDB` | Host `127.0.0.1:3306`, database `db-toko` |
| Composer | `2.10.2` | - |
| Node.js / NPM | `v26.7.0` / `11.19.0` | - |

### 2.2 Backend Dependencies (`composer.json` / `composer.lock`)
| Package | Constraint | Versi Terpasang | Peran |
|---|---|---|---|
| `laravel/framework` | `^12.0` | `v12.71.0` | Framework backend utama |
| `inertiajs/inertia-laravel` | `^2.0` | `v2.0.28` | Adapter server-side Inertia.js |
| `tightenco/ziggy` | `^2.0` | `v2.6.5` | Helper named route Laravel di Vue |
| `laravel/sanctum` | `^4.0` | `v4.3.3` | Terpasang, belum digunakan pada route |
| `laravel/tinker` | `^2.10.1` | `v2.11.1` | REPL Artisan |
| `laravel/breeze` (dev) | `^2.4` | `v2.4.2` | Scaffolding autentikasi Inertia Vue |
| `phpunit/phpunit` (dev) | `^11.5.50` | `v11.5.57` | Unit & feature testing |
| `laravel/pint` (dev) | `^1.24` | `v1.30.4` | Code formatter PHP |
| `laravel/pail` (dev) | `^1.2.2` | `v1.2.7` | Log viewer |

### 2.3 Frontend Dependencies (`package.json` / `package-lock.json`)
| Package | Constraint | Versi Terpasang | Catatan |
|---|---|---|---|
| `vue` | `^3.4.0` | `3.5.45` | Seluruh komponen aktif memakai `<script setup>` (Composition API) |
| `@inertiajs/vue3` | `^2.0.0` | `2.3.29` | Adapter client-side Inertia.js |
| `tailwindcss` | `^3.2.1` | `3.4.19` | Aktif melalui `postcss.config.js` & `tailwind.config.js` |
| `@tailwindcss/forms` | `^0.5.3` | `0.5.11` | Plugin styling form Tailwind v3 |
| `@tailwindcss/vite` | `^4.0.0` | `4.3.5` | **Anomali:** Paket Tailwind v4 terpasang di `package.json`, namun tidak dipakai di `vite.config.js` dan bentrok versi secara konseptual dengan Tailwind `3.4.19` |
| `@vitejs/plugin-vue` | `^6.0.0` | `6.0.10` | Plugin Vite untuk Vue SFC |
| `laravel-vite-plugin` | `^2.0.0` | `2.1.0` | Integrasi Vite dengan Laravel |
| `vite` | `^7.0.7` | `7.3.7` | Build tool & dev server |
| `axios` | `^1.11.0` | `1.21.0` | HTTP client bawaan `bootstrap.js` |

---

## 3. Struktur Folder & Status Implementasi File

### 3.1 Backend (`app/`, `routes/`, `database/`, `config/`)
- **`routes/web.php`**: Hanya mendefinisikan 3 route aplikasi (`/`, `/dashboard`, `/profile` GET/PATCH/DELETE) serta `require __DIR__.'/auth.php'`. Total route terdaftar (`php artisan route:list`): 24 route (mayoritas auth Breeze, Sanctum CSRF, storage, `/up`).
- **`routes/auth.php`**: Seluruh route autentikasi Breeze aktif (`login`, `register`, `forgot-password`, `reset-password`, `verify-email`, `confirm-password`, `password`, `logout`).
- **`app/Http/Controllers/`**:
  - Aktif: `Controller.php`, `ProfileController.php`, dan 9 controller di `Auth/`.
  - Belum ada: controller bisnis (`StoreController`, `DashboardController`, `ProdukController`, `KategoriController`, `PelangganController`, `TransaksiController`, `CabangController`, `LaporanController`, `PenggunaController`).
- **`app/Http/Middleware/`**:
  - Aktif: `HandleInertiaRequests.php` (baru membagikan `auth.user` secara penuh tanpa pembatasan field atau info role/permissions/flash message).
  - Belum ada: middleware pengecekan role (`admin` / `kasir`).
- **`app/Http/Requests/`**: Hanya `ProfileUpdateRequest.php` dan `Auth/LoginRequest.php`.
- **`app/Models/`**:
  - `User.php`: Standar Breeze (`name`, `email`, `password`). Belum memiliki atribut/relasi role atau cabang.
  - `Pelanggan.php`: Sudah dikonfigurasi dengan `$table = 'tbl_pelanggan'`, `$primaryKey = 'id_pelanggan'`, `$timestamps = false`, `$fillable = ['nama_pelanggan', 'kota']`, dan relasi `transaksi()` (`hasMany` ke `Transaksi`).
  - `Transaksi.php`: Sudah dikonfigurasi dengan `$table = 'tbl_transaksi'`, `$primaryKey = 'id_transaksi'`, `$timestamps = false`, `$fillable = ['tanggal', 'id_pelanggan', 'total_belanja']`, relasi `pelanggan()` (`belongsTo`) dan `detail()` (`hasMany` ke `DetailTransaksi`).
  - `Cabang.php`: **Kosong** (hanya stub class extend `Model`, belum mendefinisikan `$table`, `$primaryKey`, `$timestamps = false`, maupun `$fillable`).
  - `DetailTransaksi.php`: **Kosong** (hanya stub class extend `Model` — jika `Transaksi::detail()` dipanggil sekarang akan gagal mencari tabel `detail_transaksis`!).
  - Belum ada: `Kategori.php`, `Produk.php`, `LogAktivitas.php` (opsional jika membaca `tbl_log`).
- **`app/Policies/` & `app/Services/`**: Belum dibuat sama sekali.
- **`database/migrations/`**: Hanya berisi 3 file migration bawaan Laravel (`users`+`password_reset_tokens`+`sessions`, `cache`, `jobs`). Belum ada migration untuk tabel toko maupun perubahan tabel `users`.
- **`database/seeders/DatabaseSeeder.php`**: Masih seed default `test@example.com` tanpa role.

### 3.2 Frontend (`resources/js/`)

#### A. File Aktif dan Dapat Dipakai Kembali (Breeze Baseline)
Seluruh file aktif memakai **Vue 3 Composition API (`<script setup>`)** dan **Tailwind CSS v3**:
- **Entry & Config**: `resources/js/app.js` (Inertia + ZiggyVue), `resources/js/bootstrap.js`, `resources/css/app.css`, `resources/views/app.blade.php`.
- **Komponen UI Breeze (`resources/js/Components/`)**:
  - Tombol: `PrimaryButton.vue`, `SecondaryButton.vue`, `DangerButton.vue`
  - Input & Form: `TextInput.vue`, `InputLabel.vue`, `InputError.vue`, `Checkbox.vue`
  - Navigasi & Overlay: `Modal.vue` (native `<dialog>` + transisi + close on Escape/backdrop), `Dropdown.vue`, `DropdownLink.vue`, `NavLink.vue`, `ResponsiveNavLink.vue`
  - Branding: `ApplicationLogo.vue` (masih SVG logo Laravel)
- **Layouts**: `GuestLayout.vue`, `AuthenticatedLayout.vue`.
- **Pages**: `Welcome.vue` (halaman welcome default Laravel), `Dashboard.vue` (teks statis `"You're logged in!"`), `Auth/*` (6 halaman lengkap: `Login`, `Register`, `ForgotPassword`, `ResetPassword`, `VerifyEmail`, `ConfirmPassword`).

#### B. File Rusak / Kosong (0 Byte) & Ketidakkonsistenan Struktur
Status Git (`git status -s`) dan pemeriksaan ukuran file menemukan:
1. **Modul Profile Rusak**:
   - `resources/js/Pages/Profile/Edit.vue` termodifikasi menjadi **0 byte** (kosong).
   - 3 file di `resources/js/Pages/Profile/Partials/` (`DeleteUserForm.vue`, `UpdatePasswordForm.vue`, `UpdateProfileInformationForm.vue`) berstatus **deleted (`D`)** di working tree.
   - `public/build/manifest.json` masih memuat hasil build lama Breeze sebelum penghapusan tersebut, sehingga manifest produksi tidak sinkron dengan source code saat ini.
2. **Puluhan File Kerangka Kosong (0 byte)**:
   - `Components/`: `BaseButton.vue`, `BaseInput.vue`, `BaseModal.vue`, `BaseTable.vue` (diletakkan langsung di `Components/`, bukan `Components/UI/`, dan berpotensi duplikasi dengan `PrimaryButton.vue`, `TextInput.vue`, `Modal.vue`).
   - `Components/Feedback/`: `ConfirmDialog.vue`, `EmptyState.vue`, `ToastNotification.vue` (0 byte).
   - `Components/Forms/`: `DataRangePicker.vue` *(typo `Data` -> seharusnya `Date`)*, `ImageUploud.vue` *(typo `Uploud` -> seharusnya `Upload`)*, `SearchInput.vue` (0 byte).
   - `Components/Tables/`: `DataTable.vue`, `TableAction.vue` (0 byte).
   - `Layouts/`: `AppBreadCrumb.vue`, `AppFooter.vue`, `AppHeader.vue`, `AppLayout.vue`, `AppSidebar.vue` (0 byte).
   - `Pages/Cabang/`: `Create.vue`, `Edit.vue`, `index.vue` *(lowercase)*, `Component/PelangganForm.vue` *(0 byte; nama `PelangganForm` salah folder di dalam `Cabang/Component/` dan memakai folder singular `Component`)*.
   - `Pages/Dashboard/`: `index.vue` *(berbenturan penamaan dengan `Pages/Dashboard.vue`)* + `Components/` (`RecentTransaction.vue`, `SalesCard.vue`, `StatCard.vue`) (0 byte).
   - `Pages/Laporan/`: `Index.vue`, `Pelanggan.vue`, `Transaksi.vue`, `Components/ReportFilter.vue` (0 byte).
   - `Pages/Profile/`: `Edit.vue`, `Index.vue` (0 byte).
   - `Pages/Transaksi/`: `Create.vue`, `index.vue` *(lowercase)*, `Show.vue`, `Components/` (`DetailTransaksiTable.vue`, `TransactionSummary.vue`, `TransaksiForm.vue`) (0 byte).
3. **Folder yang Belum Ada Sama Sekali (Sesuai `AGENTS.md`)**:
   - `Pages/Store/` (`Index.vue`, `Shop.vue`, `ProductDetail.vue`)
   - `Pages/Produk/` (`Index.vue`, `Create.vue`, `Edit.vue`)
   - `Pages/Kategori/` (jika dipisah atau di dalam admin)
   - `Pages/Pelanggan/` (`Index.vue`, `Create.vue`, `Edit.vue`)
   - `Pages/Pengguna/` (`Index.vue`, `Create.vue`, `Edit.vue`)
   - `Composables/` (`useCurrency.js`, `useDate.js`, `usePermissions.js`)
   - `Utils/` (`formatCurrency.js`, `formatDate.js`)

---

## 4. Konfigurasi Database & Lingkungan

- **Konfigurasi Aktif (`.env` & `config/database.php`)**:
  - `DB_CONNECTION=mysql` mengarah ke `127.0.0.1:3306`, database `db-toko`.
  - Driver `mysql` memakai `charset => utf8mb4`, `collation => utf8mb4_unicode_ci`, dan `'strict' => true`.
  - `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database` (tabel terkait sudah tersedia di `db-toko`).
  - `FILESYSTEM_DISK=local` (untuk upload gambar produk di masa depan, perlu memakai disk `public` + `php artisan storage:link`).
- **Konfigurasi Testing (`phpunit.xml`)**:
  - `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`.
  - Terdapat file `database/database.sqlite` kosong yang tidak terpakai oleh runtime lokal.

---

## 5. Audit Autentikasi & Authorization

### 5.1 Status Autentikasi
- Menggunakan **Laravel Breeze (session-based `web` guard)** dengan proteksi login rate limiting (`LoginRequest`), password hashing (`bcrypt`), dan CSRF token standar web Inertia.
- **Temuan Keamanan Kritis pada Registrasi Publik (`routes/auth.php`)**:
  - Route `GET /register` dan `POST /register` masih aktif terbuka untuk umum.
  - `RegisteredUserController::store()` membuat akun baru di tabel `users` tanpa penetapan role yang jelas dan langsung melakukan `Auth::login($user)` lalu redirect ke `/dashboard`.
  - Untuk aplikasi internal toko (`Admin` dan `Kasir`), **registrasi publik wajib dimatikan** atau dipisahkan secara tegas agar pihak luar tidak dapat membuat akun dan langsung masuk ke dashboard operasional.

### 5.2 Status Authorization (Role `admin` & `kasir`)
- **Belum ada implementasi authorization sama sekali** di seluruh lapisan:
  1. **Database**: Tabel `users` tidak memiliki kolom `role`.
  2. **Middleware**: Belum ada middleware pembatasan role.
  3. **Policy / Gate**: Belum ada Policy untuk `Produk`, `Pelanggan`, `Transaksi`, `Cabang`, `Kategori`, `Laporan`, atau `User`.
  4. **Inertia Shared Props**: `HandleInertiaRequests::share()` belum mengirim role maupun map permission (`can`) ke frontend, serta mengekspos seluruh object `$request->user()` mentah.

---

## 6. Kesenjangan Fitur (Tersedia vs Belum Tersedia)

| Area Fitur | Status Saat Ini | Kesenjangan terhadap `AGENTS.md` |
|---|---|---|
| Autentikasi Login/Logout/Reset | Tersedia (Breeze) | Route `/register` publik berisiko; halaman Profile (`Profile/Edit.vue`) rusak |
| Role & Hak Akses (`admin`/`kasir`) | Belum ada | Harus ditegakkan di Middleware, Policy/Gate, Controller, FormRequest, dan UI |
| Storefront Publik (`/`, Shop, Detail Produk) | Belum ada | `/` masih menampilkan `Welcome.vue` default Laravel dengan aset eksternal |
| Master Kategori & Produk | Belum ada | Belum ada tabel, model, CRUD, validasi, upload gambar, & proteksi 403 kasir |
| Manajemen Pelanggan | Parsial (Model + Tabel) | Trigger `tbl_pelanggan` error karena `tbl_log` tidak ada; belum ada Controller & UI |
| Manajemen Cabang | Parsial (Stub Model + Tabel) | Model `Cabang` kosong; tidak ada relasi ke transaksi/user; belum ada Controller & UI |
| Transaksi / POS Kasir | Parsial (Model `Transaksi` + Tabel) | `DetailTransaksi` model kosong; belum ada Service `DB::transaction`, validasi backend, maupun UI POS |
| Laporan Penjualan & Pelanggan | Belum ada (hanya Views SQL) | Belum ada Controller/Filter/UI; pembatasan akses kasir vs admin belum ada |
| Manajemen Pengguna & Role | Belum ada | Belum ada CRUD staf Admin/Kasir oleh Admin |
| Automated Testing | Gagal (`pdo_sqlite` missing) | 23 dari 25 test bawaan gagal; belum ada test RBAC & transaksi |

---

## 7. Potensi Masalah & Risiko Teknis

1. **Test Suite Tidak Bisa Jalan (`QueryException: could not find driver`)**: `php artisan test` gagal pada 23/25 test karena `pdo_sqlite` belum aktif di `php.ini` Laragon sementara `phpunit.xml` memaksa `sqlite :memory:`.
2. **Halaman Profile Error Saat Dibuka**: `ProfileController::edit` merender `Profile/Edit`, tetapi `resources/js/Pages/Profile/Edit.vue` saat ini berukuran 0 byte dan partisialnya terhapus.
3. **Insert/Update Pelanggan Akan Error Runtime**: Trigger `trg_insert_pelanggan` dan `trg_update_pelanggan` menulis ke `tbl_log` yang belum eksis di database.
4. **`Transaksi::detail()` Akan Error Runtime**: `App\Models\DetailTransaksi` masih kosong dan belum memetakan `$table = 'tbl_detail_transaksi'`, `$primaryKey = 'id_detail'`, dan `$timestamps = false`.
5. **Dependensi Tailwind Tidak Konsisten**: `@tailwindcss/vite` (`^4.0.0`) ada di `package.json` berdampingan dengan `tailwindcss` (`^3.4.19`). Jika ada developer yang mengira proyek memakai Tailwind v4, konfigurasi CSS akan rusak.
6. **Inkonsistensi Penamaan & Placeholder Kosong**: File 0-byte seperti `Cabang/Component/PelangganForm.vue`, `DataRangePicker.vue`, `ImageUploud.vue`, `Cabang/index.vue`, `Dashboard/index.vue` vs `Dashboard.vue`, dan `BaseButton.vue` vs `PrimaryButton.vue` akan menyebabkan kebingungan atau import rusak bila tidak dirapikan sebelum fase implementasi.
7. **Ketiadaan `APP_LOCALE=id` / Timezone**: `config/app.php` masih `timezone => 'UTC'` dan `APP_LOCALE=en`. Untuk aplikasi POS/toko di Indonesia, pencatatan tanggal transaksi berisiko selisih hari (terutama antara jam 00:00–07:00 WIB) bila timezone masih UTC.

