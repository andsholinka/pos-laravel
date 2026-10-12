# POS UMKM: Aplikasi Kasir untuk Toko Kecil

POS UMKM adalah aplikasi kasir (point of sale) berbasis web untuk warung, toko kelontong, dan minimarket kecil. Kasir bisa melayani pembeli dengan cepat lewat layar kasir yang ramah keyboard dan barcode scanner. Pemilik toko bisa memantau omzet, laba kotor, produk terlaris, dan stok yang menipis dari dashboard, lalu mengunduh laporan penjualan dalam format PDF atau CSV (Excel). Aplikasi dibangun dengan Laravel 13, Blade, Tailwind CSS, Alpine.js, dan Chart.js, dan sudah dilengkapi data demo sekitar 2,5 bulan transaksi.

## Fitur Utama

- **Layar Kasir (POS)**: pencarian instan nama/SKU/barcode, filter kategori, keranjang dengan stepper jumlah, diskon nominal atau persen, pajak otomatis, pembayaran Tunai/QRIS/Transfer, tombol uang cepat, perhitungan kembalian, dan shortcut keyboard. Tampilan menyesuaikan layar HP (keranjang jadi bottom sheet).
- **Struk Thermal**: struk 58 mm / 80 mm siap cetak dengan CSS khusus printer thermal, bisa dicetak ulang dari riwayat transaksi.
- **Dashboard**: penjualan dan transaksi hari ini (dengan tren dibanding kemarin), grafik penjualan 7/30 hari, produk terlaris, komposisi metode pembayaran, daftar stok menipis, dan transaksi terbaru.
- **Laporan Penjualan**: filter tanggal dan rentang cepat, pengelompokan harian/bulanan, ringkasan omzet, penjualan bersih, HPP, laba kotor dan margin, grafik, tabel rincian dengan total, produk terlaris, serta export **PDF** dan **CSV** (siap dibuka di Excel).
- **Produk & Kategori**: CRUD produk dengan foto, SKU, barcode, harga beli/jual, satuan, dan stok minimum. Kategori berwarna. Produk dihapus secara soft delete agar riwayat tetap utuh.
- **Manajemen Stok**: barang masuk (dengan update harga beli), penyesuaian stok (stok opname, rusak, kadaluarsa, dll.), dan kartu stok lengkap dengan stok sebelum/sesudah untuk setiap pergerakan.
- **Riwayat Transaksi**: filter per tanggal, kasir, metode, dan status. Admin dapat membatalkan (void) transaksi dan stok otomatis dikembalikan.
- **Pengguna & Hak Akses**: dua peran (Admin dan Kasir), aktif/nonaktifkan akun, kasir hanya melihat transaksinya sendiri.
- **Pengaturan Toko**: nama, alamat, telepon, logo, persentase pajak, ukuran kertas struk, dan catatan kaki struk, dengan pratinjau struk langsung.
- **Antarmuka Bahasa Indonesia**: seluruh teks, pesan validasi, format Rupiah (`Rp 15.000`), tanggal, dan halaman error dalam Bahasa Indonesia.

## Tech Stack

| Lapisan | Teknologi |
|---|---|
| Backend | Laravel 13, PHP 8.4 |
| Tampilan | Blade, Tailwind CSS 3, Alpine.js 3 |
| Grafik | Chart.js 4 |
| PDF | barryvdh/laravel-dompdf (DomPDF) |
| Build | Vite |
| Database | SQLite (default) atau MySQL/MariaDB |
| Autentikasi | Laravel Breeze (Blade), disesuaikan |

## Screenshot

Simpan tangkapan layar di folder `docs/screenshots/` dengan nama berikut:

- `docs/screenshots/login.png`: halaman login dengan akun demo
- `docs/screenshots/dashboard.png`: dashboard admin
- `docs/screenshots/kasir.png`: layar kasir
- `docs/screenshots/pembayaran.png`: modal pembayaran
- `docs/screenshots/struk.png`: struk 58 mm
- `docs/screenshots/laporan.png`: laporan penjualan
- `docs/screenshots/produk.png`: daftar produk

> Catatan: gambar belum disertakan. Jalankan aplikasi, ambil tangkapan layar, lalu simpan dengan nama di atas.

