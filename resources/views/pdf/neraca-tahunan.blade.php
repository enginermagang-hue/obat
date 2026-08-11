@extends('pdf.layouts.base')

@section('title', 'Neraca Tahunan - ' . $neraca->nomor_neraca)

@section('subtitle', 'NERACA TAHUNAN STOK OBAT DAN BMHP')
@section('subtitle_desc', strtoupper($neraca->fasilitas?->nama ?? 'GUDANG DINAS KESEHATAN') . ' — TAHUN ' . $neraca->tahun)

@section('content')
    @include('pdf.components.info-table', ['items' => [
        ['label' => 'Nomor Neraca', 'value' => $neraca->nomor_neraca],
        ['label' => 'Status', 'value' => $neraca->status === 'selesai' ? 'Selesai' : 'Draft'],
        ['label' => 'Tanggal Cetak', 'value' => now()->format('d F Y')],
    ]])

    @if($sumberDanaList->count() > 0)
    <div style="font-size: {{ $pdfItemsSize }}pt; margin-bottom: 8px;">
        <strong>Sumber Dana:</strong>
        @foreach($sumberDanaList as $sd)
            {{ $sd->kode }}@if(!$loop->last), @endif
        @endforeach
    </div>
    @endif

    @php
        $grouped = $details->groupBy(fn ($d) => $d->obat?->kategori ?? 'LAINNYA');
        $grouped = $grouped->sortKeys();
        $totalCols = 9;
    @endphp

    <table class="items" style="table-layout: fixed;">
        <thead>
            <tr>
                <th rowspan="2" style="width: 4%; text-align: center;">No</th>
                <th rowspan="2" style="width: 40%;">Nama Obat/BMHP</th>
                <th rowspan="2" style="width: 5%; text-align: center;">Satuan</th>
                <th rowspan="2" style="width: 8%; text-align: center;">Stok Awal</th>
                <th rowspan="2" style="width: 9%; text-align: center;">Mutasi Masuk</th>
                <th rowspan="2" style="width: 9%; text-align: center;">Mutasi Keluar</th>
                <th rowspan="2" style="width: 8%; text-align: center;">Stok Akhir</th>
                <th rowspan="2" style="width: 7%; text-align: right;">Harga Sat.</th>
                <th rowspan="2" style="width: 10%; text-align: right;">Saldo Persediaan (Rp)</th>
            </tr>
            <tr></tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @foreach($grouped as $kategori => $items)
            <tr>
                <td colspan="{{ $totalCols }}" style="background-color: #FFFF00; font-weight: bold; font-size: {{ $pdfItemsSize }}pt;">
                    {{ strtoupper($kategori) }}
                </td>
            </tr>
            @foreach($items as $detail)
            @php
                $sdDetails = $detail->sumberDanaDetails->keyBy('sumber_dana_id');
                $hargaSatuan = $detail->harga_satuan ?? 0;
                if ($sdDetails->isNotEmpty()) {
                    $stokAwal = $sdDetails->sum('stok_awal_jumlah');
                    $masuk = $sdDetails->sum('masuk_jumlah');
                    $keluar = $sdDetails->sum('keluar_jumlah');
                } else {
                    $stokAwal = $detail->stok_awal ?? 0;
                    $masuk = $detail->total_masuk ?? 0;
                    $keluar = $detail->total_keluar ?? 0;
                }
                $stokAkhir = $stokAwal + $masuk - $keluar;
                $saldoPersediaan = $stokAkhir * $hargaSatuan;
            @endphp
            <tr>
                <td class="text-center">{{ $no++ }}</td>
                <td>{{ $detail->obat?->nama_obat ?? '-' }}</td>
                <td class="text-center">{{ $detail->obat?->satuan ?? '-' }}</td>
                <td class="text-right">{{ $stokAwal > 0 ? number_format($stokAwal, 0, ',', '.') : '-' }}</td>
                <td class="text-right">{{ $masuk > 0 ? number_format($masuk, 0, ',', '.') : '-' }}</td>
                <td class="text-right">{{ $keluar > 0 ? number_format($keluar, 0, ',', '.') : '-' }}</td>
                <td class="text-right">{{ $stokAkhir > 0 ? number_format($stokAkhir, 0, ',', '.') : '-' }}</td>
                <td class="text-right">{{ $hargaSatuan > 0 ? number_format($hargaSatuan, 0, ',', '.') : '-' }}</td>
                <td class="text-right">{{ $saldoPersediaan > 0 ? number_format($saldoPersediaan, 0, ',', '.') : '-' }}</td>
            </tr>
            @endforeach
            @endforeach
        </tbody>
    </table>
@endsection

@section('signature')
    @include('pdf.components.tanda-tangan', ['columns' => [
        ['label' => 'Mengetahui', 'nama' => $neraca->dibuatOleh?->name ?? 'Petugas', 'jabatan' => ''],
    ]])
@endsection
