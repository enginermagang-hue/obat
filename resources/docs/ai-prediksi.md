# AI Prediksi Kebutuhan

RUANG OBAT dilengkapi modul **prediksi kebutuhan obat** berbasis model machine learning (Rubix ML) untuk membantu perencanaan ketersediaan obat di masa depan.

## Model yang Tersedia

- **Gradient Boost** — model ensemble berbasis pohon keputusan.
- **Random Forest** — kumpulan pohon keputusan untuk prediksi lebih stabil.
- **Moving Average** — rata-rata pemakaian historis untuk prediksi sederhana.

Setiap model dilatih per kebutuhan obat (ModelPrediksi) dan dievaluasi untuk menentukan akurasi ramalan.

## Langkah-Langkah: Menjalankan Prediksi

### 1. Melatih Model

Prediksi dijalankan melalui perintah Artisan di server:

```bash
php artisan ai:train-models
```

Perintah ini akan:
- Mengumpulkan data pemakaian historis.
- Melatih ketiga model (Gradient Boost, Random Forest, Moving Average).
- Menyimpan hasil prediksi ke database.
- Menampilkan ringkasan metrik akurasi di konsol.

> Jika tidak memiliki akses server, hubungi Admin Dinas / Super Admin untuk menjalankan perintah ini.

### 2. Melihat Dashboard AI

1. Buka menu **Ai Service** → **Dashboard AI**.

![Dashboard AI](/screenshots/admin-dashboard-ai.png)

2. Dashboard menampilkan widget-widget berikut:

**Prediction Stats Overview**
Ringkasan status model, jumlah obat yang diprediksi, dan skor akurasi rata-rata.

**Drug Trend Prediction**
Grafik tren pemakaian obat untuk periode tertentu beserta garis prediksi masa depan.

**Accuracy Distribution**
Distribusi skor akurasi antar model — membantu memilih model terbaik untuk setiap obat.

**Critical Prediction Alerts**
Peringatan jika prediksi menandakan risiko stok habis pada periode mendatang.

### 3. Melihat Hasil Prediksi

1. Buka **Ai Service** → **Hasil Prediksi**.
2. Tabel menampilkan obat beserta:
   - Prediksi kebutuhan bulan depan.
   - Stok saat ini.
   - Rekomendasi pengadaan.
3. Gunakan filter untuk melihat prediksi per kategori obat atau per faskes.

## Tips

- Latih model secara berkala (setiap bulan) untuk hasil prediksi yang lebih akurat.
- Bandingkan hasil prediksi dengan LPLPO aktual untuk mengevaluasi akurasi.
- Gunakan **Critical Prediction Alerts** untuk mengantisipasi kekosongan stok.

## Bacaan Terkait

Lihat halaman **Laporan & Dokumen** untuk melihat data historis yang menjadi input prediksi.