## Akun Demo

Semua akun memakai kata sandi `password`. Di halaman login tersedia tombol "Akun Demo" untuk mengisi otomatis (atur dengan `POS_DEMO_ACCOUNTS` di `.env`).

| Peran | Nama | Email | Keterangan |
|---|---|---|---|
| Admin | Andi Pratama | `admin@pos.test` | Akses penuh, masuk ke Dashboard |
| Kasir | Siti Rahmawati | `kasir@pos.test` | Masuk langsung ke layar Kasir |
| Kasir | Budi Santoso | `kasir2@pos.test` | Kasir kedua |
| Kasir | Dewi Lestari | `dewi@pos.test` | Akun nonaktif (contoh login ditolak) |

## Instalasi

Prasyarat:

- PHP 8.3 atau lebih baru dengan ekstensi `pdo_sqlite` (atau `pdo_mysql`), `mbstring`, `openssl`, `fileinfo`, `gd`, `intl`, `zip`, `curl`
- Composer 2
- Node.js 20+ dan npm

Langkah:

```bash
git clone <url-repository> pos-umkm
cd pos-umkm

composer install
cp .env.example .env
php artisan key:generate

# Buat file database SQLite (Windows PowerShell: New-Item database/database.sqlite)
touch database/database.sqlite

# Buat tabel dan isi data demo (produk, pengguna, ~2.000 transaksi)
php artisan migrate --seed

# Tautan folder upload (foto produk, logo toko)
php artisan storage:link

npm install
npm run build

php artisan serve
```

Buka `http://127.0.0.1:8000` lalu login dengan salah satu akun demo. Untuk mengulang data demo dari awal, jalankan `php artisan migrate:fresh --seed`. Selama pengembangan, `npm run dev` bisa dipakai untuk hot reload aset.

## Menggunakan MySQL

