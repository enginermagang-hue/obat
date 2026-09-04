# -*- coding: utf-8 -*-
"""Generate PDF dokumentasi fitur Prediksi Kebutuhan Obat dengan Kecerdasan Buatan.

Struktur dokumen mengikuti kerangka jurnal acuan (JSISFOTEK 2022):
Abstrak - 1. Pendahuluan - 2. Metodologi Penelitian - 3. Hasil dan Pembahasan
- 4. Kesimpulan - Daftar Rujukan, dengan penomoran Tabel/Rumus/Gambar.

CARA PAKAI
----------
    python scripts/generate_prediksi_pdf.py

Output: tmp/Dokumentasi-Prediksi-AI.pdf (ditulis ulang setiap jalan).
Jika file PDF sedang dibuka aplikasi lain, skrip berhenti dengan pesan jelas.

CARA UBAH ISI
-------------
Edit teks/tabel di fungsi build() di bawah. Helper yang tersedia:
    h2(pdf, "...")       - judul bab bernomor (1. 2. 3. 4.)
    h3(pdf, "...")       - judul sub-bab bernomor (2.1, 3.4, ...)
    para(pdf, "...")     - paragraf (teks rata kiri-kanan)
    para(pdf, "...", bold_prefix="Rumus:") - paragraf diawali label tebal
    cap(pdf, "...")      - keterangan Tabel/Gambar (tebal, mis. "Tabel 1. ...")
    table(pdf, (lebar_kolom...), [("head1", ...), ("isi", ...)]) - tabel;
        baris pertama = header; lebar kolom = pecahan pdf.epw, mis. (0.3*W, 0.7*W)
    rumus(pdf, "Rumus 1.", "...") - persamaan bernomor, rata tengah
    center(pdf, "...")   - teks rata tengah (untuk diagram teks)
Helper styling (title/footer/need_space) tidak perlu diubah.

KEBUTUHAN
---------
    pip install fpdf2
"""

import os
import sys

try:
    from fpdf import FPDF
    from fpdf.fonts import FontFace
except ImportError:
    sys.exit("Paket fpdf2 belum terinstall. Jalankan: pip install fpdf2")

NAVY = (30, 58, 95)
HEADER_FILL = (37, 72, 120)
ZEBRA = (236, 242, 249)
RULE = (180, 190, 205)
BLACK = (30, 30, 30)

OUTPUT = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))),
                      "tmp", "Dokumentasi-Prediksi-AI.pdf")


class DocPDF(FPDF):
    def footer(self):
        self.set_y(-15)
        self.set_draw_color(*RULE)
        self.set_line_width(0.3)
        self.line(self.l_margin, self.get_y(), self.w - self.r_margin, self.get_y())
        self.set_y(-12)
        self.set_font("Helvetica", "I", 8)
        self.set_text_color(110, 110, 110)
        self.cell(0, 8, f"Dokumentasi Fitur Prediksi Kebutuhan Obat dengan Kecerdasan Buatan  |  Halaman {self.page_no()}/{{nb}}", align="C")


def need_space(pdf: DocPDF, h: float):
    if pdf.get_y() + h > pdf.h - 20:
        pdf.add_page()


def title(pdf: DocPDF, text: str):
    pdf.set_x(pdf.l_margin)
    pdf.set_font("Helvetica", "B", 18)
    pdf.set_text_color(*NAVY)
    pdf.multi_cell(0, 9, text, align="C")
    pdf.ln(2)
    pdf.set_draw_color(*NAVY)
    pdf.set_line_width(0.8)
    x = pdf.w / 2 - 40
    pdf.line(x, pdf.get_y(), x + 80, pdf.get_y())
    pdf.ln(6)


def h2(pdf: DocPDF, text: str):
    need_space(pdf, 30)
    pdf.set_x(pdf.l_margin)
    pdf.ln(3)
    pdf.set_font("Helvetica", "B", 14)
    pdf.set_text_color(*NAVY)
    pdf.multi_cell(0, 8, text)
    pdf.set_draw_color(*RULE)
    pdf.set_line_width(0.4)
    pdf.line(pdf.l_margin, pdf.get_y() + 1, pdf.w - pdf.r_margin, pdf.get_y() + 1)
    pdf.ln(4)


def h3(pdf: DocPDF, text: str):
    need_space(pdf, 22)
    pdf.set_x(pdf.l_margin)
    pdf.ln(1)
    pdf.set_font("Helvetica", "B", 12)
    pdf.set_text_color(*BLACK)
    pdf.multi_cell(0, 7, text)
    pdf.ln(1)


def para(pdf: DocPDF, text: str, bold_prefix: str = ""):
    pdf.set_x(pdf.l_margin)
    pdf.set_font("Helvetica", "", 11)
    pdf.set_text_color(*BLACK)
    if bold_prefix:
        pdf.set_font("Helvetica", "B", 11)
        pdf.write(6.2, bold_prefix + " ")
        pdf.set_font("Helvetica", "", 11)
    pdf.multi_cell(0, 6.2, text, align="J")
    pdf.ln(2)


