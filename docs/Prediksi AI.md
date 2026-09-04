# Prediksi AI — Dokumentasi Fitur

## 1. Tujuan

Mengaplikasikan **machine learning** (Jaringan Saraf Tiruan / ANN buatan sendiri dengan PHP) dan **statistical forecasting** (moving average) untuk memprediksi kebutuhan obat per fasilitas kesehatan (Puskesmas/Pustu) secara periodik, membantu pengisian **RKO (Rencana Kebutuhan Obat)** dan pembuatan **permintaan obat** secara lebih akurat dan berbasis data historis pemakaian.

> **Riwayat engine:** versi awal memakai Rubix/ML (GradientBoost, serialisasi RBX). Engine tersebut sudah **digantikan** oleh ANN PHP murni (`PhpAnnPredictionService` + `App\Services\Ann\*`) tanpa dependensi ML eksternal. Nilai enum lama (`ai_gradient_boost`, `ai_random_forest`) tetap dipertahankan di database untuk kompatibilitas data histori.

## 2. Status Implementasi

| Komponen | Status |
| :------- | :----: |
| Model + Relasi | ✅ |
| Migration (schema) | ✅ |
| Policy + Gate | ✅ |
| Prediction engine (ANN + Moving Average) | ✅ |
| Artisan command + scheduler | ✅ |
| Halaman Prediksi AI + Dashboard AI + widgets | ✅ |
| Rekomendasi pengadaan | ✅ |
| Integrasi RKO (auto-fill + generate bulk) | ✅ |
| Filament Resource terpisah (Model/Hasil Prediksi) | ❌ (diganti halaman khusus, lihat §4.5) |
| Form Schema / Table Config terpisah | ❌ (tidak dipakai lagi) |

## 3. Arsitektur

```
┌────────────────────────┐       ┌─────────────────────────────────┐
│   pemakaian_obat +     │──────▶│   PhpAnnPredictionService        │
│   detail_pemakaian_obat│       │   - ANN MLP 9-12-8-1 (PHP murni) │
│   (sumber data utama)  │       │   - MovingAverage (fallback)     │
└────────────────────────┘       └──────────┬──────────────────────┘
                                             │
                             ┌───────────────┴───────────────┐
                             ▼                               ▼
                    ┌──────────────────┐          ┌──────────────────┐
                    │  model_prediksi  │          │ prediksi_kebutuhan│
                    │  (bobot ANN JSON │          │ (hasil prediksi   │
                    │   + file .json)  │          │  3 bln ke depan)  │
                    └──────────────────┘          └────────┬─────────┘
                                                           │
                                              ┌────────────┼────────────┐
                                              ▼            ▼            ▼
                                     ┌────────────┐ ┌───────────┐ ┌───────────┐
                                     │ detail_rko │ │ PrediksiAi│ │DashboardAi│
                                     │ .prediksi_ │ │ Page      │ │ Page +    │
                                     │  id → auto-│ │ (rekomen- │ │ widgets   │
                                     │  fill      │ │  dasi+PO) │ │           │
                                     │  usulan    │ │           │ │           │
                                     └────────────┘ └───────────┘ └───────────┘
```

## 4. Komponen yang Dibangun

### 4.1 Foundation

| #   | Komponen | File                                   | Keterangan                            |
| --- | -------- | -------------------------------------- | ------------------------------------- |
| 1   | Model    | `app/Models/ModelPrediksi.php`         | Model terlatih per faskes+obat        |
| 2   | Model    | `app/Models/PrediksiKebutuhan.php`     | Hasil prediksi per periode            |
| 3   | Policy   | `app/Policies/ModelPrediksiPolicy.php` | Gate model_prediksi                   |
| 4   | Policy   | `app/Policies/PrediksiKebutuhanPolicy.php` | Gate prediksi_kebutuhan (+ scope visibilitas faskes) |

**Relasi tambahan pada model existing:**

| Model                  | Method                     | Type     |
| ---------------------- | -------------------------- | -------- |
| `FasilitasKesehatan`   | `modelPrediksi()`          | HasMany  |
| `FasilitasKesehatan`   | `prediksiKebutuhan()`      | HasMany  |
| `Obat`                 | `modelPrediksi()`          | HasMany  |
| `Obat`                 | `prediksiKebutuhan()`      | HasMany  |
| `DetailRko`            | `prediksi()`               | BelongsTo (ke PrediksiKebutuhan) |
| `ModelPrediksi`        | `fasilitas()` / `obat()` / `prediksiKebutuhan()` | BelongsTo / BelongsTo / HasMany |
| `PrediksiKebutuhan`    | `fasilitas()` / `obat()` / `model()` / `dibuatOleh()` | BelongsTo |

