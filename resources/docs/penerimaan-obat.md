# Penerimaan Obat

Modul **Penerimaan Obat** digunakan untuk mencatat penerimaan obat ke dalam gudang pusat atau fasilitas kesehatan. Setiap penerimaan memiliki nomor unik, tipe penerimaan, dan detail item obat beserta informasi batch (nomor batch, tanggal kedaluwarsa, jumlah). Saat penerimaan dikonfirmasi, stok secara otomatis diproses oleh sistem.

## Kolom Data Penerimaan

| Kolom              | Tipe    | Wajib | Keterangan                                                       |
| ------------------ | ------- | ----- | ---------------------------------------------------------------- |
| **Nomor Penerimaan** | String  | Otomatis | Nomor unik dokumen (contoh: `PO/PUSK01/2026/07/0001`). Di-generate otomatis oleh sistem. |
| **Tipe**             | Enum    | Ya    | Jenis penerimaan (lihat tabel Tipe Penerimaan di bawah).         |
| **Tanggal Penerimaan** | Date  | Ya    | Tanggal penerimaan obat.                                         |
| **Fasilitas**        | FK      | Tidak | Fasilitas penerima. Kosong = gudang/dinas pusat.                 |
| **Supplier**         | FK      | Tidak | Supplier pengirim (wajib jika tipe = Pembelian).                |
| **Nomor PO**         | String  | Tidak | Nomor Purchase Order.                                            |
| **Nomor Invoice**    | String  | Tidak | Nomor invoice/faktur supplier.                                   |
| **Sumber Dana**      | FK      | Tidak | Sumber dana/anggaran yang digunakan.                             |
| **Catatan**          | Text    | Tidak | Catatan tambahan.                                                |
| **Status**           | Enum    | Ya    | Status dokumen (lihat tabel Status di bawah).                    |

## Penjelasan Tipe Penerimaan

| Tipe          | Keterangan                                                             |
| ------------- | ---------------------------------------------------------------------- |
| **Pembelian**   | Pembelian obat dari supplier. Wajib mengisi supplier, nomor PO, dan invoice. |
| **Hibah**       | Penerimaan obat berupa hibah/donasi dari pihak lain.                  |
| **Stok Awal**   | Pencatatan stok awal saat pertama kali menggunakan sistem.            |
| **Penyesuaian** | Penyesuaian stok (penambahan) akibat temuan fisik.                    |
| **Distribusi**  | Penerimaan obat dari hasil distribusi antar fasilitas.                |
| **Manual**      | Penerimaan obat secara manual tanpa alur khusus.                      |

## Penjelasan Status

| Status        | Keterangan                                                              |
| ------------- | ----------------------------------------------------------------------- |
| **Draft**       | Dokumen tersimpan namun belum dikonfirmasi. Stok belum diproses. Bisa diedit. |
| **Dikonfirmasi** | Dokumen sudah dikonfirmasi. Stok sudah diproses ke dalam sistem.       |
| **Dibatalkan**  | Dokumen dibatalkan. Jika sebelumnya sudah dikonfirmasi, stok di-reverse. |

> **Alur:** Draft → Dikonfirmasi (stok diproses) atau Draft → Dibatalkan. Jika sudah dikonfirmasi, bisa dibatalkan (stok dikembalikan).

## Detail Item Obat

Setiap penerimaan memiliki satu atau lebih item obat. Setiap item berisi:

| Kolom              | Tipe    | Wajib | Keterangan                                            |
| ------------------ | ------- | ----- | ----------------------------------------------------- |
| **Obat**             | FK      | Ya    | Obat yang diterima.                                   |
| **Jumlah**           | Integer | Ya    | Jumlah unit yang diterima.                             |
| **Tanggal Kedaluwarsa** | Date | Ya    | Tanggal kedaluwarsa batch obat.                        |
| **Nomor Batch**      | String  | Tidak | Nomor batch (bisa di-generate otomatis di dev mode).   |
| **Harga Satuan**     | Decimal | Tidak | Harga beli per unit (untuk tipe Pembelian).            |
| **Keterangan**       | Text    | Tidak | Catatan per item.                                      |

> **Catatan:** Saat penerimaan dikonfirmasi, setiap item akan membuat atau menambah record **BatchStok** yang merepresentasikan stok fisik per batch.

## Langkah-Langkah: Melihat Daftar Penerimaan

1. Buka menu **Distribusi & Permintaan** → **Penerimaan Stok**.

![Halaman Daftar Penerimaan](/screenshots/admin-penerimaan.png)

2. Tabel menampilkan seluruh penerimaan dengan kolom: Nomor, Tipe (badge), Status (badge), Tanggal, Fasilitas, Supplier, Sumber Dana, dan Total Biaya.
3. Gunakan **tab** di bagian atas untuk beralih antara penerimaan dinas (gudang) dan faskes.
![Tab Penerimaan Obat](/screenshots/admin-penerimaan-tab.png)
4. Gunakan **filter** untuk memfilter berdasarkan status, supplier, sumber dana, atau rentang tanggal.
5. Klik nomor penerimaan untuk melihat detail.

## Langkah-Langkah: Membuat Penerimaan Baru

Pembuatan penerimaan menggunakan **wizard 3 langkah**:

### Langkah 1: Informasi

1. Pada halaman daftar, klik tombol **"Buat Penerimaan"** di pojok kanan atas.
![Tombol Tambah Penerimaan Stok](/screenshots/admin-penerimaan-tab.png)

2. Isi informasi umum:
![Form Penerimaan - Langkah 1](/screenshots/admin-penerimaan-form.png)
   - **Nomor Penerimaan** — klik tombol **Generate** untuk membuat nomor otomatis, atau isi manual.
   - **Tipe** — pilih jenis penerimaan (Pembelian, Hibah, Stok Awal, Penyesuaian, Distribusi, atau Manual).
   - **Tanggal Penerimaan** — pilih tanggal.
   - **Supplier** — (wajib untuk tipe Pembelian) pilih supplier dari daftar. Jika belum ada, klik **"+ Create"** untuk menambah supplier baru secara langsung.
   - **Nomor PO** dan **Nomor Invoice** — (untuk tipe Pembelian) isi nomor PO dan invoice.
   - **Sumber Dana** — (opsional) pilih sumber dana/anggaran.
   - **Catatan** — (opsional) tambahkan catatan.

3. Klik **"Selanjutnya"** untuk melanjutkan.

### Langkah 2: Item Obat

4. Tambahkan item obat yang diterima:
![Form Penerimaan - Langkah 2](/screenshots/admin-penerimaan-form-langkah-2.png)
   - **Obat** — pilih obat dari daftar. Harga satuan akan otomatis terisi.
   - **Jumlah** — masukkan jumlah unit yang diterima.
   - **Tanggal Kedaluwarsa** — pilih tanggal kedaluwarsa batch.
   - **Nomor Batch** — (opsional) masukkan atau biarkan kosong untuk auto-generate.
   - **Harga Satuan** — harga beli per unit.

5. Klik **"+ Tambah Item"** untuk menambah item lain.
![Tomboh Tambah Item](/screenshots/tombol-add-item.png)
6. Klik **"Selanjutnya"** untuk melanjutkan.

### Langkah 3: Konfirmasi

7. Tinjau ringkasan semua item yang akan diterima:
   - Tabel menampilkan: Nama Obat, Jumlah, Harga Satuan, dan Subtotal per item.
   - Total biaya ditampilkan di bagian bawah.
8. Klik **"Simpan"** untuk menyimpan sebagai **Draft**, atau **"Buat"** untuk langsung **Mengonfirmasi** penerimaan.

## Langkah-Langkah: Mengedit Penerimaan

1. Pada halaman daftar, klik nomor penerimaan untuk membuka halaman detail.
2. Klik tombol **"Edit"** di pojok kanan atas.
3. Hanya penerimaan berstatus **Draft** yang bisa diedit.
4. Ubah field yang diperlukan, lalu klik **"Simpan"**.

## Langkah-Langkah: Mengonfirmasi Penerimaan

1. Buka penerimaan berstatus **Draft**.
2. Klik tombol **"Konfirmasi"**.
3. Sistem akan memproses stok secara otomatis:
   - Membuat/menambah record **BatchStok** untuk setiap item.
   - Menambah jumlah stok di **Stok Gudang** atau **Stok Fasilitas**.
   - Mencatat **Riwayat Stok** (tipe: masuk).
   - Jika ada sumber dana, membuat catatan **Sumber Dana Penggunaan**.

> **Catatan:** Proses konfirmasi tidak bisa dibatalkan secara langsung. Untuk membatalkan penerimaan yang sudah dikonfirmasi, ubah status menjadi **Dibatalkan** (stok akan di-reverse/dikembalikan).

## Langkah-Langkah: Membatalkan Penerimaan

1. Buka penerimaan yang ingin dibatalkan.
2. Klik **"Edit"**, lalu ubah status menjadi **Dibatalkan**.
3. Jika penerimaan sebelumnya berstatus **Dikonfirmasi**, sistem akan mengembalikan stok secara otomatis (reverse):
   - Mengurangi jumlah stok di BatchStok.
   - Mengurangi aggregate stok (Stok Gudang / Stok Fasilitas).
   - Mencatat Riwayat Stok tipe reversal.

> **Catatan:** Hanya penerimaan berstatus **Draft** yang bisa dihapus permanen.

## Penggunaan Data Penerimaan dalam Modul Lain

| Modul             | Penggunaan Data Penerimaan                                                |
| ----------------- | ------------------------------------------------------------------------- |
| **BatchStok**       | Setiap penerimaan membuat/menambah record batch stok dengan nomor batch dan tanggal kedaluwarsa. |
| **Stok Gudang**     | Jika fasilitas kosong, stok ditambahkan ke gudang pusat.                  |
| **Stok Fasilitas**  | Jika fasilitas ditentukan, stok ditambahkan ke fasilitas tersebut.        |
| **Retur Obat**      | Retur ke supplier terkait dengan penerimaan asal.                         |
| **Riwayat Stok**    | Setiap perubahan stok tercatat dalam riwayat (tipe: masuk / distribusi_masuk). |
| **Sumber Dana**     | Penggunaan anggaran tercatat otomatis jika sumber dana ditentukan.        |
| **Laporan Neraca**  | Data penerimaan menjadi bagian dari neraca persediaan obat.               |
