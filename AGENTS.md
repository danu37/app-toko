# AGENTS.md --- Aplikasi Toko

Dokumen ini menjadi pedoman utama AI coding agent/developer saat
membangun dan mengembangkan aplikasi toko. Baca dokumen ini sebelum
mengubah kode. Ikuti struktur dan aturan yang sudah ada di repository;
jangan mengganti stack atau merombak bagian yang berjalan tanpa alasan
teknis yang jelas.

## 1. Ringkasan Proyek

Bangun aplikasi toko berbasis web dengan tampilan storefront modern
seperti referensi gambar yang diberikan: bersih, minimalis, berorientasi
produk, memiliki hero/banner promosi, kategori, dan daftar produk
unggulan.

Aplikasi memiliki dua role:

-   **Admin**: dapat mengakses seluruh fitur dan mengelola seluruh data.
-   **Kasir**: dapat menambahkan barang/produk dan pelanggan serta
    melayani transaksi. Kasir **tidak boleh mengedit atau menghapus
    barang/produk**.

Aplikasi harus responsif, mudah digunakan, konsisten, dan aman. Terapkan
pembatasan role di backend, bukan hanya dengan menyembunyikan tombol di
frontend.

## 2. Teknologi dan Batasan

Gunakan stack proyek yang sudah ditentukan:

-   Backend: Laravel.
-   Frontend: Vue 3.
-   Integrasi halaman: Inertia.js.
-   Styling: gunakan sistem CSS/Tailwind yang sudah terpasang di
    repository.
-   Database: MySQL/MariaDB.

Ketentuan: 1. Periksa `composer.json`, `package.json`, konfigurasi, dan
struktur repository sebelum memasang dependency atau mengubah versi. 2.
Jangan mengganti versi Laravel, Vue, Inertia, PHP, atau dependency utama
tanpa kebutuhan dan persetujuan. 3. Gunakan pola Laravel dan Vue yang
sudah dipakai proyek. Jangan mencampur pola Options API dan Composition
API tanpa alasan. 4. Jangan menambahkan library hanya untuk kebutuhan
yang dapat diselesaikan dengan fitur yang sudah tersedia. 5. Jangan
menaruh query database, aturan bisnis, atau pemeriksaan permission di
komponen Vue.

## 3. Referensi Desain UI

Gunakan gambar referensi sebagai arah visual, bukan sebagai halaman yang
harus disalin persis.

### Karakter visual

-   Minimalis, modern, elegan, dan berorientasi pada produk.
-   Latar dominan putih atau abu-abu sangat muda.
-   Teks utama hitam/abu-abu gelap dengan hierarki tipografi yang jelas.
-   Aksen warna digunakan seperlunya untuk tombol utama, label promo,
    dan status.
-   Foto produk harus menjadi fokus; gunakan rasio gambar dan ukuran
    kartu yang konsisten.
-   Garis batas tipis, radius sudut secukupnya, dan bayangan sangat
    halus.
-   Beri ruang kosong/whitespace yang cukup. Hindari tampilan terlalu
    padat.
-   Hindari terlalu banyak card bertumpuk, gradient berlebihan, efek
    neon, dan animasi yang mengganggu.

### Komposisi halaman storefront

1.  **Navbar**
    -   Logo/nama toko di sisi kiri.
    -   Navigasi seperti Home, Shop, kategori, dan informasi toko.
    -   Aksi pencarian dan akun di sisi kanan.
    -   Pada layar kecil, ubah navigasi menjadi menu mobile yang mudah
        digunakan.
2.  **Hero/banner**
    -   Area promosi utama dengan judul, deskripsi singkat, gambar
        produk/model, dan tombol CTA.
    -   Gunakan gambar yang tersedia di project atau placeholder yang
        layak. Jangan mengarang URL gambar eksternal.
    -   Pastikan teks tetap terbaca dan layout tidak bertabrakan dengan
        gambar.
3.  **Kategori**
    -   Tampilkan kategori dalam grid atau carousel horizontal.
    -   Setiap item memiliki gambar/thumbnail, nama kategori, dan area
        klik yang jelas.
