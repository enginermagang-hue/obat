# Laporan & Dokumen

RUANG OBAT menyediakan dokumen cetak dalam format **PDF** dan **XLS** untuk kebutuhan pelaporan.

## Jenis Dokumen

| Dokumen | Deskripsi | Format Cetak |
|---------|-----------|-------------|
| **LPLPO** | Laporan Pemakaian & Lembar Permintaan Obat | PDF |
| **RKO** | Rencana Kebutuhan Obat | PDF, XLS |
| **Neraca Tahunan** | Ringkasan stok akhir tahun | PDF, XLS |
| **Faktur Distribusi** | Bukti distribusi obat | PDF |
| **Faktur Penerimaan** | Bukti penerimaan stok | PDF |
| **Faktur Permintaan** | Bukti permintaan obat | PDF |

## Langkah-Langkah: LPLPO

1. Buka menu **Laporan** → **LPLPO**.

![Halaman LPLPO](/screenshots/admin-lplpo.png)

2. Klik **"Buat laporan"**.
3. Pilih **Faskes** dan **Periode** laporan.
4. Sistem akan menampilkan data pemakaian obat berdasarkan transaksi yang sudah tercatat.
5. Periksa dan sesuaikan jumlah pemakaian jika perlu.
6. Klik **"Simpan"**.

### Cetak LPLPO

1. Pada halaman detail LPLPO, klik tombol **"Cetak PDF"**.
2. Atur ukuran kertas, orientasi, dan margin (jika perlu).
3. Klik **"Download"** untuk mengunduh file PDF.

## Langkah-Langkah: RKO (Rencana Kebutuhan Obat)

1. Buka menu **Laporan** → **RKO**.

![Halaman RKO](/screenshots/admin-rko.png)

2. Klik **"Buat RKO"**.
3. Pilih **Faskes**, **Periode**, dan **Sumber Dana**.
4. Sistem akan menghitung estimasi kebutuhan berdasarkan:
   - Pemakaian periode sebelumnya.
   - Stok tersisa saat ini.
   - Lead time pengadaan.
5. Periksa dan sesuaikan estimasi.
6. Klik **"Simpan"**.

### Cetak / Export RKO

- Klik **"Cetak PDF"** untuk mencetak dokumen RKO.
- Klik **"Export XLS"** untuk mengunduh dalam format Excel.

## Pengaturan Cetak

1. Buka menu **Sistem** → **Pengaturan** → klik **Pengaturan PDF**.
2. Atur parameter cetak:
   - **Ukuran Kertas**: A4, F4/Folio, Letter, Legal
   - **Orientasi**: Portrait / Landscape
   - **Jenis Huruf**: DejaVu Sans, Times New Roman, dll.
   - **Ukuran Font**: Kop surat, body, tanda tangan.
   - **Margin**: Atas, Bawah, Kiri, Kanan.
3. Klik **"Simpan sebagai Default"** agar diterapkan ke semua cetakan.

> Semua halaman cetak mampu mengikuti pengaturan kop surat dan margin yang sudah ditentukan.
