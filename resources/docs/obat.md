# Manajemen Obat

Modul **Obat** digunakan untuk mengelola data master obat yang tercatat di seluruh jaringan fasilitas kesehatan. Setiap obat memiliki kode unik, informasi produk, kategori VEN, dan metode stok (FEFO/FIFO/LIFO) yang menentukan cara batch dipilih saat transaksi.

## Kolom Data Obat

### Tab Umum

| Kolom           | Tipe    | Wajib | Keterangan                                          |
| --------------- | ------- | ----- | --------------------------------------------------- |
| **Kode Obat**       | String  | Ya    | Kode unik obat (contoh: `001`).                        |
| **Nama Obat**       | String  | Ya    | Nama dagang/obat (contoh: "Paracetamol").            |
| **Nama Generik**    | String  | Tidak | Nama bahan aktif/generik obat.                      |
| **Kategori**        | String  | Ya    | Kategori obat (contoh: Antibiotik, Analgesik).       |
| **Satuan**          | String  | Ya    | Satuan pengukuran (contoh: tablet, botol).           |
| **Kekuatan**        | String  | Tidak | Dosis (contoh: `500mg`, `10ml`).                       |
| **Bentuk Sediaan**  | Enum    | Ya    | Tablet, Kapsul, Sirup, Salep, Injeksi, Drop, dll.    |

### Tab Lanjutan

| Kolom           | Tipe    | Wajib | Keterangan                                          |
| --------------- | ------- | ----- | --------------------------------------------------- |
| **Produsen**        | String  | Tidak | Nama pabrik/produsen obat.                          |
| **Kemasan**         | String  | Tidak | Kemasan/volume (contoh: "strip 10 tablet").         |
| **Harga Satuan**    | Decimal | Tidak | Harga per satuan dalam Rupiah.                      |
| **VEN Kategori**    | Enum    | Tidak | `V` (Vital), `E` (Esensial), `N` (Non-Esensial).       |
| **Status**          | Enum    | Ya    | `Aktif` / `Nonaktif`.                                   |
| **Metode Stok**     | Enum    | Ya    | `FEFO`, `FIFO`, atau `LIFO`.                             |

## Penjelasan Metode Stok

| Metode | Keterangan                                                    |
| ------ | ------------------------------------------------------------- |
| **FEFO** | *First Expired, First Out* — batch dengan tanggal kadaluarsa terdekat dipakai lebih dulu. |
| **FIFO** | *First In, First Out* — batch yang diterima paling awal dipakai lebih dulu.            |
| **LIFO** | *Last In, First Out* — batch yang diterima paling akhir dipakai lebih dulu.            |

Pilihan metode stok ditentukan **per obat** dan berlaku untuk seluruh transaksi batch obat tersebut.

## Penjelasan Kategori VEN

| Kode | Keterangan | Contoh                       |
| ---- | ---------- | ---------------------------- |
| **V**    | Vital      | Antibiotik, Anti-Jamur       |
| **E**    | Esensial   | Analgesik, Antasida          |
| **N**    | Non-Esensial | Vitamin, Suplemen          |

## Langkah-Langkah: Melihat Daftar Obat

1. Buka menu **Master Data** → **Obat**.

![Halaman Daftar Obat](/screenshots/admin-obat.png)

2. Tabel menampilkan seluruh obat dengan kolom: Nama, Kekuatan, Bentuk Sediaan (badge), VEN (badge), Status (badge), Metode Stok (badge), dan Harga Satuan.
3. Gunakan ikon **action** (tiga titik) di kolom Aksi untuk mengedit atau menghapus obat.

## Langkah-Langkah: Menambah Obat

1. Pada halaman daftar obat, klik tombol **"Buat"** di pojok kanan atas.
2. Isi form pada **Tab Umum**:

![Form Tambah Obat](/screenshots/admin-obat-form.png)

   - **Kode Obat** — masukkan kode unik. Contoh: `001`.
   - **Nama Obat** — masukkan nama obat. Contoh: "Paracetamol 500mg".
   - **Nama Generik** — (opsional) bahan aktif. Contoh: "Paracetamol".
   - **Kategori** — kategori obat. Contoh: "Analgesik".
   - **Satuan** — satuan pengukuran. Contoh: "tablet".
   - **Kekuatan** — (opsional) dosis. Contoh: "500mg".
   - **Bentuk Sediaan** — pilih dari dropdown.

3. Beralih ke **Tab Lanjutan** untuk mengisi data tambahan:
   - **Produsen**, **Kemasan**, **Harga Satuan**.
   - **VEN Kategori** — pilih Vital, Esensial, atau Non-Esensial.
   - **Status** — pilih `Aktif` atau `Nonaktif`.
   - **Metode Stok** — pilih FEFO, FIFO, atau LIFO.

4. Klik **"Simpan"** untuk menyimpan obat.

## Langkah-Langkah: Mengedit Obat

1. Pada halaman daftar, klik ikon **action** (tiga titik) di kolom Aksi pada baris obat, lalu pilih **Edit**.

![Form Edit Obat](/screenshots/admin-obat-edit.png)

2. Form akan terbuka dengan data yang sudah ada. Ubah field yang diperlukan.
3. Klik **"Simpan"** untuk menyimpan perubahan.

## Langkah-Langkah: Menghapus Obat

1. Pada halaman daftar, klik ikon **action** (tiga titik) di kolom Aksi pada baris obat, lalu pilih **Hapus**.
2. Konfirmasi penghapusan pada dialog yang muncul.

> **Catatan:** Obat yang sudah memiliki batch stok atau transaksi terkait tidak bisa dihapus. Ubah status menjadi **Nonaktif** sebagai gantinya.

## Penggunaan Data Obat dalam Modul Lain

| Modul               | Penggunaan Data Obat                                          |
| ------------------- | ------------------------------------------------------------- |
| **Penerimaan Stok** | Setiap obat diterima dalam batch dengan nomor batch dan tanggal kadaluarsa. |
| **Stok Gudang**     | Total stok obat di gudang pusat.                              |
| **Stok Faskes**     | Total stok obat per fasilitas.                                |
| **Distribusi**      | Obat dari gudang didistribusikan ke fasilitas.                |
| **Pemakaian**       | Pencatatan pemakaian obat per batch di fasilitas.             |
| **FEFO Engine**     | Metode stok menentukan urutan batch yang dipakai.             |
| **Prediksi AI**     | Model prediksi menganalisis kebutuhan obat per jenis.         |
