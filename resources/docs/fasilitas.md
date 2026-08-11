# Fasilitas Kesehatan (Puskesmas & Pustu)

Modul **Fasilitas Kesehatan** mengatur data induk fasilitas layanan kesehatan yang menjadi dasar hierarki dalam sistem RUANG OBAT: **Puskesmas** (induk) dan **Pustu** (cabang/assistensi). Semua aktivitas distribusi, permintaan, dan pencatatan stok terikat pada data fasilitas ini.

## Struktur Hierarki

```text
Dinas Kesehatan
    └── Puskesmas
            └── Pustu (Puskesmas Pembantu / UPT)
```

Setiap Pustu harus memiliki satu Puskesmas Induk. Data ini menentukan alur permintaan obat dari Pustu menuju Puskesmas sebelum didistribusikan.

## Kolom Fasilitas Kesehatan

| Kolom              | Tipe    | Keterangan                                            |
| ------------------ | ------- | ----------------------------------------------------- |
| **Kode**               | String  | Kode unik fasilitas (contoh: `PKM001`, `PSU001`).       |
| **Nama**               | String  | Nama lengkap fasilitas kesehatan.                     |
| **Tipe**               | Enum    | `Puskesmas` atau `Pustu`.                                  |
| **Puskesmas Induk**    | Fasilitas | (Hanya untuk Pustu) Fasilitas induk yang menaungi.    |
| **PIC**                | String  | Nama penanggung jawab (opsional).                     |
| **Kontak PIC**         | String  | Nomor HP/WA PIC (opsional).                           |
| **Telepon**            | String  | Nomor telepon kantor fasilitas (opsional).            |
| **Kepala Fasilitas**   | String  | Nama kepala fasilitas (opsional).                     |
| **Status**             | Enum    | `Aktif` — dapat digunakan. `Nonaktif` — tidak muncul di pilihan form transaksi. |

## Langkah-Langkah: Melihat Daftar Fasilitas

1. Buka menu **Master Data** → **Fasilitas Kesehatan**.

![Halaman Daftar Fasilitas](/screenshots/admin-fasilitas.png)

2. Tabel akan menampilkan seluruh fasilitas yang terdaftar beserta informasi singkat:
   - **Kode** dan **Nama** fasilitas.
   - **Tipe** — Puskesmas (hijau) atau Pustu (biru).
   - **Puskesmas Induk** — hanya ditampilkan untuk Pustu.
   - **PIC** dan **Kontak PIC**.
   - **Jumlah User** yang terdaftar di fasilitas tersebut.
   - **Status** — Aktif (hijau) atau Tidak Aktif (merah).

3. Gunakan kolom **Cari** di atas tabel untuk memfilter berdasarkan kode, nama, atau keterangan.

## Langkah-Langkah: Menambah Fasilitas Kesehatan

1. Pada halaman daftar, klik tombol **"Buat"** di pojok kanan atas.

![Form Tambah Fasilitas](/screenshots/admin-fasilitas-form.png)

2. Isi form pada **Tab Utama**:

   - **Kode** — masukkan kode unik fasilitas. Contoh: `PKM001`.
   - **Nama** — masukkan nama lengkap fasilitas. Contoh: "Puskesmas Cimahi Selatan".
   - **Tipe** — pilih `Puskesmas` atau `Pustu`.
   - **Puskesmas Induk** — jika tipe `Pustu`, pilih Puskesmas induk dari dropdown. Field ini hanya muncul untuk tipe Pustu.
   - **Status** — pilih `Aktif` atau `Nonaktif`.

3. Beralih ke **Tab Informasi Kontak** (opsional):

   - **PIC** — nama penanggung jawab.
   - **Kontak PIC** — nomor HP/WA.
   - **Telepon** — nomor telepon kantor.
   - **Kepala Fasilitas** — nama kepala fasilitas.
   - **Alamat** — alamat lengkap fasilitas.

4. Klik **"Simpan"** untuk menyimpan data.

## Langkah-Langkah: Mengedit Fasilitas Kesehatan

1. Pada halaman daftar, klik ikon **Ubah** (pensil) pada baris fasilitas yang ingin diubah.

![Form Edit Fasilitas](/screenshots/admin-fasilitas-edit.png)

2. Form akan terbuka dengan data yang sudah ada. Ubah field yang diperlukan.
3. Klik **"Simpan"** untuk menyimpan perubahan.

## Langkah-Langkah: Menghapus Fasilitas Kesehatan

1. Pada halaman daftar, centang kotak centang pada baris fasilitas yang ingin dihapus.
2. Setelah dipilih, toolbar akan muncul di bagian atas tabel. Klik tombol **"Hapus"**.
3. Konfirmasi penghapusan pada dialog yang muncul.

> **Catatan:** Fasilitas yang sudah memiliki data user atau transaksi terkait tidak bisa dihapus. Ubah status menjadi **Nonaktif** sebagai gantinya.

## Penggunaan Data Fasilitas dalam Modul Lain

| Modul                   | Penggunaan Fasilitas                                        |
| ----------------------- | ----------------------------------------------------------- |
| **Permintaan Obat**     | Pustu mengirim permintaan ke Puskesmas induk.               |
| **Distribusi Obat**     | Puskesmas mendistribusikan obat ke Pustu.                   |
| **Penerimaan Stok**     | Setiap penerimaan stok terikat ke fasilitas penerima.       |
| **Stok Faskes**         | Riwayat stok setiap obat di setiap fasilitas.               |
| **Pemakaian Obat**      | Pencatatan pemakaian obat oleh fasilitas tertentu.          |
| **Stok Opname**         | Penyesuaian stok fisik dilakukan per fasilitas.             |
| **LPLPO**               | Laporan pemakaian obat dihasilkan per fasilitas.            |
| **RKO**                 | Rencana kebutuhan obat dihitung per fasilitas.              |
| **Prediksi AI**         | Model prediksi dijalankan per fasilitas.                    |