### 4.2 Prediction Engine (ANN + Moving Average)

| #   | Komponen | File                                       | Keterangan                                    |
| --- | -------- | ------------------------------------------ | --------------------------------------------- |
| 5   | Service  | `app/Services/PhpAnnPredictionService.php` | `train()`, `generatePredictions()`, `getMonthlyUsage()` |
| 6   | ANN      | `app/Services/Ann/AnnTrainer.php`          | Training MLP (SGD + early stopping + L2)      |
| 7   | ANN      | `app/Services/Ann/AnnModel.php`            | Forward pass + serialisasi bobot (JSON)       |
| 8   | ANN      | `app/Services/Ann/AnnScaler.php`           | Standardisasi fitur (mean/std per fitur)      |
| 9   | Service  | `app/Services/MovingAverageService.php`    | `predict()`, `hasSufficientData()` (fallback) |
| 10  | Command  | `app/Console/Commands/AiTrainModels.php`   | `php artisan ai:train-models`                 |

#### `PhpAnnPredictionService` API

```php
// Train model untuk kombinasi faskes + obat
train(FasilitasKesehatan $faskes, Obat $obat): ModelPrediksi

// Generate prediksi 3 bulan ke depan dari model yg sudah di-train
generatePredictions(ModelPrediksi $model): Collection<int, PrediksiKebutuhan>

// Ambil data pemakaian bulanan 12 bulan terakhir, zero-fill (public, reusable)
getMonthlyUsage(int $fasilitasId, int $obatId): array // ['YYYY-MM' => total]
```

**Sumber data (`getMonthlyUsage()`):** agregasi `SUM(detail_pemakaian_obat.jumlah)` per bulan dari join `pemakaian_obat`, difilter per faskes+obat. Jendela 12 bulan dihitung mundur dari `MAX(tanggal_pemakaian)` faskes tersebut (bukan dari hari ini), bulan kosong diisi 0 agar lag kalender stabil. Kompatibel SQLite (`strftime`) dan MySQL (`DATE_FORMAT`).

**Feature vector (9 fitur):**

| Index | Fitur            | Keterangan                                 |
| :---: | ---------------- | ------------------------------------------ |
| 0     | `lag_1_bulan`    | Pemakaian bulan sebelumnya (t-1)           |
| 1     | `lag_2_bulan`    | Pemakaian 2 bulan sebelumnya (t-2)         |
| 2     | `lag_3_bulan`    | Pemakaian 3 bulan sebelumnya (t-3)         |
| 3     | `rata_rata_6_bulan` | Rata-rata pemakaian 6 bulan terakhir      |
| 4     | `rata_rata_12_bulan` | Rata-rata pemakaian 12 bulan terakhir    |
| 5     | `bulan`          | Nomor bulan (1-12) untuk pola musiman      |
| 6     | `trend_3_bulan`  | Slope regresi linear 3 bulan terakhir      |
| 7     | `stok_saat_ini`  | Stok saat ini (`stok_faskes`, fallback `batch_stok`/`stok_gudang`) |
| 8     | `tipe_faskes`    | 1=puskesmas, 0=pustu (gudang mengikuti relasi) |

**Arsitektur & hiperparameter ANN (`AnnTrainer`, default):**

| Parameter | Nilai | Keterangan |
| --------- | ----- | ---------- |
| Topologi | MLP 9-12-8-1 | 9 input → hidden 12 (ReLU) → hidden 8 (ReLU) → 1 output linear |
| Learning rate | 0.01 | SGD per-sampel (online) |
| Epochs | 800 | Dihentikan dini bila validasi tidak membaik |
| L2 | 1e-4 | Regularisasi bobot |
| Patience | 20 | Early stopping pada split validasi internal 80/20 |
| Split train/test | 0.8 | Test set dipakai hanya bila ≥ 2 sampel; bila tidak, metrik NULL |
| Inisialisasi | He | `randn() * sqrt(2/fan_in)`, bias 0 |

**Evaluasi model:** R² (`akurasi_r2`, dijepit 0–1), MAE (`mae`), MAPE (`mape`, %; periode aktual 0 dilewati). Prediksi dibulatkan ke integer ≥ 0.

