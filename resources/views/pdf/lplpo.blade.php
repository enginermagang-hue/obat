@extends('pdf.layouts.base')

@section('title', 'LPLPO - ' . $laporan->nomor_laporan)

@section('subtitle', 'LAPORAN PEMAKAIAN DAN LEMBAR PERMINTAAN OBAT (LPLPO)')
@section('subtitle_desc', strtoupper($laporan->fasilitas?->nama ?? '-'))

@php
    $bulanList = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
    $namaBulan = $bulanList[$laporan->periode_bulan] ?? $laporan->periode_bulan;
@endphp

@section('content')
    @include('pdf.components.info-table', ['items' => [
        ['label' => 'Nomor Laporan', 'value' => $laporan->nomor_laporan],
        ['label' => 'Puskesmas', 'value' => $laporan->fasilitas?->nama ?? '-'],
        ['label' => 'PIC', 'value' => $laporan->fasilitas?->pic ?? '-'],
        ['label' => 'Kontak PIC', 'value' => $laporan->fasilitas?->kontak_pic ?? '-'],
        ['label' => 'Periode', 'value' => $namaBulan . ' ' . $laporan->periode_tahun],
        ['label' => 'Status', 'value' => match($laporan->status) {
            'draft' => 'Draft',
            'diajukan' => 'Diajukan',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
            default => $laporan->status,
        }],
        ['label' => 'Tanggal Cetak', 'value' => now()->format('d F Y')],
    ]])

    @if($laporan->catatan)
    <div class="catatan">
        <span class="label">Catatan:</span> {{ $laporan->catatan }}
    </div>
    @endif

    <table class="items">
        <thead>
            <tr>
                <th style="width: 25px; text-align: center;">No</th>
                <th style="width: 40px;">Kode</th>
                <th>Nama Obat</th>
                <th style="width: 40px;">Satuan</th>
                <th style="width: 45px; text-align: right;">Stok Awal</th>
                <th style="width: 45px; text-align: right;">Penerimaan</th>
                <th style="width: 45px; text-align: right;">Persediaan</th>
                <th style="width: 45px; text-align: right;">Pemakaian</th>
                <th style="width: 45px; text-align: right;">Sisa Stok</th>
                <th style="width: 50px; text-align: right;">Stok Opt.</th>
                <th style="width: 50px; text-align: right;">Permintaan</th>
                <th style="width: 60px;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @forelse($details as $detail)
            <tr>
                <td class="text-center">{{ $no++ }}</td>
                <td>{{ $detail->obat?->kode_obat ?? '-' }}</td>
                <td>{{ $detail->obat?->nama_obat ?? '-' }}</td>
                <td>{{ $detail->obat?->satuan ?? '-' }}</td>
                <td class="text-right">{{ number_format($detail->stok_awal, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($detail->jumlah_masuk, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($detail->stok_awal + $detail->jumlah_masuk, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($detail->jumlah_keluar, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($detail->sisa_stok, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($detail->stok_optimum ?? 0, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($detail->permintaan_selanjutnya, 0, ',', '.') }}</td>
                <td>{{ $detail->keterangan ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="12" class="text-center" style="padding: 20px; color: #9ca3af;">
                    Tidak ada data obat
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
@endsection

@section('signature')
    @include('pdf.components.tanda-tangan', ['columns' => [
        [
            'label' => 'Mengetahui',
            'nama' => $laporan->disetujuiOleh?->name ?? $laporan->dibuatOleh?->name ?? 'Petugas',
            'jabatan' => 'Kepala Dinas Kesehatan',
        ],
        [
            'label' => 'Menyerahkan',
            'nama' => $laporan->dibuatOleh?->name ?? 'Petugas',
            'jabatan' => 'Kepala Instalasi Farmasi',
        ],
        [
            'label' => 'Meminta',
            'nama' => $laporan->dibuatOleh?->name ?? 'Petugas',
            'jabatan' => 'Kepala Puskesmas',
        ],
        [
            'label' => 'Menerima',
            'nama' => $laporan->dibuatOleh?->name ?? 'Petugas',
            'jabatan' => 'Petugas Puskesmas',
        ],
    ]])
@endsection