def cap(pdf: DocPDF, text: str):
    need_space(pdf, 16)
    pdf.set_x(pdf.l_margin)
    pdf.set_font("Helvetica", "B", 10.5)
    pdf.set_text_color(*BLACK)
    pdf.multi_cell(0, 6, text, align="C")
    pdf.ln(1)


def rumus(pdf: DocPDF, nomor: str, teks: str):
    need_space(pdf, 16)
    pdf.set_x(pdf.l_margin)
    pdf.set_font("Helvetica", "B", 11)
    pdf.set_text_color(*BLACK)
    pdf.write(6.5, nomor + "  ")
    pdf.set_font("Helvetica", "", 11)
    w = pdf.w - pdf.r_margin - pdf.get_x()
    pdf.multi_cell(w, 6.5, teks, align="C")
    pdf.ln(3)


def center(pdf: DocPDF, text: str, bold: bool = False):
    pdf.set_x(pdf.l_margin)
    pdf.set_font("Helvetica", "B" if bold else "", 11)
    pdf.set_text_color(*BLACK)
    pdf.multi_cell(0, 6.5, text, align="C")


def table(pdf: DocPDF, widths, rows):
    pdf.set_x(pdf.l_margin)
    pdf.set_draw_color(*RULE)
    pdf.set_font("Helvetica", "", 10)
    with pdf.table(
        width=pdf.epw,
        col_widths=widths,
        first_row_as_headings=True,
        headings_style=FontFace(emphasis="BOLD", color=(255, 255, 255), fill_color=HEADER_FILL),
        cell_fill_mode="EVEN_ROWS",
        cell_fill_color=ZEBRA,
        line_height=5.8,
        padding=(1.6, 2, 1.6, 2),
        text_align="LEFT",
        v_align="M",
    ) as t:
        for r in rows:
            row = t.row()
            for c in r:
                row.cell(c)
    pdf.ln(4)


