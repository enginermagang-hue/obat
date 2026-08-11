# Setup Awal Aplikasi RUANG OBAT - Panduan Superadmin

## Daftar Isi
1. Overview Setup
2. Cara Mengakses Setup Wizard
3. Langkah-Langkah Setup
4. File Konfigurasi yang Dihasilkan
5. Troubleshooting

## Overview Setup

Saat pertama kali membuka aplikasi RUANG OBAT, superadmin akan dialihkan ke halaman **Setup Wizard** untuk mengkonfigurasi aplikasi sesuai kebutuhan organisasi.

Setup wizard ini memandu superadmin melalui 5 langkah:

1. **Data Organisasi** - Informasi dasar organisasi/dinas kesehatan
2. **Fasilitas Kesehatan Utama** - Konfigurasi puskesmas/fasilitas induk
3. **Admin Utama** - Buat akun administrator pertama
4. **Konfigurasi PDF** - Atur header dan footer laporan
5. **Konfirmasi** - Tinjau semua data sebelum menyimpan

## Cara Mengakses Setup Wizard

### Melalui Web Interface
1. Buka aplikasi RUANG OBAT di `http://lamaa.test:8080/admin`
2. Login dengan akun superadmin (jika belum setup)
3. Anda akan otomatis dialihkan ke Setup Wizard
4. Atau akses langsung: `http://lamaa.test:8080/admin/setup-wizard`

### Melalui Command Line (Artisan)
```bash
php artisan ruang-obat:setup
```

Perintah ini akan memandu setup secara interaktif melalui terminal.

## Langkah-Langkah Setup Detail

### Step 1: Data Organisasi
Masukkan informasi dasar organisasi Anda:

- **Nama Organisasi**: Nama resmi organisasi (contoh: "Dinas Kesehatan Kota Bandung")
- **Kode Organisasi**: Kode unik untuk identifikasi (contoh: "DINKES-BDG")
- **Deskripsi Organisasi**: Penjelasan singkat tentang organisasi (opsional)

### Step 2: Fasilitas Kesehatan Utama
Konfigurasikan fasilitas kesehatan yang menjadi induk (puskesmas):

- **Nama Fasilitas**: Nama lengkap puskesmas (contoh: "Puskesmas Cimahi Selatan")
- **Kode Fasilitas**: Kode unik fasilitas (contoh: "PKM-CS-001")

Fasilitas ini akan menjadi parent untuk pustu (sub-units) yang akan dibuat kemudian.

### Step 3: Admin Utama
Buat akun administrator pertama yang akan mengelola sistem:

- **Nama Admin**: Nama lengkap administrator (contoh: "Budi Santoso")
- **Email Admin**: Email unik untuk login (contoh: "admin@dinkes.bandung.go.id")

Password default: `123` (HARUS diganti setelah login pertama)

### Step 4: Konfigurasi PDF
Atur format untuk laporan PDF yang akan dihasilkan:

- **Header PDF**: Konten header laporan (bisa kosong atau berisi nama organisasi)
- **Footer PDF**: Konten footer laporan (bisa kosong atau berisi copyright)

### Step 5: Konfirmasi
Tinjau semua data yang telah dimasukkan:

- Pastikan semua informasi sudah benar
- Klik "Selesaikan Setup" untuk menyimpan
- Sistem akan logout dan diminta login kembali dengan akun admin baru

## File Konfigurasi yang Dihasilkan

Setelah menyelesaikan setup, sistem akan menyimpan konfigurasi di:

### Database Table: `setup_configurations`

```sql
- is_setup_completed: boolean (true setelah setup selesai)
- organization_name: string
- organization_code: string
- organization_description: text
- primary_facility_name: string
- primary_facility_code: string
- admin_email: string
- admin_name: string
- pdf_header: text
- pdf_footer: text
- setup_completed_at: timestamp
```

### Database Records Created:

1. **User Record** - Akun admin baru dengan role `admin_gudang`
2. **FasilitasKesehatan Record** - Puskesmas utama sebagai parent facility
3. **SetupConfiguration Record** - Konfigurasi organisasi dan sistem

## Troubleshooting

### Setup Wizard tidak muncul setelah login
**Solusi:**
- Clear cache: `php artisan cache:clear`
- Cek database: `SELECT * FROM setup_configurations;`
- Jika data ada tapi `is_setup_completed = 0`, reset dengan: `php artisan tinker` lalu `\App\Models\SetupConfiguration::query()->update(['is_setup_completed' => false]);`

### Email admin sudah terpakai
**Solusi:**
- Gunakan email yang belum terdaftar di sistem
- Atau hapus user lama terlebih dahulu di database

### Tidak bisa login setelah setup selesai
**Solusi:**
- Gunakan email yang Anda masukkan di Step 3
- Password default: `123`
- Cek kembali typo pada email/password

### Reset Setup Lengkap
Jika ingin mengulang setup dari awal:

```bash
php artisan tinker
```

Kemudian jalankan:

```php
\App\Models\SetupConfiguration::query()->update(['is_setup_completed' => false]);
User::where('email', 'admin@example.com')->delete();
\App\Models\FasilitasKesehatan::where('type', 'puskesmas')->first()->delete();
```

Setelah itu, logout dan akses `/admin` kembali untuk mengulangi setup.

## Catatan Penting

⚠️ **SECURITY REMINDER:**
- Ubah password admin (`123`) segera setelah login pertama
- Jangan bagikan password default ke orang lain
- Gunakan password yang kuat dan unik

⚠️ **DATA CONSISTENCY:**
- Setup hanya perlu dilakukan sekali
- Untuk mengubah organisasi/fasilitas, gunakan menu Pengaturan di admin panel
- Jangan menghapus setup_configurations record secara manual

✓ **NEXT STEPS setelah Setup:**
1. Login dengan akun admin baru
2. Ubah password di Profile/Pengaturan
3. Buat pustu (sub-facilities) jika diperlukan
4. Konfigurasi supplier dan obat di Master Data
5. Mulai menggunakan sistem
