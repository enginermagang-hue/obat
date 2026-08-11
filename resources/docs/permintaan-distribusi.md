# Permintaan & Distribusi

Modul ini mengatur alur **permintaan obat** dari Pustu ke Puskesmas, dan **distribusi obat** dari Puskesmas (atau Dinas) ke fasilitas yang membutuhkan.

## Alur Permintaan Obat

```text
Pustu  ──(create permintaan)──▶  Puskesmas
Puskesmas  ──(validate)──▶  Dinas Kesehatan
```

## Langkah-Langkah: Permintaan Obat (Role Pustu)

1. Login sebagai user dengan role **Pustu**.
2. Buka menu **Distribusi & Permintaan** → **Permintaan Obat**.
3. Klik tombol **"Buat Permintaan Obat"**.

![Halaman Permintaan Obat](/screenshots/form-permintaan-obat.png)

4. Isi formulir permintaan:
   - **Nomor Permintaan** — otomatis tergenerate.
   - **Tanggal Permintaan** — tanggal pengajuan.
   - **Tujuan** — otomatis terisi Puskesmas induk.
   - **Catatan** — tambahkan keterangan jika perlu.
5. Pada bagian **Daftar Item**, klik **"Tambah Item"**.

![Form Item Permintaan](/screenshots/form-permintaan-obat-item.png)

6. Pilih **Obat** dari daftar, masukkan **Jumlah Diminta** dan **Catatan** (opsional).
7. Ulangi langkah 5–6 untuk setiap obat yang diminta.
8. Klik **"Kirim"** untuk mengirim permintaan ke Puskesmas.

### Menyetujui / Menolak Permintaan (Role Puskesmas / Admin Gudang)

1. Buka menu **Permintaan Obat** → pilih tab **"Menunggu Persetujuan"**.
2. Klik nomor permintaan yang ingin ditindaklanjuti.
3. Pada halaman detail, klik tombol **"Setujui"** atau **"Tolak"**.
4. Jika menyetujui, jumlah yang diminta akan masuk ke proses distribusi.

## Langkah-Langkah: Distribusi Obat

### Membuat Distribusi (Role Puskesmas / Admin Gudang)

1. Buka menu **Distribusi & Permintaan** → **Distribusi Obat**.

![Halaman Distribusi Obat](/screenshots/admin-distribusi-obat-list.png)

2. Klik **"Buat distribusi"**.
3. Pilih **Tujuan** (Puskesmas / Pustu penerima).
4. Pilih **Sumber Dana**.
5. Pada tabel obat, klik **"Tambah Baris"**.
6. Pilih **obat** — sistem otomatis memilih batch dengan **tanggal kadaluarsa terdekat (FEFO)**.
7. Masukkan **jumlah** yang akan didistribusikan.
8. Klik **"Simpan"**.

> Stok langsung dikurangi saat distribusi disimpan berdasarkan sistem FEFO.

### Menerima Distribusi (Role Pustu)

1. Buka **Distribusi & Permintaan** → **Penerimaan Stok**.
2. Klik distribusi yang statusnya **"Dalam Perjalanan"**.
3. Klik tombol **"Terima"**.
4. Stok akan otomatis ditambahkan ke inventory Pustu.

![Halaman Penerimaan Stok](/screenshots/admin-penerimaan-stok.png)

## Langkah-Langkah: Penerimaan Stok Non-Distribusi

Gunakan menu **Penerimaan Stok** untuk menambah stok dari sumber non-distribusi (misal: pembelian langsung):

1. Buka **Distribusi & Permintaan** → **Penerimaan Stok**.
2. Klik **"Buat penerimaan"**.
3. Pilih **Sumber Dana** dan **Supplier** (jika ada).
4. Pada tabel, klik **"Tambah Baris"**.
5. Pilih **obat**, masukkan **nomor batch**, **tanggal kadaluarsa**, dan **jumlah**.
6. Ulangi untuk setiap obat.
7. Klik **"Simpan"**.

## Retur Obat

Jika terdapat obat rusak / tidak sesuai, gunakan modul **Retur Obat**:

1. Buka menu **Distribusi & Permintaan** → **Retur Obat**.
2. Klik **"Buat Retur"**.
3. Pilih **distribusi asal** dan **obat** yang diretur.
4. Masukkan jumlah dan alasan retur.
5. Klik **"Simpan"**.

## Tips

- Selalu pilih batch dengan tanggal kadaluarsa paling dekat (FEFO).
- Jika ingin mengembalikan stok, gunakan modul **Retur Obat**.
