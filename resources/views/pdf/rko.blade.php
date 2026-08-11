@extends('pdf.layouts.base')

@section('title', 'RKO - ' . $laporan->nomor_rko)

@section('subtitle', 'RENCANA KEBUTUHAN OBAT (RKO)')
@section('subtitle_desc', strtoupper($laporan->fasilitas?->nama ?? '-'))

@section('content')
    @include('pdf.components.info-table', ['items' => [
        ['label' => 'Nomor RKO', 'value' => $laporan->nomor_rko],
        ['label' => 'Puskesmas', 'value' => $laporan->fasilitas?->nama ?? '-'],
        ['label' => 'PIC', 'value' => $laporan->fasilitas?->pic ?? '-'],
        ['label' => 'Kontak PIC', 'value' => $laporan->fasilitas?->kontak_pic ?? '-'],
        ['label' => 'Periode Tahun', 'value' => $laporan->periode_tahun],
        ['label' => 'Status', 'value' => 'Disetujui'],
        ['label' => 'Total Anggaran', 'value' => 'Rp ' . number_format((float) $laporan->total_anggaran, 0, ',', '.')],
        ['label' => 'Tanggal Pembuatan', 'value' => $laporan->tanggal_pembuatan?->format('d/m/Y') ?? '-'],
        ['label' => 'Tanggal Pengajuan', 'value' => $laporan->tanggal_pengajuan?->format('d/m/Y') ?? '-'],
        ['label' => 'Tanggal Disetujui', 'value' => $laporan->tanggal_disetujui?->format('d/m/Y') ?? '-'],
        ['label' => 'Dibuat Oleh', 'value' => $laporan->dibuatOleh?->name ?? '-'],
        ['label' => 'Disetujui Oleh', 'value' => $laporan->disetujuiOleh?->name ?? '-'],
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
                <th style="width: 22px; text-align: center;">No</th>
                <th style="width: 38px;">Kode</th>
                <th>Nama Obat</th>
                <th style="width: 38px;">Satuan</th>
                <th style="width: 30px; text-align: center;">ABC</th>
                <th style="width: 30px; text-align: center;">VEN</th>
                <th style="width: 45px; text-align: right;">Pakai Th Lalu</th>
                <th style="width: 45px; text-align: right;">Rata²/Bln</th>
                <th style="width: 40px; text-align: right;">Sisa Stok</th>
                <th style="width: 45px; text-align: right;">Keb. 18 Bln</th>
                <th style="width: 45px; text-align: right;">Rencana Keb.</th>
                <th style="width: 40px; text-align: right;">Usulan</th>
                <th style="width: 35px; text-align: right;">Buffer%</th>
                <th style="width: 40px; text-align: right;">Buffer Qty</th>
                <th style="width: 45px; text-align: right;">Total Keb.</th>
                <th style="width: 55px; text-align: right;">Harga</th>
                <th style="width: 60px; text-align: right;">Total Harga</th>
                <th style="width: 60px;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; $grandTotal = 0; @endphp
            @forelse($details as $detail)
            <tr>
                <td class="text-center">{{ $no++ }}</td>
                <td>{{ $detail->obat?->kode_obat ?? '-' }}</td>
                <td>{{ $detail->obat?->nama_obat ?? '-' }}</td>
                <td>{{ $detail->obat?->satuan ?? '-' }}</td>
                <td class="text-center">{{ $detail->abc_kategori ?? '-' }}</td>
                <td class="text-center">{{ $detail->ven_kategori ?? '-' }}</td>
                <td class="text-right">{{ number_format($detail->pemakaian_tahun_sebelumnya, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($detail->rata_rata_pemakaian_bulanan, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($detail->stok_akhir, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($detail->kebutuhan_tahunan, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($detail->rencana_kebutuhan, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($detail->usulan, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($detail->buffer_stock_persen, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($detail->buffer_stok_qty, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($detail->total_kebutuhan, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($detail->harga_perkiraan, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($detail->total_harga, 0, ',', '.') }}</td>
                <td>{{ $detail->keterangan ?? '-' }}</td>
            </tr>
            @php $grandTotal += $detail->total_harga; @endphp
            @empty
            <tr>
                <td colspan="18" class="text-center" style="padding: 20px; color: #9ca3af;">
                    Tidak ada data obat
                </td>
            </tr>
            @endforelse
        </tbody>
        @if($details->count() > 0)
        <tfoot>
            <tr>
                <td colspan="16" class="text-right">Total Anggaran</td>
                <td class="text-right">{{ number_format($grandTotal, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>
@endsection
