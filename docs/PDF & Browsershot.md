# Cetak PDF — Driver & Arsitektur

## Perubahan Driver (v1 → v2)

Pada versi sebelumnya, cetak PDF menggunakan **Browsershot** (Node.js + Puppeteer + Chrome).
Karena banyak shared hosting menonaktifkan `proc_open`, project ini dimigrasi ke **DOMPDF** (pure PHP).

| Aspek            | Sebelum (Browsershot)    | Sesudah (DOMPDF)                |
| ---------------- | ------------------------ | ------------------------------- |
| Driver           | `browsershot`              | `dompdf`                          |
| Runtime external | Node.js + Chrome         | Tidak ada (pure PHP)            |
| `proc_open`      | Diperlukan               | Tidak diperlukan                |
| `.env` variable  | `LARAVEL_PDF_DRIVER=browsershot` | `LARAVEL_PDF_DRIVER=dompdf` |
| Remote CSS/Font  | Otomatis (Chrome)        | Perlu `LARAVEL_PDF_DOMPDF_REMOTE_ENABLED=true` |

## Arsitektur (DOMPDF)

```
Blade View (HTML+CSS)
       ↓
Spatie\LaravelPdf\Facades\Pdf
       ↓
Spatie\LaravelPdf\Drivers\DomPdfDriver
       ↓
Dompdf\Dompdf (PHP library)
       ↓
Output: PDF file or base64
```

### Komponen utama

| Package                    | Versi  | Fungsi                          |
| -------------------------- | ------ | ------------------------------- |
| `spatie/laravel-pdf`       | v2     | Laravel PDF facade & driver     |
| `dompdf/dompdf`            | v3.1+  | Pure PHP PDF renderer           |

### File penting

- `app/Services/PdfGenerationService.php` — Service utama PDF generation (preview)
- `config/laravel-pdf.php` — Konfigurasi driver
- `resources/views/pdf/*.blade.php` — Template PDF views
- `app/Http/Controllers/Cetak*.php` — Controller untuk route cetak
- `app/Services/PdfSettingsService.php` — Setting font, margin, kop surat (logo sudah auto base64 untuk DOMPDF)

## Konfigurasi `.env`

```env
# Driver PDF (dompdf recommended untuk shared hosting)
LARAVEL_PDF_DRIVER=dompdf

# Izinkan DOMPDF memuat resource eksternal (Google Fonts, gambar remote)
LARAVEL_PDF_DOMPDF_REMOTE_ENABLED=true
```

### Opsi driver lainnya

| Driver        | Kebutuhan                          | Cocok untuk               |
| ------------- | ---------------------------------- | ------------------------- |
| `browsershot` | Node.js + Chrome + `proc_open`     | VPS / dedicated server    |
| `dompdf`      | PHP saja                           | Shared hosting / semua    |
| `cloudflare`  | Akun Cloudflare + API token        | Tanpa server Chrome      |
| `gotenberg`   | Docker instance Gotenberg          | Docker deployment        |

## Kompatibilitas View PDF

Semua view PDF (`resources/views/pdf/*.blade.php`) **sudah kompatibel dengan DOMPDF**:

- CSS inline murni (tidak pakai Flexbox/Grid/Tailwind)
- Logo kop surat otomatis dikonversi ke base64 data URI oleh `PdfSettingsService`
- Tabel `table.items` pakai CSS2 (`border-collapse`, `vertical-align`)
- Google Font di-load via `<link>` + `@import` (butuh `is_remote_enabled=true`)

### Yang dihapus saat migrasi

- `->waitUntilReady(...)` — fitur Puppeteer (tunggu font load sebelum render)
- `->withBrowsershot(...)` — konfigurasi Chrome args
- `<script>window.pdfReady = true; ...</script>` — JavaScript Puppeteer hook

## Shared Hosting — Langkah Setup

```bash
# 1. Set environment
LARAVEL_PDF_DRIVER=dompdf
LARAVEL_PDF_DOMPDF_REMOTE_ENABLED=true

# 2. Clear config cache
php artisan config:clear
php artisan view:clear

# 3. Test generate PDF
php artisan tinker --execute="
    \$html = '<h1>Hello World</h1><p>Test PDF</p>';
    \$dompdf = new Dompdf\Dompdf();
    \$dompdf->loadHtml(\$html);
    \$dompdf->render();
    echo strlen(\$dompdf->output()) . ' bytes';
"
```

Expected: output angka (misal `3124 bytes`), bukan error.

## Font Handling

### Google Fonts (Noto Sans, Roboto, dll)

DOMPDF memuat Google Fonts via HTTPS jika `LARAVEL_PDF_DOMPDF_REMOTE_ENABLED=true`.
Pastikan hosting mengizinkan outbound HTTPS ke `fonts.googleapis.com`.

### System Font (DejaVu Sans, DejaVu Serif)

Tidak perlu koneksi internet. Font sudah dibundling dalam DOMPDF.
Jika Google Font tidak bisa di-load, ubah `font_family` di **Pengaturan → Pengaturan PDF** ke `DejaVu Sans`.

## Development (Lokal)

### Setup awal (setelah clone project)

```bash
composer install
npm install
php artisan key:generate
php artisan migrate
```

**Tidak perlu install Node.js/Puppeteer/Chrome** untuk cetak PDF lagi.

Jika ingin tetap menggunakan Browsershot untuk development:

```bash
# Install Chrome via Puppeteer
npm install
npx puppeteer browsers install chrome
npx puppeteer browsers install chrome-headless-shell

# Set driver di .env
LARAVEL_PDF_DRIVER=browsershot
```

## Troubleshooting

### Error: `proc_open is not available` (di hosting)

**Penyebab:** Shared hosting menonaktifkan `proc_open` di `disable_functions`.

**Solusi:** Pastikan `.env` di hosting pakai `LARAVEL_PDF_DRIVER=dompdf`.

### Font tidak muncul di PDF

**Penyebab:** Google Font gagal di-load (network issue atau `remote_enabled` false).

**Solusi:**
```env
LARAVEL_PDF_DOMPDF_REMOTE_ENABLED=true
```
Atau ubah font ke `DejaVu Sans` via Pengaturan PDF.

### Layout tabel berbeda dari Browsershot

**Penyebab:** DOMPDF tidak mendukung beberapa CSS3 properties.

**Solusi:** Cek view PDF — gunakan CSS2 yang sudah terbukti kompatibel dengan DOMPDF.

### Error: `The Process class relies on proc_open`

**Penyebab:** Masih pakai driver `browsershot` di hosting.

**Solusi:**
```env
LARAVEL_PDF_DRIVER=dompdf
```

## Verifikasi instalasi (DOMPDF)

```bash
# Cek driver aktif
php artisan tinker --execute="echo config('laravel-pdf.driver');"
# Expected: dompdf

# Test generate PDF
php artisan tinker --execute="
    echo strlen(\Spatie\LaravelPdf\Facades\Pdf::view('pdf.faktur-distribusi', [])->format('A4')->base64()) . ' bytes';
"
```