**Penyimpanan model:** bobot + bias + struktur layer + scaler + `yMean`/`yStd` diserialisasi sebagai JSON — disimpan di kolom `model_data` **dan** file `storage/app/ai-models/{fasilitas_id}_{obat_id}.json` (kolom `model_path`). Saat inferensi, file diprioritaskan; `model_data` sebagai cadangan.

**Fallback data belum cukup:** jika bulan berbeda dengan pemakaian > 0 berjumlah < 6 (`MIN_DATA_MONTHS`), model disimpan dengan status `data_belum_cukup` (tanpa bobot) dan prediksi dibuat iteratif 3 bulan via `MovingAverageService` (rata-rata jendela 3 bulan terakhir + CI 95% ±1.96 SD, interval bawah dijepit ≥ 0). Model dengan status selain `aktif`/`data_belum_cukup` (mis. `gagal`, `kadaluarsa`) tidak menghasilkan prediksi.

#### `MovingAverageService` API

```php
predict(array $monthlyData, int $window = 3): array{jumlah, confidence_lower, confidence_upper}
hasSufficientData(array $monthlyData, int $minimumMonths = 3): bool
```

### 4.3 Training Command & Scheduler

| #   | Komponen                           | Keterangan                                   |
| --- | ---------------------------------- | -------------------------------------------- |
| 11  | `AiTrainModels` (`ai:train-models`)| Opsi `--fasilitas-id=`, `--obat-id=`, `--force`; chunk lazy 50, maks 500 kombinasi/run; lewati yang sudah `aktif` kecuali `--force`; progress bar + tabel rekap (processed/trained/predictions/errors) |
| 12  | Schedule `routes/console.php`      | `AiTrainModels` setiap Minggu jam 02:00 (`weekly()->sundays()->at('02:00')`, `withoutOverlapping`, `runInBackground`, environment `production`+`local`) |

### 4.4 Rekomendasi Pengadaan (`PrediksiRekomendasiService`)

Mengubah hasil prediksi menjadi rekomendasi order per obat (agregasi lintas faskes bila tidak memilih satu faskes):

```
rekom = ceil(prediksi_total_horizon × 1.20 − stok_saat_ini), min 0
```

- `SAFETY_STOCK_RATE = 0.20` (safety stock 20%).
- Status per obat: `Aman` (rekom 0) / `Kritis` (coverage < 21 hari) / `Perlu Pesan` (sisanya); baris diurutkan menaik berdasarkan defisit relatif (`spike_pct`).
- `kpi()`: obat diprediksi, total obat aktif, obat defisit, estimasi anggaran (`rekom × harga_satuan`), rata-rata akurasi.
- `lonjakan(5)`: 5 obat defisit teratas untuk panel insight.
- `detail($obatId)`: prediksi per bulan + CI, tren realisasi 12 bulan, info model (status, R²/MAE/MAPE, tanggal training), stok gudang, dan faktor penjelasan (ambang stok, tren konsumsi, defisit, confidence model, ketersediaan gudang).

### 4.5 Halaman & Widget Admin

Tidak ada Filament Resource terpisah untuk prediksi. UI terdiri dari halaman khusus + dashboard:

| #   | UI | File | Keterangan |
| --- | -- | ---- | ---------- |
| 13  | Prediksi AI (halaman utama) | `app/Filament/Pages/PrediksiAiPage.php` (view `filament.pages.prediksi-ai`) | Menu **Prediksi AI** (ikon `cpu-chip`; grup `Laporan` untuk user faskes, `Ai Service` untuk lainnya); gate `view_prediksi_kebutuhan` |
| 14  | Dashboard AI | `app/Filament/Resources/DashboardAi/DashboardAiPage.php` + `Widgets/*` | Filter faskes/obat/bulan/tahun; widget `PredictionStatsOverview`, `ModelStatusChart`, `AccuracyDistributionChart`, `PredictionVsActualChart`, `TopPredictedDrugsChart`, `DrugTrendPredictionChart`, `CriticalPredictionAlerts` |
| 15  | Grafik realisasi | `app/Filament/Widgets/PrediksiRealisasiChart.php` | Perbandingan prediksi vs realisasi |

**Halaman Prediksi AI (`PrediksiAiPage`) menyediakan:**

