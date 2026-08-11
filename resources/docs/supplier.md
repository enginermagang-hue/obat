# Manajemen Supplier

Modul **Supplier** digunakan untuk mengelola data penyedia obat yang bermitra dengan fasilitas kesehatan. Setiap supplier memiliki informasi kontak, identitas pajak (NPWP), dan status keaktifan yang menentukan apakah supplier dapat digunakan dalam transaksi penerimaan stok.

## Kolom Data Supplier

| Kolom      | Tipe    | Wajib | Keterangan                                              |
| ---------- | ------- | ----- | ------------------------------------------------------- |
| **Nama**       | String  | Ya    | Nama perusahaan/supplier (unik). Contoh: "PT Anugrah Pharmindo". |
| **Alamat**     | Text    | Tidak | Alamat lengkap kantor/gudang supplier.                  |
| **Telepon**    | String  | Tidak | Nomor telepon/WhatsApp. Contoh: `0812-xxxx-xxxx`.            |
| **Email**      | String  | Tidak | Alamat email perusahaan.                                |
| **NPWP**       | String  | Tidak | Nomor Pokok Wajib Pajak perusahaan.                     |
| **Status**     | Enum    | Ya    | `Aktif` / `Nonaktif`.                                       |

## Penjelasan Status

| Status      | Keterangan                                                             |
| ----------- | ---------------------------------------------------------------------- |
| **Aktif**     | Supplier aktif dan dapat digunakan dalam transaksi penerimaan stok.    |
| **Nonaktif**  | Supplier tidak aktif. Tidak dapat dipilih saat membuat penerimaan stok baru. |

> **Tip:** Jika sebuah supplier sudah tidak bermitra lagi, ubah statusnya menjadi **Nonaktif** alih-alih menghapusnya, karena data supplier yang sudah terkait dengan penerimaan stok sebelumnya tidak bisa dihapus.

## Langkah-Langkah: Melihat Daftar Supplier

1. Buka menu **Master Data** → **Supplier**.

![Halaman Daftar Supplier](/screenshots/admin-supplier.png)

2. Tabel menampilkan seluruh supplier dengan kolom: Nama, Telepon, Email, dan Status (badge).
3. Gunakan filter **Status** di bagian atas tabel untuk memfilter berdasarkan status Aktif atau Nonaktif.
4. Gunakan kolom **Cari** untuk mencari supplier berdasarkan nama, telepon, email, alamat, atau NPWP.

## Langkah-Langkah: Menambah Supplier

1. Pada halaman daftar supplier, klik tombol **"Tambah Supplier"** di pojok kanan atas.

![Form Tambah Supplier](/screenshots/admin-supplier-form.png)

2. Isi form berikut:
   - **Nama** — masukkan nama perusahaan supplier (wajib, harus unik). Contoh: "PT Anugrah Pharmindo".
   - **Telepon** — (opsional) nomor telepon/WhatsApp. Contoh: "0812-3456-7890".
   - **Email** — (opsional) alamat email perusahaan. Contoh: "info@anugrah.co.id".
   - **NPWP** — (opsional) nomor NPWP perusahaan.
   - **Alamat** — (opsional) alamat lengkap kantor/gudang supplier.
   - **Status** — aktifkan atau nonaktifkan supplier.

3. Klik **"Simpan"** untuk menyimpan supplier baru.

## Langkah-Langkah: Mengedit Supplier

1. Pada halaman daftar, klik ikon **action** (tiga titik) di kolom Aksi pada baris supplier, lalu pilih **Edit**.
![Klik Edit Supplier](/screenshots/admin-supplier-klik-edit.png)
2. Form akan terbuka dengan data yang sudah ada. Ubah field yang diperlukan.
![Klik Edit Supplier](/screenshots/admin-supplier-edit-form.png)
3. Klik **"Simpan"** untuk menyimpan perubahan.

## Langkah-Langkah: Menghapus Supplier

1. Pada halaman daftar, klik ikon **action** (tiga titik) di kolom Aksi pada baris supplier, lalu pilih **Hapus**.
2. Konfirmasi penghapusan pada dialog yang muncul.

> **Catatan:** Supplier yang sudah memiliki data penerimaan stok atau retur obat terkait tidak bisa dihapus. Ubah status menjadi **Nonaktif** sebagai gantinya.

## Penggunaan Data Supplier dalam Modul Lain

| Modul                | Penggunaan Data Supplier                                                |
| -------------------- | ----------------------------------------------------------------------- |
| **Penerimaan Stok**  | Setiap penerimaan obat harus menunjukkan supplier pengirim.             |
| **Retur Obat**       | Retur obat ke supplier mencatat supplier terkait untuk pengembalian.    |
