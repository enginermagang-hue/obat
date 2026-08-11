# Overview RUANG OBAT

## Apa itu RUANG OBAT?

**RUANG OBAT** adalah singkatan dari **Sistem Informasi Manajemen Obat**. Sistem ini dirancang khusus untuk membantu **fasilitas kesehatan** (puskesmas dan pustu) dalam mengelola siklus alokasi dan penggunaan obat secara terstruktur, akuntabel, dan efisien.

## Fitur Utama

- Manajemen permintaan dan distribusi obat antar-fasilitas.
- Pelacakan stok berbasis **batch number** dan **tanggal kadaluarsa** (FEFO).
- Pelaporan dokumen resmi (LPLPO, RKO, Neraca Tahunan, Faktur).
- Prediksi kebutuhan obat berbasis **AI / Rubix ML**.

## Struktur Hierarki Fasilitas Kesehatan

```text
Dinas Kesehatan
    └── Puskesmas
            └── Pustu (Puskesmas Pembantu / UPT)
```

## Langkah-Langkah Pengoperasian

### 1. Login ke Sistem

1. Buka halaman **Login** melalui browser di alamat aplikasi RUANG OBAT.
2. Masukkan **Alamat Email** dan **Kata Sandi** yang sudah terdaftar.
3. Centang **Ingat saya** jika ingin tetap login.
4. Klik tombol **"Masuk"**.

![Halaman Login RUANG OBAT](/screenshots/admin-login.png)

> Login dapat dilakukan menggunakan akun email internal RUANG OBAT atau melalui **Login Google** (jika telah diaktifkan oleh Super Admin).

### 2. Menjelajahi Halaman Dashboard

Setelah berhasil login, Anda akan masuk ke halaman **Dashboard** yang menampilkan ringkasan data sistem:

- **Total Obat** — jumlah obat yang terdaftar.
- **Permintaan Pending** — permintaan obat yang menunggu persetujuan.
- **Penerimaan Stok** — total stok yang diterima bulan ini.
- **Stok Menipis** — obat dengan stok di bawah minimum.

![Dashboard RUANG OBAT](/screenshots/admin-dashboard.png)

### 3. Navigasi Menu

Gunakan **sidebar kiri** untuk mengakses modul-modul utama:

- **Ai Service** — Dashboard AI dan hasil prediksi.
- **Master Data** — Data obat, faskes, sumber dana, supplier.
- **Distribusi & Permintaan** — Permintaan, distribusi, penerimaan, pemakaian, retur obat.
- **Inventory** — Stok gudang, stok faskes, riwayat stok, stok opname.
- **Laporan** — LPLPO, RKO, Neraca Tahunan, Alokasi Dana.
- **Manajemen Akses** — Manajemen user, role, permission.
- **Sistem** — Profil, backup database, pengaturan.

### 4. Manajemen Profil

Klik ikon **profil (avatar)** di pojok kanan atas → **Profil Saya** untuk mengatur:

- Nama dan email
- Password
- Avatar (upload / preset / DiceBear)
- Tema warna (terang/gelap/otomatis)
- Posisi navbar (sidebar/topbar)
- Notifikasi

![Halaman Profil Saya](/screenshots/admin-profil-saya.png)

Lihat halaman **Peran Pengguna** untuk detail tanggung jawab setiap jenjang.