- Filter: Puskesmas (terkunci untuk user 1 faskes; user puskesmas melihat faskes sendiri + pustu binaannya), kategori, cari obat, bulan, tahun, horizon (default 3).
- KPI + insight AI + daftar lonjakan + tabel rekomendasi per obat + modal detail per obat.
- Aksi **Buat Permintaan** (bulk maupun per obat) → membuat `permintaan_obat` via `BuatPermintaanService` dengan catatan sumber prediksi (hanya petugas faskes).
- Aksi **training**: `trainModel($modelId)` (retrain satu kombinasi, `--force`) dan `trainAll()` (semua kombinasi, `--force`) — notifikasi sukses/gagal.
- Export XLS via route `admin.prediksi.cetak-xls` (dengan filter aktif).

### 4.6 Integrasi RKO

| #   | Resource              | Lokasi Files                                  |
| --- | --------------------- | --------------------------------------------- |
| 16  | DetailRkoForm (update) | `app/Filament/Resources/LaporanRkos/Schemas/LaporanRkoForm.php` | Auto-fill saat pilih obat |
| 17  | Logika generate | `app/Filament/Resources/LaporanRkos/Concerns/ManagesRkoDetails.php` (`generateFromPrediksi()`, kolom AI, `prediksi_id`) |
| 18  | Tombol generate | `app/Filament/Resources/LaporanRkos/Pages/CreateLaporanRko.php` + `EditLaporanRko.php` (tombol "Generate dari Prediksi", fill + afterSave `prediksi_id`) |

**Auto-fill di DetailRkoForm:**
Saat user memilih obat di form RKO, sistem otomatis:
1. Mencari `prediksi_kebutuhan` untuk `fasilitas_id` + `obat_id` + `tahun` yang sesuai
2. Jika ditemukan → mengisi `keterangan` dengan format: `"Prediksi AI: {jumlah} (range: {cl} - {cu})"`
3. User tetap bisa edit manual

---

## 5. Detail Model & Relasi

### `ModelPrediksi`

| Kolom                | Tipe              | Nullable | Keterangan                            |
| -------------------- | ----------------- | -------- | ------------------------------------- |
| id                   | BIGINT (PK)       |          |                                       |
| fasilitas_id         | BIGINT (FK)       |          | FK → fasilitas_kesehatan (cascade delete) |
| obat_id              | BIGINT (FK)       |          | FK → obat (cascade delete)            |
| model_data           | LONGTEXT          |          | Bobot ANN terserialisasi (JSON)       |
| model_path           | VARCHAR           | ✓        | Path file model (`ai-models/{fid}_{oid}.json`) |
| akurasi_r2           | DECIMAL(5,4)      | ✓        | R² score 0-1                          |
| mae                  | DECIMAL(10,2)     | ✓        | Mean Absolute Error                   |
| mape                 | DECIMAL(5,2)      | ✓        | Mean Absolute Percentage Error (%)    |
| tanggal_training     | DATE              |          | Tanggal terakhir training             |
| data_training_count  | INT               |          | Jumlah bulan berbeda dengan pemakaian > 0 |
| fitur_digunakan      | JSON              | ✓        | Daftar nama fitur (9 fitur, lihat §4.2) |
| status               | ENUM              |          | `aktif`, `kadaluarsa`, `gagal`, `data_belum_cukup` (default `data_belum_cukup`) |
| error_message        | TEXT              | ✓        | Pesan error bila training `gagal`     |
| created_at           | TIMESTAMP         | ✓        |                                       |
| updated_at           | TIMESTAMP         | ✓        |                                       |

Unique: `[fasilitas_id, obat_id]`. Index: `[status, tanggal_training]`.
Warna badge status: `aktif`=success, `kadaluarsa`=warning, `gagal`=danger, `data_belum_cukup`=gray.

### `PrediksiKebutuhan`

