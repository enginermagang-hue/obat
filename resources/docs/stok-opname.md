# Stok Opname

## Apa itu Stok Opname?

**Stok Opname** adalah proses penyesuaian jumlah stok fisik riil dengan data yang tercatat di sistem. Proses ini penting untuk memastikan akurasi data inventory.

## Kapan Stok Opname Dilakukan?

- Akhir periode (bulanan / triwulan / tahunan).
- Saat terjadi selisih stok yang mencurigakan.
- Pergantian petugas gudang.
- Sebelum audit internal.

## Tipe Stok Opname

Saat membuat opname, Anda perlu memilih **Tipe Opname** yang sesuai dengan kebutuhan:

### 1. Penyesuaian (Stok Existing)
Digunakan untuk **menyesuaikan stok yang sudah ada** di sistem. Tipe ini yang paling sering digunakan.

**Contoh penggunaan:**
- Stok fisik berbeda dengan catatan sistem karena kesalahan input.
- Obat rusak / hilang / kadaluarsa yang perlu dihapus dari stok.
- Menemukan stok lebih banyak dari catatan (kelebihan stok).

> Efek: Stok sistem akan berubah menjadi sama dengan stok fisik yang diinput.

### 2. Stok Awal
Digunakan saat **pertama kali mengisi stok** untuk fasilitas kesehatan atau gudang baru. Tipe ini menganggap semua stok yang dimasukkan adalah saldo awal.

**Contoh penggunaan:**
- Fasilitas kesehatan baru pertama kali menggunakan RUANG OBAT.
- Gudang baru dibuka dan perlu input stok awal.
- Migrasi data dari sistem lama ke RUANG OBAT.

> Efek: Stok sistem akan diisi sejumlah stok fisik yang diinput (tidak ada pengurangan).

### 3. Stok Baru
Digunakan untuk **menambahkan stok baru** ke dalam sistem tanpa melalui proses penerimaan distribusi. Stok akan ditambahkan ke stok yang sudah ada.

**Contoh penggunaan:**
- Menerima stok dari sumber non-distribusi (misal: bantuan, hibah).
- Menambahkan stok hasil produksi internal (misal: obat racikan).
- Stok yang sebelumnya tidak tercatat tetapi harus dimasukkan ke sistem.

> Efek: Stok sistem akan bertambah sejumlah stok fisik yang diinput.

## Langkah-Langkah: Melakukan Stok Opname

1. Buka menu **Inventory** → **Stok Opname**.

![Daftar Stok Opname](/screenshots/admin-stok-opname-list.png)

2. Klik tombol **"Buat stok opname"**.
3. Pilih **Fasilitas Kesehatan** (gudang / faskes tempat opname dilakukan).
4. Pada tabel obat, klik **"Tambah Baris"** untuk setiap obat yang akan diopname.
5. Masukkan data opname:
   - **Stok Sistem** — jumlah stok saat ini di sistem (otomatis terisi).
   - **Stok Fisik** — jumlah stok riil hasil penghitungan fisik.
   - **Keterangan** — catatan jika ada selisih.

![Form Stok Opname](/screenshots/form-stok-opname.png)

6. Sistem akan otomatis menghitung **selisih** (fisik - sistem).
7. Ulangi langkah 4–6 untuk setiap obat.
8. Klik **"Simpan"**.

## Yang Terjadi Setelah Opname

- Jika **Stok Fisik > Stok Sistem** → sistem menambahkan stok (surplus).
- Jika **Stok Fisik < Stok Sistem** → sistem mengurangi stok (defisit).
- Semua perubahan dicatat di **Riwayat Stok** sebagai transaksi opname.

## Tips

- Lakukan opname saat stok sedang sepi (tidak ada transaksi masuk/keluar).
- Libatkan dua orang: satu menghitung, satu mencatat.
- Cetak daftar stok dari halaman **Stok Gudang** sebagai referensi lapangan.

Lihat halaman **Stok Batch & FEFO** untuk informasi manajemen stok harian.
