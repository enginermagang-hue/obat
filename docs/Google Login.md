# Google Login (OAuth) — Dokumentasi

## 1. Tujuan

Menyediakan mekanisme **otentikasi alternatif** menggunakan akun Google bagi user yang sudah terdaftar di sistem. Tidak ada registrasi publik — user hanya bisa login Google jika sudah mengaktifkan fitur ini dari pengaturan profil masing-masing.

## 2. Status Implementasi

| Komponen | Status |
| :------- | :----: |
| Model + Relasi | ✅ |
| Migration (schema) | ✅ |
| Policy + Gate | ❌ |
| Filament Resource | ❌ |
| Form Schema | ✅ |
| Table Config | ❌ |
| Plugin Socialite | ✅ |
| Halaman Profil (linking) | ✅ |

## 3. Alur / Cara Kerja

### 3.1. Linking (Aktivasi dari Profil)

```
User login (email/password)
  → Buka Profil (EditProfile)
  → Klik "Hubungkan Google"
  → Redirect ke Google OAuth
  → Authorize
  → Callback /auth/google/callback
  → google_id tersimpan + google_login_enabled = true
  → Redirect kembali ke halaman admin
```

### 3.2. Login dengan Google

```
User buka halaman login /admin
  → Klik tombol "Google"
  → Redirect ke Google OAuth
  → Pilih akun Google
  → Callback (ditangani filament-socialite)
  → Cari user berdasarkan email
    ├─ User tidak ditemukan  → error "Akun tidak ditemukan"
    ├─ User ditemukan, tapi google_login_enabled = false
    │   → error "Akun Anda belum mengaktifkan login dengan Google"
    └─ User ditemukan & enabled → login sukses
```

## 4. Detail Teknis

### 4.1. Package

- `laravel/socialite` — Library OAuth dari Laravel
- `dutchcodingcompany/filament-socialite` (^3.1) — Integrasi Socialite ke Filament 5

### 4.2. Migration

**Tabel:** `users` (ditambah kolom)

| Kolom                  | Tipe               | Keterangan                                  |
| ---------------------- | ------------------ | ------------------------------------------- |
| `google_id`            | `string`, nullable, indexed | ID unik dari Google untuk user       |
| `google_login_enabled` | `boolean`, default `false`  | Status aktivasi login Google         |

**Tabel:** `socialite_users` (dari package)

Menyimpan relasi antara user lokal dengan provider OAuth.

### 4.3. Konfigurasi

**`config/services.php`**
```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_OAUTH_REDIRECT'),
],
```

**`.env`**
```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_OAUTH_REDIRECT=${APP_URL}/auth/google/callback
```

### 4.4. Model (`App\Models\User`)

**Fillable** — tambah: `'google_id'`, `'google_login_enabled'`

### 4.5. Panel Provider (`AdminPanelProvider.php`)

Plugin Socialite dipasang dengan:
- `registration(false)` — tidak ada registrasi publik
- `resolveUserUsing()` — custom resolver yang cek `google_login_enabled`
- Halaman profil di-override dengan `EditProfile` custom

### 4.6. Halaman Profil (`app/Filament/Pages/Auth/EditProfile.php`)

Tambahan dari form default:
- **Toggle** `google_login_enabled` — izinkan login dengan Google
- **Action button** "Hubungkan Google" — memulai OAuth linking flow

### 4.7. Route Callback (`routes/web.php`)

**`GET /auth/google/callback`**

Bedakan dua tipe request via parameter `state`:
- `state = "link:{userId}"` → linking flow (simpan `google_id`, enable login)
- Selain itu → login flow (ditangani otomatis oleh `filament-socialite`)

### 4.8. Error Handling

| Skenario                                  | Pesan Error                                                                 |
| ----------------------------------------- | --------------------------------------------------------------------------- |
| User tidak ditemukan di database          | "Akun tidak ditemukan."                                                     |
| User ditemukan tapi belum aktifkan Google | "Akun Anda belum mengaktifkan login dengan Google. Silakan aktifkan di pengaturan profil." |
| Google OAuth ditolak user                 | Redirect balik ke login, flash error dari Socialite provider                |

## 5. Hak Akses

- **Superadmin / Admin** — bisa melihat status `google_login_enabled` user (via UserResource jika ditambahkan)
- **User biasa** — hanya bisa mengubah seting Google login untuk akunnya sendiri (via halaman Profil)

## 6. Aturan Bisnis

- User **harus sudah ada** di database — tidak ada auto-registrasi dari Google
- User harus **mengaktifkan sendiri** fitur ini dari profil — tidak bisa diaktifkan oleh admin (kecuali diubah manual via database)
- `google_id` hanya tersimpan setelah user sukses melakukan **OAuth linking** dari profil
- Setelah linking, user tetap bisa login dengan email/password seperti biasa
- Jika user menonaktifkan toggle `google_login_enabled`, login Google tidak akan berfungsi sampai diaktifkan kembali

## 7. Setup Google Cloud Console

1. Buka https://console.cloud.google.com
2. Buat project baru atau pilih project yang sudah ada
3. Navigasi ke **APIs & Services** → **Credentials**
4. Klik **Create Credentials** → **OAuth client ID**
5. Application type: **Web application**
6. **Authorized JavaScript origins**: `https://lamaa.test`
7. **Authorized redirect URIs**: `https://lamaa.test/auth/google/callback`
8. Klik **Create**, copy `Client ID` dan `Client Secret`
9. Masukkan ke `.env`:
   ```
   GOOGLE_CLIENT_ID=xxxxx.apps.googleusercontent.com
   GOOGLE_CLIENT_SECRET=GOCSPX-xxxxx
   ```
10. **Penting**: Jika menggunakan domain production, ganti `https://lamaa.test` dengan URL production yang sesuai