| Kolom              | Tipe              | Nullable | Keterangan                            |
| ------------------ | ----------------- | -------- | ------------------------------------- |
| id                 | BIGINT (PK)       |          |                                       |
| fasilitas_id       | BIGINT (FK)       |          | FK → fasilitas_kesehatan (cascade delete) |
| obat_id            | BIGINT (FK)       |          | FK → obat (cascade delete)            |
| model_id           | BIGINT (FK)       | ✓        | FK → model_prediksi (null on delete)  |
| periode_bulan      | INT               |          | 1-12                                  |
| periode_tahun      | INT               |          |                                       |
| jumlah_prediksi    | INT               |          | Hasil prediksi                        |
| confidence_lower   | INT               | ✓        | Batas bawah CI 95%                    |
| confidence_upper   | INT               | ✓        | Batas atas CI 95%                     |
| metode             | ENUM              |          | `ann_php`, `moving_average`, `manual` (+ legacy `ai_gradient_boost`, `ai_random_forest` untuk data lama) |
| dibuat_oleh        | BIGINT (FK)       | ✓        | FK → users (NULL = system-generated)  |
| catatan            | TEXT              | ✓        | "Prediksi AI: ... (range: ...)"       |
| created_at         | TIMESTAMP         | ✓        |                                       |
| updated_at         | TIMESTAMP         | ✓        |                                       |

Unique: `[fasilitas_id, obat_id, periode_bulan, periode_tahun]`. Index: `metode`.

---

## 6. Permission Assignment

Permission dibuat otomatis oleh `RoleAndPermissionSeeder` via loop resource × aksi (`view/create/update/delete`).

| Permission | super_admin | admin_gudang | admin_dinas | puskesmas | pustu |
| ---------- | :---------: | :----------: | :---------: | :-------: | :---: |
| `view_model_prediksi` | ✅ | ✅ | ✅ | ✅ | ❌ |
| `create/update/delete_model_prediksi` | ✅ | ❌ | ❌ | ❌ | ❌ |
| `view_prediksi_kebutuhan` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `create/update/delete_prediksi_kebutuhan` | ✅ | ❌ | ❌ | ❌ | ❌ |

> **Note:** Training hanya via `php artisan ai:train-models` (cron, halaman Prediksi AI, atau manual). Untuk role non-super_admin, semua data prediksi bersifat read-only. Halaman Prediksi AI memakai gate `view_prediksi_kebutuhan`; user faskes hanya melihat faskes yang terlihat baginya (puskesmas + pustu binaannya).

---

## 7. Alur Training (Cron Mingguan)

```
php artisan ai:train-models
  │
  ├─ Options:
  │   --fasilitas-id=   → training untuk satu faskes saja
  │   --obat-id=        → training untuk satu obat saja
  │   --force           → retrain meski model sudah aktif
  │
  ├─ 1. Ambil kombinasi distinct faskes+obat dari detail_pemakaian_obat
  │     (lazy chunk 50, max 500 kombinasi per run)
  │
  ├─ 2. Untuk setiap kombinasi (lewati yang sudah aktif kecuali --force):
  │     ├─ a. Extract pemakaian 12 bulan terakhir (zero-fill, lihat §4.2)
  │     ├─ b. Feature engineering (9 fitur)
  │     ├─ c. Jika bulan berisi data >= 6:
  │     │     - Train ANN MLP 9-12-8-1 (SGD lr=0.01, 800 epochs, L2, early stopping)
  │     │     - Evaluasi split 80/20 → R², MAE, MAPE
  │     │     - Simpan JSON bobot → model_prediksi + file (status: aktif)
  │     │     - Tandai model aktif lama sebagai 'kadaluarsa'
  │     │     - Generate prediksi 3 bulan ke depan (autoregresif: prediksi bulan
  │     │       ke-i menjadi lag untuk bulan ke-(i+1); CI = ±1.96 SD historis)
  │     └─ d. Jika bulan berisi data < 6:
  │           - Simpan model dengan status: data_belum_cukup
  │           - Prediksi iteratif 3 bulan via Moving Average (metode: moving_average)
  │
  └─ 3. Tampilkan rekap: processed / trained / predictions / errors
```

**Schedule:** Setiap Minggu jam 02:00 (via `routes/console.php`, environment `production` + `local`):
```php
Schedule::command(AiTrainModels::class)
    ->weekly()->sundays()->at('02:00')
    ->withoutOverlapping()->runInBackground()
    ->environments(['production', 'local']);
```

---

## 8. Alur Integrasi RKO

### 8.1 Auto-fill saat Tambah Item Manual

```
User buka halaman Create/Edit RKO
  → Pilih faskes (auto-fill dari user, disabled untuk user faskes)
  → Pilih sumber dana & periode tahun
  → Tambah item obat via Repeater:
     1. Pilih obat dari Select
     2. → afterStateUpdated trigger:
        a. Set harga_perkiraan dari obat.harga_satuan
        b. Cari prediksi_kebutuhan WHERE fasilitas_id + obat_id + periode_tahun
        c. Jika ditemukan:
           - Set keterangan = "Prediksi AI: {jumlah} (range: {cl} - {cu})"
        d. User tetap bisa edit semua field manual
     3. Hitung total_harga = usulan × harga_perkiraan
```