4.  **Produk unggulan**
    -   Grid produk dengan gambar, nama, harga, dan informasi ringkas.
    -   Tombol aksi ditampilkan sesuai konteks dan permission pengguna.
    -   Sediakan state loading, kosong, dan error.
5.  **Footer**
    -   Informasi toko, tautan navigasi, dan informasi kontak jika
        datanya tersedia.

### Halaman internal admin dan kasir

Halaman internal harus tetap memakai identitas visual yang sama, tetapi
mengutamakan efisiensi kerja:

-   Sidebar atau navigasi yang jelas.
-   Header dengan nama halaman dan identitas pengguna.
-   Tabel yang mudah dibaca, pencarian, filter, pagination, dan
    konfirmasi aksi.
-   Form memiliki label, validasi, pesan error, dan tombol simpan/batal.
-   Halaman transaksi harus memprioritaskan kecepatan input dan
    keterbacaan total belanja.
-   Jangan memaksakan layout storefront ke halaman operasional kasir.

### Responsivitas dan aksesibilitas

-   Pastikan layout bekerja pada desktop, tablet, dan ponsel.
-   Jangan sampai tabel/form menyebabkan overflow horizontal tanpa
    penanganan.
-   Gunakan label input yang jelas, kontras warna cukup, focus state,
    dan tombol dengan teks/aksesibilitas yang baik.
-   Jangan mengandalkan warna saja untuk menyampaikan status.

## 4. Role dan Matriks Hak Akses

### Admin

Admin memiliki akses penuh untuk: - Melihat dashboard dan seluruh
ringkasan. - Melihat, menambahkan, mengedit, dan menghapus
barang/produk. - Melihat, menambahkan, mengedit, dan menghapus
pelanggan. - Melihat dan mengelola transaksi sesuai aturan bisnis. -
Melihat laporan penjualan dan laporan lainnya. - Mengelola kategori,
cabang, pengguna, role, dan pengaturan aplikasi apabila fitur tersebut
tersedia. - Mengakses seluruh halaman dan data yang diizinkan oleh
sistem.

### Kasir

Kasir hanya memiliki akses operasional berikut: - Melihat daftar
barang/produk. - **Menambahkan barang/produk baru.** - Tidak boleh
mengedit barang/produk yang sudah ada. - Tidak boleh menghapus
barang/produk. - Melihat daftar pelanggan. - Menambahkan pelanggan
baru. - Melihat data yang diperlukan untuk melayani transaksi. -
Membuat/melayani transaksi dan menambahkan detail barang ke transaksi. -
Melihat transaksi yang diperlukan untuk operasional, sesuai kebijakan
aplikasi.

Kasir tidak boleh: - Mengakses halaman pengelolaan pengguna/role. -
Mengubah role atau permission. - Mengedit atau menghapus
barang/produk. - Menghapus data pelanggan atau mengedit pelanggan
kecuali kelak ada persetujuan requirement baru. - Mengakses pengaturan
atau tindakan administratif yang tidak disebutkan sebagai hak kasir.

### Tabel permission

  Fitur                      Admin   Kasir
  -------------------------- ------- ---------------------------------------------
  Dashboard                  Akses   Akses operasional
  Lihat barang/produk        Ya      Ya
  Tambah barang/produk       Ya      Ya
  Edit barang/produk         Ya      Tidak
  Hapus barang/produk        Ya      Tidak
  Lihat pelanggan            Ya      Ya
  Tambah pelanggan           Ya      Ya
  Edit pelanggan             Ya      Tidak
  Hapus pelanggan            Ya      Tidak
  Buat/lakukan transaksi     Ya      Ya
  Lihat laporan              Ya      Sesuai kebutuhan operasional; default tidak
  Kelola pengguna dan role   Ya      Tidak
  Pengaturan aplikasi        Ya      Tidak

Jika requirement transaksi belum menjelaskan apakah kasir boleh
membatalkan/menghapus transaksi, **jangan berikan permission tersebut
kepada kasir secara default**. Admin dapat mengelolanya sesuai aturan
bisnis yang disepakati.

