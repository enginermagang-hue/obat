# Stok Batch & FEFO

## Apa itu Batch Number?

Setiap masuk atau keluar stok obat dicatat ke dalam **BatchStok** yang melacak:

- Nomor batch produksi
- Tanggal kadaluarsa
- Jumlah stok tersedia

## Sistem FEFO (First Expired, First Out)

Saat distribusi, RUANG OBAT otomatis memilih batch dengan **tanggal kadaluarsa paling dekat** terlebih dahulu. Hal ini memastikan stok kadaluarsa cepat habis digunakan sebelum stok lama yang lebih aman.

## Langkah-Langkah: Manajemen Stok

### Melihat Stok Gudang

1. Buka menu **Inventory** → **Stok Gudang**.

![Halaman Stok Gudang](/screenshots/admin-stok-gudang.png)

2. Gunakan tab untuk memfilter:
   - **Semua** — menampilkan seluruh stok.
   - **Stok Habis** — obat dengan stok = 0.
   - **Stok Hampir Habis** — stok di bawah minimum.
   - **Stok Kadaluarsa** — batch yang sudah expired.
   - **Stok Hampir Kadaluarsa** — batch yang akan expired dalam 30 hari.

3. Klik nama obat untuk melihat riwayat stok per batch.

### Menerima Stok Non-Distribusi

Gunakan menu **Penerimaan Stok** untuk menambah stok dari sumber non-distribusi (misal: pembelian langsung):

1. Buka **Distribusi & Permintaan** → **Penerimaan Stok**.
2. Klik **"Buat penerimaan"**.
3. Pilih **Sumber Dana** dan **Supplier** (jika ada).
4. Pada tabel, klik **"Tambah Baris"**.
5. Pilih **obat**, masukkan **nomor batch**, **tanggal kadaluarsa**, dan **jumlah**.
6. Ulangi untuk setiap obat.
7. Klik **"Simpan"**.

### Melakukan Stok Opname

Untuk menyesuaikan jumlah fisik riil dengan data sistem, lihat panduan lengkap di halaman **[Stok Opname](/panduan/stok-opname)**.

## Dashboard Widget

Stok yang **menipis** dan batch yang **sudah kadaluarsa** akan muncul di dashboard widget:

- **Stok Menipis** — obat dengan stok di bawah batas minimum.
- **Batch Akan Expired** — batch yang akan kadaluarsa dalam 30 hari ke depan.

## Penting

- Stok dihitung otomatis setiap kali ada transaksi penerimaan, distribusi, permintaan, retur, atau opname.
- Selalu periksa tanggal kadaluarsa sebelum mendistribusikan obat.
