# User & Auth — Dokumentasi Fitur

## 1. Tujuan

Mengelola **pengguna sistem** dan **otentikasi** untuk aplikasi RUANG OBAT. Setiap user terdaftar di database lokal dan terikat ke satu fasilitas kesehatan (nullable untuk role Dinas). Autentikasi dilakukan via email/password atau Google OAuth (jika diaktifkan).

## 2. Status Implementasi

| Komponen | Status |
| :------- | :----: |
| Model + Relasi | ✅ |
| Migration (schema) | ✅ |
| Policy + Gate | ✅ |
| Filament Resource | ❌ |
| Form Schema | ✅ |
| Table Config | ✅ |

## 3. Detail Teknis

### Model & Relasi

**Model:** `App\Models\User`  
**Table:** `users`  

**Fillable Fields:**
- `name` — Nama lengkap
- `email` — Email (unique)
- `password` — Password (hashed, nullable untuk Google-only users)
- `fasilitas_kesehatan_id` — FK ke `fasilitas_kesehatan.id` (nullable)
- `google_id` — ID Google OAuth (nullable)
- `google_login_enabled` — Boolean, default false
- `last_active_at` — Timestamp aktivitas terakhir

**Relasi:**

| Method                 | Type     | Target               |
| ---------------------- | -------- | -------------------- |
| `fasilitasKesehatan()` | BelongsTo | `FasilitasKesehatan` |
| `preferences()`        | HasOne   | `UserPreference`     |

**Attributes:**
- `getFilamentAvatarUrl()` — Mendukung DiceBear, preset, atau upload avatar

### Fitur Auth

- **Login email/password**: Standar Laravel + Filament
- **Google OAuth**: Via `laravel/socialite` + `filament-socialite` plugin
  - User harus mengaktifkan sendiri dari halaman Profil
  - Tidak ada registrasi publik
- **Role-based access**: Via Spatie Laravel Permission

### Hak Akses ke Resource User

| Permission         | super_admin | admin_gudang | admin_dinas | puskesmas | pustu |
| ------------------ | :---------: | :----------: | :---------: | :-------: | :---: |
| `view_users`       |     ✅      |     ❌       |     ❌      |    ❌     |  ❌   |
| `create_users`     |     ✅      |     ❌       |     ❌      |    ❌     |  ❌   |
| `update_users`     |     ✅      |     ❌       |     ❌      |    ❌     |  ❌   |
| `delete_users`     |     ✅      |     ❌       |     ❌      |    ❌     |  ❌   |

> Hanya **Super Admin** yang bisa mengelola user. Role lain tidak memiliki akses ke User Resource.

### Aturan Bisnis

1. Email bersifat unique
2. Satu user hanya bisa terikat ke satu faskes
3. User Dinas (admin_gudang, admin_dinas) memiliki `fasilitas_kesehatan_id = null`
4. Password hanya required saat create (nullable saat edit)
5. User bisa login via Google jika `google_login_enabled = true` dan `google_id` terisi

## 4. Daftar File

### Files Baru

(Tidak ada)

### Files Dimodifikasi

- `app/Filament/Resources/Users/Pages\CreateUser.php`
- `app/Filament/Resources/Users/Pages\EditUser.php`
- `app/Filament/Resources/Users/Pages\ListUsers.php`
- `app/Filament/Resources/Users/Schemas\UserForm.php`
- `app/Filament/Resources/Users/Tables\UsersTable.php`
- `app/Filament/Resources/Users/UserResource.php`
- `app/Models/User.php`
- `app/Policies/UserPolicy.php`