# ---------------------------------------------------------------------------
# ISI DOKUMEN - edit bagian ini untuk mengubah konten PDF
# ---------------------------------------------------------------------------
def build(pdf: DocPDF):
    W = pdf.epw
    title(pdf, "Prediksi Kebutuhan Obat Menggunakan Artificial Neural Network\npada Sistem Informasi Manajemen Stok Obat")

    h3(pdf, "Abstrak")
    para(pdf, "Prediksi jumlah kebutuhan obat pada fasilitas kesehatan sangat dibutuhkan untuk menjamin ketersediaan obat bagi pasien sekaligus menghindari penumpukan stok kedaluwarsa. Dokumen ini mendeskripsikan fitur prediksi kebutuhan obat yang menerapkan Artificial Neural Network (ANN) jenis Multilayer Perceptron berarsitektur 9-12-8-1, ditulis murni dengan PHP tanpa library machine learning eksternal. Sumber data adalah pencatatan pemakaian obat yang diagregasi bulanan selama 12 bulan, direkayasa menjadi 9 fitur, dinormalisasi dengan z-score, dan dilatih dengan stochastic gradient descent disertai early stopping. Model dievaluasi dengan R2, MAE, dan MAPE; prediksi dibuat 3 bulan ke depan secara autoregresif dengan fallback moving average bila data belum cukup, lalu diintegrasikan ke RKO dan permintaan obat.")
    para(pdf, "Prediksi Kebutuhan Obat; Artificial Neural Network; Backpropagation; Moving Average; R2; MAE; MAPE; RKO.", "Kata kunci:")

    h3(pdf, "Abstract")
    para(pdf, "Predicting drug demand at health facilities is essential to guarantee drug availability for patients while avoiding expired-stock accumulation. This document describes a drug-demand prediction feature implementing a 9-12-8-1 Multilayer Perceptron Artificial Neural Network (ANN), written purely in PHP without external machine learning libraries. Drug usage records aggregated monthly over 12 months are engineered into 9 features, normalized with z-score, and trained with stochastic gradient descent with early stopping. Models are evaluated with R2, MAE, and MAPE; predictions cover 3 months ahead autoregressively with a moving-average fallback when data are insufficient, and are integrated into drug requirement planning (RKO) and drug requisitions.")
    para(pdf, "Drug Demand Prediction; Artificial Neural Network; Backpropagation; Moving Average; R2; MAE; MAPE; RKO.", "Keywords:")

    h2(pdf, "1. Pendahuluan")
    para(pdf, "Sistem informasi manajemen stok obat melayani Gudang Dinas, Puskesmas, dan Pustu. Tantangan utamanya adalah menentukan jumlah kebutuhan obat setiap periode secara akurat: kekurangan stok menghambat pelayanan pasien, sedangkan kelebihan stok berisiko kedaluwarsa dan memboroskan anggaran. Selama ini Rencana Kebutuhan Obat (RKO) disusun berbasis rekapitulasi manual sehingga kurang responsif terhadap pola konsumsi aktual.")
    para(pdf, "Perkembangan Artificial Intelligence (AI) menghadirkan beragam teknik prediksi, salah satunya Artificial Neural Network (ANN) yang mengadopsi cara kerja jaringan syaraf. Algoritma Backpropagation Neural Network (BPNN) termasuk yang terbaik untuk prediksi, termasuk prediksi kebutuhan obat pada layanan kesehatan. Masalah yang diselesaikan adalah bagaimana merancang arsitektur ANN yang tepat beserta parameternya sehingga menghasilkan prediksi akurat, serta bagaimana hasil prediksi tersebut dapat langsung dipakai dalam perencanaan resmi faskes.")
    para(pdf, "Dokumen ini bertujuan mendeskripsikan rancangan dan implementasi fitur prediksi kebutuhan obat pada sistem informasi manajemen stok obat: pengumpulan data, analisis data, perancangan arsitektur ANN, pelaksanaan prediksi, hasil dan pembahasan, serta kesimpulan.")

    h2(pdf, "2. Metodologi Penelitian")
    para(pdf, "Tahapan prediksi kebutuhan obat terdiri dari pengumpulan data, analisis data, perancangan model arsitektur ANN, dan pelaksanaan prediksi, sebagaimana disajikan pada Gambar 1. Implementasi memakai PHP murni (Laravel 13, PHP 8.4, panel admin Filament) dengan basis data MySQL untuk pengembangan dan produksi; SQLite hanya dipakai untuk pengujian otomatis; penjadwalan training memakai Laravel Scheduler tiap Minggu jam 02:00.")
    cap(pdf, "Tabel 1. Lingkungan implementasi")
    table(pdf, (0.32 * W, 0.68 * W), [
        ("Komponen", "Teknologi"),
        ("Bahasa implementasi AI", "PHP (murni, tanpa library machine learning eksternal)"),
        ("Framework aplikasi", "Laravel 13, PHP 8.4, panel admin Filament"),
        ("Basis data", "MySQL untuk pengembangan dan produksi; SQLite hanya untuk testing"),
        ("Model ANN", "Kode sendiri: AnnTrainer (training), AnnModel (inferensi), AnnScaler (normalisasi)"),
        ("Metode cadangan", "MovingAverageService (rata-rata bergerak + confidence interval)"),
        ("Penjadwalan", "Laravel Scheduler: perintah ai:train-models tiap Minggu jam 02:00"),
    ])
    center(pdf, "Pengumpulan data  ->  Analisis data  ->  Perancangan arsitektur  ->  Pelaksanaan prediksi", True)
    center(pdf, "Gambar 1. Tahapan prediksi kebutuhan obat")
    pdf.ln(4)

    h3(pdf, "2.1. Pengumpulan Data")
    para(pdf, "Data dikumpulkan dari pencatatan pemakaian obat (tabel pemakaian_obat dan detail_pemakaian_obat) per kombinasi fasilitas kesehatan dan obat. Setiap pemakaian otomatis mengurangi stok sehingga data ini mencerminkan konsumsi nyata. Untuk tiap kombinasi, sistem mengagregasi SUM(jumlah) per bulan selama 12 bulan terakhir, dihitung mundur dari tanggal pemakaian terakhir pada faskes tersebut; bulan tanpa catatan diisi nol (zero-fill) agar deret kalender tetap utuh.")
    para(pdf, "Data yang sama tercatat sebagai riwayat keluar dan menjadi dasar penyusunan LPLPO (Laporan Pemakaian dan Lembar Permintaan Obat), sehingga sumber training selaras dengan dokumen perencanaan resmi faskes sebagaimana dipakai pada penelitian acuan.")
    cap(pdf, "Tabel 2. Struktur penyimpanan data prediksi (MySQL)")
    table(pdf, (0.28 * W, 0.72 * W), [
        ("Tabel", "Peran dan constraint kunci"),
        ("model_prediksi", "Satu baris per kombinasi faskes+obat (unik); FK cascade ke fasilitas_kesehatan dan obat; index (status, tanggal_training); akurasi_r2 DECIMAL(5,4), mae DECIMAL(10,2), mape DECIMAL(5,2); status ENUM; bobot JSON di LONGTEXT + file"),
        ("prediksi_kebutuhan", "Unik (fasilitas_id, obat_id, periode_bulan, periode_tahun); FK model_id nullOnDelete; index kolom metode (ENUM); dibuat_oleh nullable (NULL = system-generated)"),
        ("pemakaian_obat + detail", "Sumber training; agregasi memakai DATE_FORMAT(tanggal_pemakaian, '%Y-%m') pada MySQL (strftime pada SQLite)"),
        ("detail_rko", "Konsumen hasil: prediksi_id nullable menautkan usulan RKO ke baris prediksi"),
    ])
    para(pdf, "Perubahan tipe ENUM (mis. penambahan metode ann_php) dijalankan via ALTER TABLE ... MODIFY ENUM khusus MySQL dan dilewati pada SQLite, karena kode AI ditulis dual-driver.")

    h3(pdf, "2.2. Analisis Data")
    para(pdf, "Data yang dikumpulkan dianalisis dengan rekayasa fitur dan normalisasi. Dari deret 12 bulan dibentuk vektor 9 fitur untuk tiap titik waktu, sebagaimana Tabel 3.")
    cap(pdf, "Tabel 3. Fitur masukan model")
    table(pdf, (0.08 * W, 0.30 * W, 0.62 * W), [
        ("No", "Fitur", "Keterangan"),
        ("1", "lag_1_bulan", "Pemakaian satu bulan sebelumnya"),
        ("2", "lag_2_bulan", "Pemakaian dua bulan sebelumnya"),
        ("3", "lag_3_bulan", "Pemakaian tiga bulan sebelumnya"),
        ("4", "rata_rata_6_bulan", "Rata-rata pemakaian 6 bulan terakhir"),
        ("5", "rata_rata_12_bulan", "Rata-rata pemakaian 12 bulan terakhir"),
        ("6", "bulan", "Nomor bulan 1-12 untuk menangkap pola musiman"),
        ("7", "trend_3_bulan", "Kemiringan (slope) regresi linear 3 bulan terakhir"),
        ("8", "stok_saat_ini", "Stok berjalan (stok faskes; cadangan batch/gudang)"),
        ("9", "tipe_faskes", "1 untuk puskesmas, 0 untuk pustu"),
    ])
    para(pdf, "Normalisasi dilakukan karena skala fitur sangat timpang (mis. lag ratusan unit vs tipe 0/1). Setiap fitur distandardisasi dengan z-score memakai mean dan std dari data training (AnnScaler); bila std nyaris nol (< 1e-8, fitur konstan), pembaginya diganti 1,0. Target (jumlah pemakaian) juga dinormalisasi lalu dikembalikan (denormalisasi / post-processing) saat prediksi agar keluar dalam satuan asli; parameter normalisasi ikut disimpan bersama bobot model.")

    h3(pdf, "2.3. Perancangan Model Arsitektur ANN")
    para(pdf, "Arsitektur yang dirancang adalah Multilayer Perceptron (MLP) 9-12-8-1, sebagaimana Tabel 4, dengan total 233 parameter yang dipelajari.")
    cap(pdf, "Tabel 4. Rancangan arsitektur model ANN")
    table(pdf, (0.24 * W, 0.16 * W, 0.22 * W, 0.38 * W), [
        ("Lapis", "Neuron", "Aktivasi", "Parameter (bobot + bias)"),
        ("Masukan", "9", "-", "-"),
        ("Tersembunyi 1", "12", "ReLU", "12 x 9 + 12 = 120"),
        ("Tersembunyi 2", "8", "ReLU", "8 x 12 + 8 = 104"),
        ("Keluaran", "1", "Linear", "1 x 8 + 1 = 9"),
    ])
    para(pdf, "Pembelajaran mengikuti tahapan Backpropagation: inisialisasi bobot awal, tahap aktivasi (forward pass menghitung output aktual lapis tersembunyi dan lapis keluaran), weight training (menghitung gradien error lapis keluaran lalu lapis tersembunyi dan mengubah bobot), dan tahap iterasi (pengulangan epoch hingga error minimal atau early stopping). Bobot diinisialisasi metode He (w = randn x akar(2/fan_in), bias 0) yang adaptif per lapis; komposisi lapis tersembunyi 12-8 merupakan ketetapan konfigurasi sistem.")
    cap(pdf, "Tabel 5. Perbandingan dengan penelitian acuan (BPNN prediksi obat Puskesmas)")
    table(pdf, (0.26 * W, 0.37 * W, 0.37 * W), [
        ("Aspek", "Penelitian acuan", "Sistem ini"),
        ("Arsitektur", "12-12-1 (12 input = 12 bulan)", "9-12-8-1 (9 fitur rekayasa)"),
        ("Normalisasi", "Min-max ke rentang [0,1, 0,9]", "Z-score per fitur + target"),
        ("Aktivasi", "Logsig hidden, purelin output", "ReLU hidden, linear output"),
        ("Split data", "70% training / 30% testing", "80% training / 20% testing"),
        ("Iterasi", "Epoch 1000 tetap", "Epoch 800 + early stopping patience 20"),
        ("Pembelajaran", "Learning rate 0,1 + momentum 0,9", "SGD lr 0,01 + regularisasi L2"),
        ("Perkakas", "MATLAB", "PHP murni (tanpa library ML)"),
        ("Metrik", "MAPE dan akurasi = 100% - MAPE", "R2, MAE, MAPE (mentah)"),
        ("Keluaran", "Tabel prediksi tahunan", "Prediksi 3 bulan + CI, terintegrasi RKO/permintaan"),
    ])
    cap(pdf, "Tabel 6. Hiperparameter training")
    table(pdf, (0.30 * W, 0.70 * W), [
        ("Aspek", "Nilai / Ketentuan"),
        ("Learning rate", "0,01"),
        ("Epoch maksimal", "800, dengan early stopping patience 20 epoch pada split validasi internal"),
        ("Regularisasi L2", "1e-4"),
        ("Split train/test", "80/20; metrik hanya dihitung bila test set berisi minimal 2 sampel"),
        ("Syarat data", "Minimal 6 bulan berbeda dengan pemakaian > 0 dan minimal 2 vektor fitur"),
        ("Jadwal", "Cron tiap Minggu jam 02:00; maks 500 kombinasi per jalan; model aktif dilewati kecuali --force"),
    ])

    h3(pdf, "2.4. Pelaksanaan Prediksi")
    para(pdf, "Setelah data valid dan arsitektur siap, prediksi dilaksanakan. Model ANN memprediksi 3 bulan ke depan secara autoregresif (hasil bulan ke-i menjadi lag bulan berikutnya) disertai confidence interval 95% (+-1,96 SD historis); hasil dibulatkan dan tidak negatif. Bila data belum cukup, dipakai moving average. Perbandingan kedua metode pada Tabel 7.")
    cap(pdf, "Tabel 7. Metode prediksi")
    table(pdf, (0.26 * W, 0.37 * W, 0.37 * W), [
        ("Aspek", "ANN (ann_php)", "Moving Average (moving_average)"),
        ("Syarat", "Data >= 6 bulan, model aktif", "Data < 6 bulan (status data_belum_cukup)"),
        ("Cara kerja", "Prediksi 3 bulan autoregresif; hasil bulan ke-i menjadi lag bulan berikutnya", "Rata-rata 3 bulan terakhir, diiterasi 3 bulan"),
        ("Rentang", "CI 95%: +-1,96 SD historis", "CI 95%: +-1,96 SD jendela (bawah >= 0)"),
        ("Keluaran", "Bulat, tidak negatif", "Bulat, tidak negatif"),
    ])
    para(pdf, "Status model lain: kadaluarsa (digantikan training baru) dan gagal (pesan error tersimpan); keduanya tidak menghasilkan prediksi. Nilai metode lama (ai_gradient_boost, ai_random_forest) dipertahankan untuk data histori.")
    cap(pdf, "Tabel 8. Metrik evaluasi per model")
    table(pdf, (0.25 * W, 0.75 * W), [
        ("Metrik", "Keterangan"),
        ("R2 (akurasi_r2)", "Koefisien determinasi 0-1; makin dekat 1 makin baik"),
        ("MAE (mae)", "Mean Absolute Error; rata-rata selisih absolut vs aktual"),
        ("MAPE (mape)", "Mean Absolute Percentage Error dalam persen; periode aktual nol dilewati"),
    ])
    para(pdf, "Catatan konvensi: pada penelitian acuan, akurasi didefinisikan sebagai 100% dikurangi MAPE. Sistem ini menyimpan ketiga metrik dalam bentuk mentah (R2/MAE/MAPE) dan tidak menghitung skor akurasi turunan tersebut.")

    h2(pdf, "3. Hasil dan Pembahasan")
    h3(pdf, "3.1. Data")
    para(pdf, "Tabel 9 menyajikan deret ilustrasi 12 bulan yang dipakai pada seluruh contoh dokumen ini (format mengikuti Tabel 1 penelitian acuan).")
    cap(pdf, "Tabel 9. Data pemakaian ilustrasi (unit)")
    table(pdf, (0.25 * W, 0.35 * W, 0.40 * W), [
        ("Bulan", "Pemakaian (unit)", "Keterangan"),
        ("Januari", "95", "Nilai minimum deret"),
        ("Februari", "100", ""),
        ("Maret", "110", ""),
        ("April", "105", ""),
        ("Mei", "115", ""),
        ("Juni", "120", ""),
        ("Juli", "118", ""),
        ("Agustus", "125", ""),
        ("September", "130", ""),
        ("Oktober", "120", ""),
        ("November", "135", "Nilai maksimum deret"),
        ("Desember", "128", ""),
    ])

    h3(pdf, "3.2. Normalisasi Data")
    para(pdf, "Normalisasi memakai Rumus 1 dengan mean 116,75 dan std populasi 11,7836 dari deret Tabel 9; hasilnya pada Tabel 10 (format mengikuti Tabel 2 penelitian acuan).")
    rumus(pdf, "Rumus 1.", "x' = (x - mean) / std")
    cap(pdf, "Tabel 10. Data setelah normalisasi (z-score)")
    table(pdf, (0.25 * W, 0.35 * W, 0.40 * W), [
        ("Bulan", "Nilai z-score", "Contoh hitung"),
        ("Januari", "-1,8458", "(95 - 116,75) / 11,7836"),
        ("Februari", "-1,4215", ""),
        ("Maret", "-0,5728", ""),
        ("April", "-0,9971", ""),
        ("Mei", "-0,1485", ""),
        ("Juni", "0,2758", ""),
        ("Juli", "0,1061", ""),
        ("Agustus", "0,7001", ""),
        ("September", "1,1244", ""),
        ("Oktober", "0,2758", ""),
        ("November", "1,5488", ""),
        ("Desember", "0,9547", ""),
    ])
    para(pdf, "Pembanding metode: dengan rumus min-max penelitian acuan (rentang 0,1-0,9; min 95, maks 135), nilai 128 menjadi 0,8 x (128-95)/(135-95) + 0,1 = 0,76. Kedua metode sahih dan hanya berbeda skala; sistem memakai z-score.")

    h3(pdf, "3.3. Penentuan Pola Data Latih dan Data Uji")
    para(pdf, "Deret 12 bulan menghasilkan 9 vektor fitur valid karena vektor pertama baru dapat dibentuk pada bulan ke-4 (membutuhkan lag_1 sampai lag_3). Split 80/20 memakai floor(9 x 0,8) = 7 vektor training dan 2 vektor testing; karena test set berisi minimal 2 sampel, ketiga metrik evaluasi terhitung.")
    cap(pdf, "Tabel 11. Pola data latih dan data uji (ilustrasi 12 bulan)")
    table(pdf, (0.40 * W, 0.30 * W, 0.30 * W), [
        ("Tahap", "Perhitungan", "Jumlah"),
        ("Deret bulanan", "Jendela 12 bulan", "12 titik"),
        ("Vektor fitur valid", "Bulan ke-4 s/d ke-12", "9 vektor"),
        ("Training (80%)", "floor(9 x 0,8)", "7 vektor"),
        ("Testing (20%)", "9 - 7", "2 vektor"),
    ])
    para(pdf, "Catatan: nilai R2 hasil evaluasi dijepit pada rentang 0-1 sebelum disimpan. Bila test set berisi kurang dari 2 sampel, ketiga metrik dibiarkan NULL (belum terukur) dan model tetap dapat dipakai selama berstatus aktif.")

    h3(pdf, "3.4. Perancangan Model Arsitektur ANN")
    para(pdf, "Rancangan mengikuti Tabel 4 dengan tahapan Backpropagation sebagaimana diuraikan pada 2.3. Struktur aliran model disajikan pada Gambar 2; legenda lambang pada Tabel 12; persamaan yang dipakai pada Rumus 2 sampai Rumus 4.")
    center(pdf, "[9 fitur]  ->  [Tersembunyi 12, ReLU]  ->  [Tersembunyi 8, ReLU]  ->  [1 keluaran, Linear]", True)
    center(pdf, "Gambar 2. Arsitektur model ANN 9-12-8-1")
    pdf.ln(4)
    cap(pdf, "Tabel 12. Notasi pada algoritma backpropagation")
    table(pdf, (0.22 * W, 0.78 * W), [
        ("Simbol", "Arti"),
        ("x", "Vektor fitur masukan (9 fitur, lihat Tabel 3)"),
        ("t", "Nilai target (jumlah pemakaian aktual)"),
        ("w", "Bobot koneksi antar neuron"),
        ("b", "Bias / nilai ambang tiap neuron"),
        ("z", "Total masukan terbobot sebuah neuron"),
        ("a", "Keluaran aktivasi neuron tersembunyi"),
        ("y", "Keluaran prediksi neuron terakhir"),
        ("lr", "Learning rate / laju pembelajaran (0,01)"),
        ("L2", "Koefisien regularisasi bobot (1e-4)"),
        ("grad (delta)", "Gradien error hasil backpropagation"),
        ("n", "Jumlah sampel data"),
        ("SD", "Standar deviasi (simpangan baku)"),
        ("T", "Total prediksi pada horizon (rekomendasi)"),
        ("S", "Stok saat ini (rekomendasi)"),
    ])
    rumus(pdf, "Rumus 2.", "z = jumlah(w x x) + b;  a = max(0, z);  y = jumlah(w x a) + b")
    rumus(pdf, "Rumus 3.", "MSE = (1/n) x jumlah(y_pred - y_aktual)^2")
    rumus(pdf, "Rumus 4.", "w <- w - lr x (grad + L2 x w)")
    para(pdf, "Contoh satu neuron (ilustrasi): fitur lag_1 = 140 dengan mean 120 dan std 10 memberi z-score (140-120)/10 = 2,0. Neuron berbobot 0,5 dan bias -0,3 menghasilkan z = 0,5 x 2,0 - 0,3 = 0,7 dan aktivasi ReLU max(0; 0,7) = 0,7. Denormalisasi / post-processing: keluaran ternormalisasi 0,5 dengan yStd 20 dan yMean 100 menjadi 0,5 x 20 + 100 = 110 unit. Satu langkah SGD: w = 0,5 dengan gradien 0,4 menjadi 0,5 - 0,01 x (0,4 + 1e-4 x 0,5) = 0,4960. Inisialisasi He: lapis pertama (fan_in = 9) memakai simpangan akar(2/9) = 0,4714, mis. undian 0,63 memberi bobot awal 0,297.")

    h3(pdf, "3.5. Prediksi Kuantitas Kebutuhan Obat")
    para(pdf, "Contoh moving average: pemakaian 3 bulan terakhir 120, 135, 128 memberi rata-rata 128; SD sampel 7,51; margin 1,96 x 7,51 = 15; sehingga prediksi 128 (range 113-143).")
    rumus(pdf, "Rumus 5.", "MA = jumlah(y_jendela) / window;  CI 95% = MA +- 1,96 x SD")
    cap(pdf, "Tabel 13. Langkah moving average + CI (ilustrasi)")
    table(pdf, (0.30 * W, 0.40 * W, 0.30 * W), [
        ("Langkah", "Perhitungan", "Hasil"),
        ("1. Rata-rata", "(120 + 135 + 128) / 3 = 383 / 3", "127,67 dibulatkan 128"),
        ("2. SD (sampel)", "Jumlah kuadrat selisih = 112,67; dibagi 2; diakarkan", "SD = 7,51"),
        ("3. Margin 95%", "1,96 x 7,51", "14,71 dibulatkan 15"),
        ("4. Prediksi + CI", "128; bawah = max(0, 128-15); atas = 128+15", "128 (range 113-143)"),
    ])
    para(pdf, "Contoh metrik: aktual [100, 120] vs prediksi [110, 115] memberi MAE = (10+5)/2 = 7,5; MAPE = ((0,1)+(0,0417))/2 x 100% = 7,08%; R2 = 1 - 125/200 = 0,375 (rata-rata aktual 110; SS_tot 200; SS_res 125).")
    rumus(pdf, "Rumus 6.", "MAE = mean|y - t|;  MAPE = mean(|y - t| / t) x 100%;  R2 = 1 - SS_res / SS_tot")
    cap(pdf, "Tabel 14. Contoh metrik evaluasi (ilustrasi)")
    table(pdf, (0.22 * W, 0.48 * W, 0.30 * W), [
        ("Metrik", "Perhitungan", "Hasil"),
        ("MAE", "(|100-110| + |120-115|) / 2 = (10 + 5) / 2", "7,5"),
        ("MAPE", "((10/100) + (5/120)) / 2 x 100%", "7,08%"),
        ("R2", "Rata-rata aktual = 110. SS_tot = 200. SS_res = 125", "0,375"),
    ])
    para(pdf, "Mekanisme autoregresif (ilustrasi): prediksi bulan ke-1 memakai seluruh lag aktual menghasilkan 132; bulan ke-2 memakai lag_1 = 132 menghasilkan 129; bulan ke-3 memakai lag_1 = 129 dan lag_2 = 132 menghasilkan 131.")
    cap(pdf, "Tabel 15. Rollout autoregresif 3 bulan (ilustrasi)")
    table(pdf, (0.22 * W, 0.40 * W, 0.38 * W), [
        ("Bulan", "Sumber lag_1", "Hasil ilustrasi"),
        ("Bulan ke-1", "Data aktual (128)", "132"),
        ("Bulan ke-2", "Prediksi bulan ke-1 (132)", "129"),
        ("Bulan ke-3", "Prediksi bulan ke-2 (129)", "131"),
    ])
    para(pdf, "Hasil prediksi diubah menjadi rekomendasi pengadaan dengan Rumus 7. Contoh: total prediksi 3 bulan 300 unit dan stok 200 unit memberi ceil(300 x 1,20 - 200) = 160 unit; coverage (200/100) x 30,44 = 60,9 hari sehingga berstatus Perlu Pesan. Pembanding: stok 400 memberi rekom 0 (Aman); stok 30 memberi rekom 330 dengan coverage 9,13 hari < 21 hari (Kritis).")
    rumus(pdf, "Rumus 7.", "rekom = max(0, ceil(T x 1,20 - S))")
    cap(pdf, "Tabel 16. Contoh rekomendasi pengadaan (ilustrasi)")
    table(pdf, (0.30 * W, 0.40 * W, 0.30 * W), [
        ("Langkah", "Perhitungan", "Hasil"),
        ("1. Kebutuhan + safety 20%", "300 x 1,20", "360"),
        ("2. Rekomendasi", "ceil(360 - 200), min 0", "160 unit"),
        ("3. Rata-rata bulanan", "300 / 3", "100 unit/bulan"),
        ("4. Coverage", "(200 / 100) x 30,44 hari", "60,9 hari"),
        ("5. Status", "rekom > 0 dan coverage >= 21 hari", "Perlu Pesan"),
    ])
    para(pdf, "Uji kombinasi parameter berikut adalah ilustrasi grid-search mengikuti gaya Tabel 5 penelitian acuan, bukan hasil eksperimen yang dijalankan. Konfigurasi aktual sistem tetap: learning rate 0,01; epoch 800; L2 1e-4; patience 20.")
    cap(pdf, "Tabel 17. Ilustrasi pengujian parameter")
    table(pdf, (0.24 * W, 0.18 * W, 0.18 * W, 0.40 * W), [
        ("Learning rate", "Epoch", "L2", "Perilaku ilustratif"),
        ("0,005", "800", "1e-4", "Konvergen lambat, butuh epoch lebih banyak"),
        ("0,01", "800", "1e-4", "Stabil - konfigurasi aktif sistem"),
        ("0,05", "800", "1e-4", "Berisiko osilasi, loss tidak turun mulus"),
        ("0,01", "400", "1e-4", "Berhenti prematur, akurasi di bawah optimal"),
        ("0,01", "800", "1e-3", "Regularisasi terlalu kuat, model kurang peka"),
    ])
    para(pdf, "Pemanfaatan hasil: RKO terisi otomatis (keterangan Prediksi AI: jumlah + range, atau tombol Generate dari Prediksi untuk semua obat aktif dengan penanda kolom AI; rumus Kemenkes tetap berlaku) dan petugas faskes dapat membuat permintaan obat langsung dari halaman Prediksi AI. Hak akses pada Tabel 18; pengoperasian via php artisan ai:train-models (opsi --fasilitas-id, --obat-id, --force), menu Prediksi AI, dan agregat Dashboard AI. Pengujian otomatis: MovingAverageServiceTest (7 test) dan AiTrainModelsCommandTest (2 test).")
    cap(pdf, "Tabel 18. Hak akses fitur prediksi")
    table(pdf, (0.44 * W, 0.14 * W, 0.14 * W, 0.14 * W, 0.14 * W), [
        ("Permission", "Super Admin", "Gudang", "Dinas", "Puskes. / Pustu"),
        ("view_model_prediksi", "Ya", "Ya", "Ya", "Ya / Tidak"),
        ("view_prediksi_kebutuhan", "Ya", "Ya", "Ya", "Ya / Ya"),
        ("create/update/delete", "Ya", "Tidak", "Tidak", "Tidak"),
    ])

    h2(pdf, "4. Kesimpulan")
    para(pdf, "Fitur prediksi kebutuhan obat menerapkan Jaringan Saraf Tiruan MLP 9-12-8-1 yang ditulis murni dengan PHP: data pemakaian diagregasi bulanan, direkayasa menjadi 9 fitur, dinormalisasi z-score, dilatih dengan SGD + early stopping, dievaluasi dengan R2/MAE/MAPE, lalu dipakai memprediksi 3 bulan ke depan secara autoregresif dengan fallback moving average bila data belum cukup.")
    para(pdf, "Dibanding penelitian acuan (BPNN 12-12-1, min-max, MATLAB, keluaran tabel tahunan), sistem ini memakai fitur rekayasa yang lebih kaya, fungsi aktivasi ReLU, split 80/20, penyimpanan model ganda (JSON + file), serta integrasi langsung ke RKO dan permintaan obat sehingga hasil prediksi dapat dieksekusi menjadi perencanaan.")
    para(pdf, "Batasan yang perlu dicatat: komposisi lapis tersembunyi 12-8 adalah ketetapan konfigurasi (bukan hasil trial-and-error); model membutuhkan minimal 6 bulan berisi agar ANN dipakai; sistem menyimpan metrik mentah tanpa skor akurasi turunan 100%-MAPE.")
    para(pdf, "Saran pengembangan: uji variasi hiperparameter secara sistematis, penjadwalan retraining adaptif berbasis drift akurasi, serta penambahan fitur kalender dan musim penyakit.")

    h2(pdf, "Daftar Rujukan")
    pdf.set_x(pdf.l_margin)
    pdf.set_font("Helvetica", "", 10.5)
    pdf.set_text_color(*BLACK)
    pdf.multi_cell(0, 6, "[1]  F. Khairati dan H. Putra, Prediksi Kuantitas Penggunaan Obat pada Layanan Kesehatan Menggunakan Algoritma Backpropagation Neural Network, Jurnal Sistim Informasi dan Teknologi, vol. 4, no. 3, hal. 128-135, 2022, doi: 10.37034/jsisfotek.v4i3.158.", align="J")
    pdf.ln(2)


def main() -> None:
    try:
        with open(OUTPUT, "ab"):
            pass
    except OSError:
        sys.exit(f"Gagal menulis {OUTPUT}: file sedang dibuka aplikasi lain. Tutup dulu lalu jalankan ulang.")
    pdf = DocPDF(orientation="P", unit="mm", format="A4")
    pdf.alias_nb_pages("{nb}")
    pdf.set_auto_page_break(True, margin=20)
    pdf.set_margins(22, 20, 22)
    pdf.add_page()
    build(pdf)
    pdf.output(OUTPUT)
    print(f"OK: {OUTPUT} ({pdf.page_no()} halaman)")


if __name__ == "__main__":
    main()