## 5. Keamanan dan Implementasi Role

Keamanan role wajib diterapkan di beberapa lapisan:

1.  **Database/identitas**: setiap akun memiliki role yang valid,
    misalnya `admin` atau `kasir`.
2.  **Backend**: gunakan middleware, Policy, Gate, atau mekanisme
    authorization Laravel yang konsisten dengan project.
3.  **Controller/Request**: validasi dan otorisasi dilakukan sebelum
    proses perubahan data.
4.  **Frontend**: sembunyikan menu dan tombol yang tidak boleh digunakan
    role tersebut untuk memperjelas UI. Ini bukan pengganti
    authorization backend.
5.  **Endpoint**: pengguna kasir yang memanggil endpoint edit/delete
    secara langsung harus menerima HTTP 403.
6.  Jangan mempercayai role yang dikirim dari form, query string, atau
    local storage. Role harus berasal dari akun yang terautentikasi.
7.  Jangan hanya mengandalkan pengecekan di Vue seperti `v-if`; semua
    aksi sensitif harus diamankan di server.
8.  Gunakan validasi Form Request dan transaksi database untuk proses
    yang melibatkan beberapa perubahan data.

Contoh perilaku yang wajib diuji: - Kasir berhasil membuat produk. -
Kasir mendapat 403 saat mencoba mengubah produk melalui URL/API secara
langsung. - Kasir mendapat 403 saat mencoba menghapus produk melalui
URL/API secara langsung. - Admin berhasil membuat, mengubah, dan
menghapus produk. - Kasir berhasil membuat pelanggan dan transaksi. -
Kasir tidak dapat mengakses halaman pengelolaan pengguna.

## 6. Struktur Frontend Vue yang Disarankan

Sesuaikan dengan struktur aktual repository. Jika proyek masih
menggunakan struktur standar Laravel + Inertia, gunakan pola berikut dan
buat file secara bertahap sesuai kebutuhan.

``` text
resources/js/
├── Components/
│   ├── UI/
│   │   ├── BaseButton.vue
│   │   ├── BaseInput.vue
│   │   ├── BaseSelect.vue
│   │   ├── BaseBadge.vue
│   │   ├── BaseModal.vue
│   │   └── BasePagination.vue
│   ├── Forms/
│   ├── Tables/
│   └── Feedback/
├── Layouts/
│   ├── StoreLayout.vue
│   ├── AdminLayout.vue
│   ├── CashierLayout.vue
│   └── Components/
│       ├── AppHeader.vue
│       ├── AppSidebar.vue
│       └── AppFooter.vue
├── Pages/
│   ├── Store/
│   │   ├── Index.vue
│   │   ├── Shop.vue
│   │   └── ProductDetail.vue
│   ├── Dashboard/
│   │   └── Index.vue
│   ├── Produk/
│   │   ├── Index.vue
│   │   ├── Create.vue
│   │   ├── Edit.vue
│   │   └── Components/
│   ├── Pelanggan/
│   │   ├── Index.vue
│   │   ├── Create.vue
│   │   └── Components/
│   ├── Transaksi/
│   │   ├── Index.vue
│   │   ├── Create.vue
│   │   ├── Show.vue
│   │   └── Components/
│   ├── Laporan/
│   └── Pengguna/
├── Composables/
│   ├── useCurrency.js
│   ├── useDate.js
│   └── usePermissions.js
├── Utils/
│   ├── formatCurrency.js
│   └── formatDate.js
└── app.js
```

Aturan struktur: - `Pages`: halaman Inertia yang terhubung ke
route/controller. - `Components/UI`: komponen generik yang dapat dipakai
lintas fitur. - `Pages/<Fitur>/Components`: komponen khusus satu
fitur. - `Layouts`: layout berdasarkan konteks storefront, admin, dan
kasir. - `Composables`: logika Vue yang dapat digunakan ulang. - Jangan
membuat banyak file kosong hanya untuk memenuhi struktur. - Jangan
membuat duplikasi komponen dengan fungsi yang sama.