### 8.2 Generate dari Prediksi (Bulk)

```
User buka Create RKO
  → Klik tombol "Generate dari Prediksi" (icon sparkles, warna info)
  → Konfirmasi dialog → Proses:
     1. Query semua obat aktif (Obat::where('status','aktif'))
     2. Query PrediksiKebutuhan WHERE fasilitas_id + periode_tahun
        → GroupBy obat_id → ambil prediksi bulan terbaru per obat
     3. Untuk setiap obat:
        ├─ Jika ada prediksi:
        │   - rata_rata_pemakaian_bulanan = jumlah_prediksi
        │   - pemakaian_tahun_sebelumnya = jumlah_prediksi × 12
        │   - keterangan = "Prediksi: {n} ({metode}, range: {lo}–{hi})"
        │   - prediksi_id = prediksi->id
        └─ Jika tidak ada prediksi:
            - rata_rata_pemakaian_bulanan = 0
            - pemakaian_tahun_sebelumnya = 0
            - keterangan = null, prediksi_id = null
     4. Ambil stok_akhir dari StokFaskes
     5. Hitung rumus Kemenkes (kebutuhan_tahunan, buffer, dll)
     6. Timpa $this->details[] → flush table
  → Notifikasi: "{n} item obat ({m} dari prediksi)"
```

**Perbedaan dengan "Generate dari Pemakaian":**

| Aspek | Generate dari Pemakaian | Generate dari Prediksi |
| :---- | :---------------------- | :--------------------- |
| Sumber data | RiwayatStok (keluar tahun lalu) | PrediksiKebutuhan (ANN/MA) |
| rata_rata_pemakaian_bulanan | sum(keluar) / 12 | jumlah_prediksi |
| pemakaian_tahun_sebelumnya | sum(keluar) aktual | jumlah_prediksi × 12 |
| Item yang di-generate | Obat berdasarkan riwayat/stok | Semua obat aktif |
| prediksi_id | null | FK ke prediksi_kebutuhan |
| Kolom AI | ❌ (minus) | ✅ (sparkles icon) |
| keterangan | null | "Prediksi: {n} ({metode}, range: ...)" |

---

## 9. Testing

```
✓ MovingAverageServiceTest (7 tests, 14 assertions)
  - test_predict_returns_average_of_last_3_months
  - test_predict_returns_zero_for_empty_data
  - test_predict_uses_specified_window
  - test_predict_handles_single_value
  - test_has_sufficient_data_returns_true
  - test_has_sufficient_data_returns_false
  - test_predict_never_returns_negative_jumlah

✓ AiTrainModelsCommandTest (2 tests, 5 assertions)
  - test_command_has_correct_configuration
  - test_command_has_expected_options
```

**Menjalankan tests:**
```bash
php artisan test --compact --filter="MovingAverageServiceTest|AiTrainModelsCommandTest"
```

---

## 10. Cara Penggunaan

### Training Model

```bash
# Training semua kombinasi faskes+obat (lewati yang sudah aktif)
php artisan ai:train-models

# Training spesifik
php artisan ai:train-models --fasilitas-id=1
php artisan ai:train-models --obat-id=5
php artisan ai:train-models --fasilitas-id=1 --obat-id=5

# Retrain paksa meski sudah ada model aktif
php artisan ai:train-models --force
```

Alternatif via UI: halaman **Prediksi AI** → aksi training per model atau training semua (keduanya memakai `--force`).

### Melihat Hasil

1. Buka panel admin → menu **Prediksi AI**
2. Atur filter faskes, kategori, bulan, tahun, horizon
3. Lihat KPI, insight AI, daftar lonjakan, dan tabel rekomendasi per obat (status Aman/Perlu Pesan/Kritis)
4. Klik item untuk detail: prediksi per bulan + CI, tren realisasi 12 bulan, info model (R²/MAE/MAPE), stok gudang, faktor penjelasan
5. Halaman **Dashboard AI** menampilkan agregat: statistik prediksi, status model, distribusi akurasi, prediksi vs aktual, dan alert kritis

### Buat Permintaan dari Prediksi