1. Buat database, misalnya `pos_umkm`, dengan collation `utf8mb4_unicode_ci`.
2. Ubah bagian database di `.env`:

   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=pos_umkm
   DB_USERNAME=root
   DB_PASSWORD=
   ```

3. Jalankan `php artisan migrate --seed`.

Semua query laporan memakai SQL yang kompatibel dengan SQLite dan MySQL (`DATE(created_at)`), jadi tidak ada perubahan kode yang diperlukan. Pastikan `APP_TIMEZONE=Asia/Jakarta` agar pengelompokan per hari sesuai jam lokal.

## Hak Akses

| Fitur | Admin | Kasir |
|---|:---:|:---:|
| Layar kasir & checkout | ✓ | ✓ |
| Cetak struk | ✓ | ✓ (transaksi sendiri) |
| Riwayat transaksi | ✓ (semua) | ✓ (transaksi sendiri) |
| Lihat daftar & detail produk | ✓ | ✓ (tanpa harga beli/margin) |
| Tambah/ubah/hapus produk & kategori | ✓ | ✗ |
| Barang masuk & penyesuaian stok | ✓ | ✗ |
| Batalkan (void) transaksi | ✓ | ✗ |
| Dashboard & laporan (PDF/CSV) | ✓ | ✗ |
| Kelola pengguna | ✓ | ✗ |
| Pengaturan toko | ✓ | ✗ |
| Ubah profil & kata sandi sendiri | ✓ | ✓ |

## Shortcut Kasir

| Tombol | Fungsi |
|---|---|
| `F2` | Fokus ke kolom pencarian / scan barcode |
| `F4` | Fokus ke kolom diskon |
| `F8` | Kosongkan keranjang |
| `F9` | Buka pembayaran |
| `Enter` | Di pencarian: tambahkan produk (barcode/SKU cocok atau produk yang disorot). Di modal pembayaran: proses pembayaran. Setelah sukses: transaksi baru |
| `↑` / `↓` | Pindah sorotan produk di hasil pencarian |
| `Esc` | Tutup modal yang terbuka, atau kosongkan pencarian |

Barcode scanner USB bekerja seperti keyboard (mengetik kode lalu Enter), jadi langsung didukung tanpa konfigurasi.

## Arsitektur & Catatan Teknis

- **CheckoutService**: seluruh proses checkout berjalan di dalam satu `DB::transaction`. Harga dan stok selalu dihitung ulang dari database (harga dari browser tidak dipercaya), lalu nama, SKU, harga jual, dan harga beli disalin ke `sale_items` sebagai snapshot agar laporan lama tidak berubah ketika harga produk diubah.
- **Row lock**: produk yang dibeli dikunci dengan `lockForUpdate()` sehingga dua kasir tidak bisa menjual stok terakhir yang sama. Di SQLite (yang mengabaikan `lockForUpdate()`), koneksi memakai `transaction_mode` `IMMEDIATE` dan `busy_timeout` 5 detik, sehingga checkout yang bersamaan mengantre, bukan gagal dengan "database is locked". Void transaksi juga berjalan dalam transaksi dan mengembalikan stok.
- **Nomor invoice**: format `INV-YYYYMMDD-NNNN`, urut per hari, dihitung di dalam transaksi dengan lock, dijaga unique index, dan dicoba ulang hingga 3 kali bila terjadi bentrok.
- **Ledger stok**: stok produk tidak pernah diubah langsung. Semua perubahan (stok awal, barang masuk, penjualan, penyesuaian, void) lewat `StockService`, yang mencatat baris `stock_movements` berisi jumlah, stok sebelum, dan stok sesudah, sehingga kartu stok selalu bisa diaudit.
- **Uang sebagai integer**: semua nominal disimpan sebagai bilangan bulat Rupiah untuk menghindari selisih pembulatan float.
- **Laporan**: `SalesReportService` mengagregasi penjualan per hari dengan `DATE(created_at)` (berjalan di SQLite dan MySQL), mengisi hari kosong dengan nol, dan menggabungkan per bulan di PHP. Laba kotor = (subtotal − diskon) − HPP; pajak tidak dihitung sebagai pendapatan; transaksi void tidak dihitung. CSV memakai pemisah `;` dan BOM UTF-8 agar langsung rapi di Excel berbahasa Indonesia.
- **Hak akses**: middleware `role:admin` melindungi seluruh rute admin, policy (`SalePolicy`, `UserPolicy`) mengatur aturan per data seperti "kasir hanya melihat transaksinya sendiri" dan "admin tidak bisa menonaktifkan dirinya sendiri". Akun nonaktif langsung dikeluarkan dari sesi.
- **Tanpa N+1 query**: setiap halaman memuat relasi dengan eager loading, dan `Model::preventLazyLoading()` aktif di luar production sehingga lazy loading langsung ketahuan saat pengembangan.
- **Struk thermal**: halaman struk berdiri sendiri dengan CSS inline, `@page` sesuai lebar kertas (58/80 mm), font monospace, dan elemen non-cetak disembunyikan lewat `@media print`.
- **Aksesibilitas**: skip link, label pada setiap input, `aria-label` pada tombol ikon, focus ring yang terlihat, dan status yang tidak hanya mengandalkan warna.

## Struktur Folder

```text
app/
├── Enums/                 # Role, PaymentMethod, SaleStatus, StockMovementType, DiscountType
├── Http/
│   ├── Controllers/       # Dashboard, Pos, Sale, Product, Category, StockMovement, Report, User, StoreSetting
│   ├── Middleware/        # EnsureUserHasRole, EnsureUserIsActive
│   └── Requests/          # Form Request validasi (pesan Bahasa Indonesia)
├── Models/                # User, Product, Category, Sale, SaleItem, StockMovement, StoreSetting
├── Policies/              # SalePolicy, UserPolicy
├── Services/              # CheckoutService, StockService, SalesReportService
└── Support/helpers.php    # rupiah(), percent()
database/
├── migrations/
└── seeders/               # data demo toko, produk, dan riwayat penjualan
lang/id/                   # terjemahan validasi, auth, pagination
resources/
├── css/app.css            # komponen Tailwind (.btn, .card, .form-input, ...)
├── js/                    # app.js, pos.js (layar kasir), charts.js (grafik)
└── views/                 # layouts, components, pos, sales, products, reports, errors, ...
routes/web.php
```

## Lisensi

Proyek ini dirilis di bawah [lisensi MIT](https://opensource.org/licenses/MIT).
