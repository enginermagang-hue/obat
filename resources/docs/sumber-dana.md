# Sumber Dana Obat

Modul **Sumber Dana** digunakan untuk mengelola data sumber dana (anggaran/budget) yang dialokasikan untuk pengadaan dan penggunaan obat di fasilitas kesehatan. Sumber dana membantu memisahkan transaksi berdasarkan asal pendanaan, sehingga pelaporan dan audit menjadi lebih terstruktur.

## Fungsi Sumber Dana

- Menyimpan data dana/budget yang dialokasikan untuk obat pada tahun anggaran tertentu.
- Mengelompokkan transaksi stok (penerimaan, distribusi, pemakaian) berdasarkan sumber dana.
- Menjadi acuan utama di **Laporan RKO (Rencana Kebutuhan Obat)** dan **Neraca Tahunan**.
- Memudahkan pelacakan realisasi penggunaan anggaran per sumber dana.

## Kolom Sumber Dana

| Kolom           | Tipe    | Keterangan                                                  |
| --------------- | ------- | ----------------------------------------------------------- |
| **Kode**        | String  | Kode unik sumber dana (contoh: `APBN-2025`, `BLUD-2025`).  |
| **Nama**        | String  | Nama program/sumber dana (contoh: "APBN 2025").             |
| **Tahun**       | Integer | Tahun anggaran (contoh: `2025`).                            |
| **Total Anggaran** | Decimal | Jumlah pagu anggaran (contoh: `Rp 150.000.000`).         |
| **Keterangan**  | Text    | Catatan tambahan (opsional).                                |
| **Status**      | Boolean | `Aktif` / `Nonaktif` — sumber dana nonaktif tidak muncul di form pilihan transaksi. |

## Langkah-Langkah: Menambah Sumber Dana

1. Buka menu **Master Data** → **Sumber Dana**.
2. Klik tombol **"Buat"** di pojok kanan atas.

   ![Halaman Sumber Dana](/screenshots/admin-sumber-dana.png)

3. Isi formulir Sumber Dana:
   - **Kode** — masukkan kode unik sumber dana. Contoh: `APBN-2025`.
   - **Nama Sumber Dana** — masukkan nama program. Contoh: "APBN 2025".
   - **Tahun** — pilih tahun anggaran dari dropdown.
   - **Total Anggaran (Pagu)** — masukkan nilai total pagu anggaran. Contoh: `150.000.000`.
   - **Keterangan** — (opsional) catatan tambahan.
   - **Aktif** — toggle `Aktif` jika sumber dana langsung digunakan.
4. Klik **"Simpan"** untuk menyimpan data.

## Langkah-Langkah: Mengedit Sumber Dana

1. Pada halaman daftar **Sumber Dana**, klik ikon **edit** (pensil) di baris sumber dana yang ingin diubah.
2. Ubah field yang diperlukan pada modal edit.
3. Klik **"Simpan"**.

## Langkah-Langkah: Mengaktifkan / Menonaktifkan Sumber Dana

Status sumber dana dapat diubah secara **massal** melalui *bulk action* di toolbar tabel:

1. Centang satu atau beberapa sumber dana pada tabel.
2. Pada toolbar, pilih salah satu aksi:
   - **Aktifkan** — untuk mengaktifkan sumber dana yang dipilih.
   - **Nonaktifkan** — untuk menonaktifkan sumber dana (akan diminta konfirmasi).
3. Sumber dana yang **nonaktif** tidak akan muncul di pilihan form transaksi (penerimaan, RKO, dll).

## Keterkaitan Sumber Dana dengan Modul Lain

Sumber dana terintegrasi dengan beberapa modul:

- **Penerimaan Stok** — saat menerima obat, pilih sumber dana asal.
- **Batch Stok** — setiap batch otomatis mewarisi `sumber_dana_id` dari penerimaan.
- **Distribusi** — batch yang didistribusikan membawa informasi sumber dana ke faskes tujuan.
- **Laporan RKO** — rencana kebutuhan dihitung per sumber dana.
- **Neraca Tahunan** — ringkasan stok akhir tahun dipecah per sumber dana.
- **AI Prediksi** — estimasi kebutuhan obat mempertimbangkan data historis per sumber dana.

## Tips Penggunaan

- **Buat satu sumber dana per tahun** untuk setiap program pendanaan. Misalnya: `APBN-2025`, `APBD-2025`, `BLUD-2025`.
- **Nonaktifkan** sumber dana yang sudah tidak berlaku di tahun berikutnya, jangan dihapus (untuk menjaga histori transaksi).
- **Pastikan total anggaran realistis** karena nilai ini akan dibandingkan dengan realisasi penggunaan di laporan.
- Sumber dana **tidak bisa dihapus** jika sudah pernah digunakan dalam transaksi; gunakan fitur **nonaktifkan** sebagai gantinya.