1. Di halaman Prediksi AI, klik **Buat Permintaan** (bulk untuk semua defisit, atau per obat dari modal detail)
2. Sistem membuat `permintaan_obat` via `BuatPermintaanService` dengan catatan sumber periode prediksi
3. Hanya petugas faskes yang dapat membuat permintaan; faskes harus dipilih

### RKO Auto-Fill & Generate dari Prediksi

1. Buka Laporan → RKO → Buat RKO baru
2. Pilih faskes, sumber dana, tahun
3. Tambah item → pilih obat → `keterangan` terisi otomatis dari prediksi AI, **atau**
4. Klik tombol **"Generate dari Prediksi"** → semua obat aktif terisi (kolom AI menandai item dari prediksi) → edit seperlunya → simpan

---

## 11. Dependencies

Tidak ada dependensi ML eksternal. ANN diimplementasikan murni dengan PHP (tanpa `rubix/ml`):

```json
{
  "require": {
    "php": "^8.3"
  }
}
```

---

## 12. Daftar File

### Engine & Data

| #   | File Path                                              |
| --- | ------------------------------------------------------ |
| 1   | `app/Models/ModelPrediksi.php`                         |
| 2   | `app/Models/PrediksiKebutuhan.php`                     |
| 3   | `app/Services/PhpAnnPredictionService.php`             |
| 4   | `app/Services/Ann/AnnTrainer.php`                      |
| 5   | `app/Services/Ann/AnnModel.php`                        |
| 6   | `app/Services/Ann/AnnScaler.php`                       |
| 7   | `app/Services/MovingAverageService.php`                |
| 8   | `app/Services/PrediksiRekomendasiService.php`          |
| 9   | `app/Console/Commands/AiTrainModels.php`               |
| 10  | `app/Policies/ModelPrediksiPolicy.php`                 |
| 11  | `app/Policies/PrediksiKebutuhanPolicy.php`             |
| 12  | `tests/Unit/MovingAverageServiceTest.php`              |
| 13  | `tests/Feature/AiTrainModelsCommandTest.php`           |

### UI (Halaman, Widget, Integrasi)

| #   | File Path                                              |
| --- | ------------------------------------------------------ |
| 14  | `app/Filament/Pages/PrediksiAiPage.php`                |
| 15  | `app/Filament/Resources/DashboardAi/DashboardAiPage.php` |
| 16  | `app/Filament/Resources/DashboardAi/Widgets/*` (7 widget: PredictionStatsOverview, ModelStatusChart, AccuracyDistributionChart, PredictionVsActualChart, TopPredictedDrugsChart, DrugTrendPredictionChart, CriticalPredictionAlerts) |
| 17  | `app/Filament/Widgets/PrediksiRealisasiChart.php`      |
| 18  | `app/Filament/Resources/LaporanRkos/Concerns/ManagesRkoDetails.php` |
| 19  | `app/Filament/Resources/LaporanRkos/Pages/CreateLaporanRko.php` |
| 20  | `app/Filament/Resources/LaporanRkos/Pages/EditLaporanRko.php` |
| 21  | `app/Filament/Resources/LaporanRkos/Schemas/LaporanRkoForm.php` |

### Pendukung

| #   | File Path                                              |
| --- | ------------------------------------------------------ |
| 22  | `app/Models/FasilitasKesehatan.php` (relasi)           |
| 23  | `app/Models/Obat.php` (relasi)                         |
| 24  | `app/Models/DetailRko.php` (relasi `prediksi()`)       |
| 25  | `app/Providers/AuthServiceProvider.php` (registrasi policy) |
| 26  | `routes/console.php` (jadwal mingguan)                 |
| 27  | `database/seeders/RoleAndPermissionSeeder.php` (permission, lihat §6) |
| 28  | `app/Services/BuatPermintaanService.php` (buat PO dari prediksi) |
| 29  | `app/Services/AnalisisObatService.php` (konsumsi rekomendasi) |
| 30  | `app/Services/TabulasiImportService.php` (validasi format `prediksi_wide`: min 6 bulan untuk ANN, 3 bulan untuk fallback MA) |
| 31  | `app/Console/Commands/BersihPemakaianObatCommand.php` (opsi `--with-prediksi`: null-kan `detail_rko.prediksi_id` lalu hapus prediksi & model) |

---

*Dokumentasi ini selaras dengan `docs/Skema Database.md`, `docs/Laporan RKO.md`, dan `docs/permissions-reference.md`.*
