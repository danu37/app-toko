# 🛒 Sistem Informasi Penjualan Toko (POS)

Aplikasi web Sistem Informasi Penjualan Toko (*Point of Sales*) berskala menengah yang dikembangkan untuk mengelola transaksi, pelanggan, dan inventaris. Proyek ini dibangun menggunakan **Laravel** dipadukan dengan **Vue.js** secara *Single Page Application* (SPA), serta menitikberatkan pada optimasi performa dan keamanan data melalui implementasi pemrograman basis data lanjutan (*Advanced SQL*).

## 🛠️ Teknologi yang Digunakan

* **Backend:** Framework Laravel (PHP)
* **Frontend:** Vue.js, Inertia.js, Tailwind CSS
* **Database:** MariaDB / MySQL (XAMPP)
* **Arsitektur:** MVC (Model-View-Controller) dengan pemrosesan logika bisnis hibrida di sisi *Database Engine*.

---

## ✨ Fitur Utama Aplikasi

1. **Manajemen Master Data (CRUD Terpusat)**
   * Pengelolaan data **Kategori** dan **Produk** (`tbl_kategori`, `tbl_produk`).
   * Manajemen data **Pelanggan** (`tbl_pelanggan`).
2. **Sistem Pemrosesan Transaksi**
   * Antarmuka kasir/transaksi yang reaktif dengan Vue.js.
   * Proses perekaman transaksi utama (`tbl_transaksi`) dan rincian item (`tbl_detail_transaksi`) secara bersamaan.
3. **Analitik & Laporan Lanjutan**
   * Laporan riwayat transaksi pelanggan secara mendetail.
   * Analisis total pembelanjaan, peringkat transaksi tertinggi, dan identifikasi pelanggan pasif.
4. **Keamanan & Konsistensi Data**
   * Perlindungan dari kegagalan sistem saat proses simpan data menggunakan *Transaction Control Language* (TCL: `COMMIT` & `ROLLBACK`).
   * Sistem riwayat aktivitas otomatis (Audit Trail) tanpa membebani logika *Controller* aplikasi.

---

## 🗄️ Implementasi Database Lanjutan (Advanced SQL)

Berbeda dengan aplikasi CRUD biasa yang sepenuhnya bergantung pada ORM (Eloquent), sistem ini mendelegasikan beberapa tugas berat dan repetitif langsung ke *Database Engine* untuk performa maksimal:

* **Database Views:** Digunakan untuk memproduksi Laporan Penjualan terpadu. Aplikasi Laravel cukup memanggil View ini layaknya tabel biasa tanpa perlu melakukan instruksi *Multiple JOIN* berulang kali.
* **Subqueries & JOIN:** Diimplementasikan untuk mengolah data kompleks, seperti mencari pelanggan yang belum pernah bertransaksi atau transaksi dengan nilai di atas rata-rata.
* **Stored Procedures:** Digunakan untuk merangkum proses *insert* transaksi berskala besar. *Controller* Laravel cukup memanggil procedure `CALL LaporanTransaksi(id)` sehingga proses jauh lebih ringkas.
* **Stored Functions:** Mengambil alih beban komputasi seperti `HitungTotal()`, `HitungDiskon()`, dan `HitungPajak()` secara mandiri di dalam database.
* **Database Triggers:** Bekerja di latar belakang untuk melakukan dua hal esensial:
  1. **Validasi (*Before Insert*):** Menolak secara otomatis jika ada transaksi bernilai negatif masuk ke sistem.
  2. **Audit Log (*After Insert/Update*):** Mencatat otomatis setiap perubahan nama pelanggan atau transaksi baru ke dalam tabel `tbl_log` tanpa perlu ditulis melalui Laravel.

---

## ⚙️ Cara Instalasi & Menjalankan Proyek

Pastikan Anda sudah menginstal PHP, Composer, Node.js, dan XAMPP (MariaDB/MySQL).

1. **Clone Repositori**
   ```bash
   git clone [https://github.com/username/app-toko.git](https://github.com/username/app-toko.git)
   cd app-toko