## 7. Struktur Backend Laravel yang Disarankan

Ikuti struktur yang sudah ada. Secara umum:

``` text
app/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   ├── Requests/
│   └── Resources/
├── Models/
├── Policies/
├── Services/
└── Providers/
```

-   Controller menangani HTTP request dan response, bukan menampung
    seluruh logika bisnis.
-   Form Request menangani validasi input.
-   Policy/Gate atau middleware menangani authorization.
-   Service digunakan untuk proses bisnis yang kompleks atau dipakai
    lintas controller.
-   Model menangani relasi dan perilaku data yang relevan.
-   Gunakan eager loading untuk mencegah N+1 query.
-   Gunakan pagination untuk daftar data yang berpotensi besar.

## 8. Catatan Database Proyek

Database awal `db-toko` yang diberikan memiliki tabel utama:

-   `tbl_cabang`
-   `tbl_pelanggan`
-   `tbl_transaksi`
-   `tbl_detail_transaksi`
-   `users`
-   tabel bawaan Laravel seperti `migrations`, `sessions`, `jobs`, dan
    lainnya.

Relasi yang tersedia: - `tbl_pelanggan` memiliki banyak
`tbl_transaksi`. - `tbl_transaksi` memiliki banyak
`tbl_detail_transaksi`. - `tbl_detail_transaksi` menyimpan
`nama_produk`, `jumlah`, dan `harga` secara langsung. - `tbl_cabang`
belum memiliki relasi yang terdefinisi pada skema awal.

Database awal belum memiliki tabel master produk/kategori. Karena
requirement aplikasi membutuhkan fitur menambahkan dan mengelola barang,
lakukan pemeriksaan skema sebelum membuat fitur tersebut.

Jika produk akan menjadi data master yang dapat dipilih berulang kali
dalam transaksi, diskusikan/terapkan migrasi tabel produk (dan kategori
bila diperlukan) serta relasi detail transaksi ke produk. Jangan
mengasumsikan tabel atau kolom tersebut sudah tersedia. Buat migrasi
baru; jangan menghapus atau merusak data transaksi lama. Jika tetap
memakai struktur awal, jelaskan konsekuensi bahwa nama produk hanya
disimpan sebagai teks pada detail transaksi.

Tabel custom awal menggunakan prefix `tbl_` dan tidak semuanya memiliki
kolom timestamps. Saat membuat model Laravel untuk tabel tersebut,
tentukan `$table`, `$primaryKey`, dan `$timestamps` sesuai skema aktual.
Jangan mengasumsikan semua tabel memiliki `created_at` dan `updated_at`.

Database awal juga memiliki procedure, function, trigger, dan view.
Periksa definisi serta dependensinya sebelum mengubahnya. Trigger yang
ada merujuk ke `tbl_log`; pastikan tabel tersebut memang tersedia
sebelum trigger dijalankan. Jangan menghapus objek database yang sudah
ada tanpa alasan dan persetujuan.

## 9. Alur Bisnis Utama

### Barang/produk

-   Admin dapat melihat, menambahkan, mengedit, dan menghapus produk.
-   Kasir dapat melihat dan menambahkan produk.
-   Kasir tidak boleh mengubah atau menghapus produk yang sudah
    tersimpan.
-   Validasi nama, harga, stok, kategori, dan field lain berdasarkan
    skema yang benar-benar digunakan.
-   Jangan menganggap stok otomatis berkurang sebelum mekanisme stok dan
    aturan transaksi disepakati.

### Pelanggan

-   Admin dapat mengelola seluruh data pelanggan.
-   Kasir dapat melihat dan menambahkan pelanggan.
-   Validasi data pelanggan di backend.
-   Hindari membuat pelanggan duplikat jika ada aturan identifikasi yang
    disepakati.

### Transaksi

-   Kasir dapat membuat transaksi untuk melayani pelanggan.
-   Satu transaksi dapat memiliki beberapa detail barang.
-   Hitung subtotal dan total di backend; jangan mempercayai nilai total
    yang dikirim browser.
