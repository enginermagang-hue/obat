# Peran Pengguna

RUANG OBAT menerapkan hierarki peran untuk menjaga alur data sesuai aturan tata kelola obat di puskesmas/pustu. Setiap peran memiliki akses menu yang berbeda.

## Perbandingan Sidebar per Role

### Super Admin
Akses penuh ke semua menu termasuk Manajemen Akses (User, Role, Permission) dan Ai Service.

![Dashboard Super Admin](/screenshots/admin-dashboard.png)

### Admin Dinas
Memantau seluruh faskes. Memiliki akses Ai Service, Laporan Alokasi Dana, tanpa Manajemen Akses.

![Dashboard Admin Dinas](/screenshots/role-admin-dinas.png)

### Admin Gudang
Mengelola stok gudang dan supplier. Memiliki Backup Database tanpa Ai Service dan Manajemen Akses.

![Dashboard Admin Gudang](/screenshots/role-admin-gudang.png)

### Puskesmas
Mengelola distribusi ke Pustu dan laporan. Tanpa Master Data dan Manajemen Akses.

![Dashboard Puskesmas](/screenshots/role-puskesmas.png)

### Pustu
Membuat permintaan dan menerima distribusi. Menu terbatas, tanpa Stok Gudang dan Stok Opname.

![Dashboard Pustu](/screenshots/role-pustu.png)

## Detail Tanggung Jawab per Role

### 1. Super Admin
Akun pertama yang dibuat saat setup awal aplikasi. Memiliki akses penuh ke semua fitur.

**Tugas:**
- Membuat / menghapus user.
- Mengelola role dan permission.
- Mengelola semua master data (obat, faskes, supplier, sumber dana).
- Akses semua laporan dan AI Service.

### 2. Admin Dinas (Dinas Kesehatan)

**Tugas:**
- Melihat laporan agregat dari seluruh puskesmas.
- Mengelola sumber dana.
- Menerima dan menindaklanjuti permintaan dari Puskesmas.
- Mengelola prediksi AI (Dashboard AI, Hasil Prediksi, Model Prediksi).

### 3. Admin Gudang (Gudang Puskesmas)

**Tugas:**
- Mengelola stok gudang utama.
- Menyetujui permintaan dari Pustu.
- Membuat permintaan ke Dinas Kesehatan.
- Mengelola supplier.
- Backup database.

### 4. Puskesmas

**Tugas:**
- Mengelola distribusi obat ke Pustu.
- Melaporkan pemakaian obat (LPLPO).
- Menyusun Rencana Kebutuhan Obat (RKO).

### 5. Pustu (UPT / Puskesmas Pembantu)

**Tugas:**
- Membuat permintaan obat ke Puskesmas.
- Menerima dan mencatat penerimaan obat.
- Melaporkan penggunaan obat.

## Langkah-Langkah: Manajemen User

### Melihat Daftar User

1. Login sebagai **Super Admin**.
2. Buka menu **Manajemen Akses** → **User** di sidebar.
3. Halaman ini menampilkan tabel semua user yang terdaftar.
4. Kolom **Fasilitas** menunjukkan faskes tempat user bertugas.
5. Gunakan kolom pencarian untuk mencari user berdasarkan nama atau email.

![Daftar User RUANG OBAT](/screenshots/admin-users.png)

### Membuat User Baru

1. Klik tombol **"Buat user"** di halaman User.
2. Isi formulir data user (nama, email, password).
3. Pilih **Role** yang sesuai (Super Admin, Admin Gudang, Admin Dinas, Puskesmas, Pustu).
4. Pilih **Fasilitas Kesehatan** (khusus role Puskesmas / Pustu — pilih faskes yang sesuai).
5. Klik **"Simpan"**.

### Mengubah Role atau Fasilitas User

1. Klik **"Ubah"** pada user yang ingin diedit.
2. Ubah field **Role** atau **Fasilitas Kesehatan** sesuai kebutuhan.
3. Klik **"Simpan"**.

> Perubahan role akan langsung mempengaruhi menu dan halaman yang dapat diakses user tersebut.