-   Validasi harga dan barang dari database.
-   Gunakan database transaction (`DB::transaction`) agar header
    transaksi dan detailnya tersimpan secara konsisten.
-   Simpan informasi tanggal dan relasi pelanggan sesuai skema.
-   Tampilkan ringkasan barang, jumlah, harga, subtotal, dan total
    sebelum transaksi disimpan.
-   Aturan pembatalan, pengembalian, diskon, pajak, dan perubahan
    transaksi harus mengikuti requirement yang telah disepakati; jangan
    membuat aturan sendiri.

## 10. Standar Kualitas Kode

-   Gunakan nama class, method, variable, route, dan file yang
    konsisten.
-   Ikuti konvensi Laravel dan Vue.
-   Hindari controller atau komponen Vue yang terlalu besar.
-   Hindari duplikasi validasi dan logika bisnis.
-   Gunakan route name dan route helper Inertia yang konsisten.
-   Jangan hardcode data bisnis yang seharusnya berasal dari database.
-   Gunakan `v-for` dengan `:key` yang stabil.
-   Gunakan `useForm` Inertia untuk form jika sesuai pola project.
-   Tangani loading, validation error, empty state, dan server error.
-   Gunakan `htmlspecialchars`/escaping bawaan Vue untuk output; jangan
    gunakan `v-html` untuk input pengguna yang tidak disanitasi.
-   Jangan menyimpan password, token, atau secret di source control.
-   Jangan mengubah file `.env` atau konfigurasi deployment tanpa
    kebutuhan yang jelas.

## 11. Testing dan Verifikasi

Setiap fitur harus diverifikasi sebelum dianggap selesai.

### Backend

-   Jalankan test yang relevan.
-   Uji validasi input.
-   Uji authorization untuk admin dan kasir.
-   Uji endpoint yang ditolak dengan status 403.
-   Uji relasi dan penyimpanan transaksi.
-   Periksa query dan integritas data.

### Frontend

-   Periksa halaman pada desktop dan mobile.
-   Periksa tombol, form, modal, pencarian, filter, dan pagination.
-   Periksa tampilan untuk role admin dan kasir.
-   Pastikan tombol edit/hapus produk tidak tampil untuk kasir.
-   Pastikan error validasi dan feedback sukses terlihat jelas.

### Sebelum menyelesaikan tugas

1.  Ringkas file yang diubah.
2.  Jelaskan migration atau perubahan database yang diperlukan.
3.  Sebutkan perintah test/build yang dijalankan dan hasilnya.
4.  Sebutkan bagian yang belum diuji atau masih membutuhkan keputusan.
5.  Jangan mengklaim test berhasil jika belum benar-benar dijalankan.

## 12. Aturan Kerja AI Coding Agent

Sebelum mengerjakan task: 1. Baca `AGENTS.md` ini. 2. Periksa struktur
repository dan file terkait. 3. Periksa route, controller, model,
migration, middleware, dan komponen yang sudah ada. 4. Pahami alur data
sebelum mengubah kode. 5. Buat perubahan sekecil mungkin yang
menyelesaikan kebutuhan.

Saat mengerjakan: - Jangan menimpa implementasi yang sudah berjalan
tanpa memeriksa dampaknya. - Jangan menghapus data, migration, route,
atau fitur lama secara sembarangan. - Jangan membuat data dummy di
production. - Jangan mengubah requirement role tanpa instruksi. - Jika
requirement bertentangan dengan skema database, jelaskan konflik dan
ajukan perubahan yang paling aman. - Jika ada keputusan bisnis yang
belum jelas, tanyakan sebelum menerapkan perilaku yang berisiko. -
Prioritaskan keamanan, integritas transaksi, konsistensi UI, dan
kemudahan pemeliharaan.

**Prioritas utama proyek:** aplikasi toko yang fungsional dan aman, role
admin/kasir benar-benar ditegakkan di backend, serta UI storefront dan
halaman operasional yang konsisten dengan referensi desain.
