@extends('pdf.layouts.base')

@section('title', 'RKO - ' . $laporan->nomor_rko)

@section('subtitle', 'RENCANA KEBUTUHAN OBAT (RKO) ' . $laporan->periode_tahun)
@section('subtitle_desc', strtoupper($laporan->fasilitas?->nama ?? '-'))

@push('styles')
<style>
    table.items {
        table-layout: fixed;
        width: 100%;
        font-size: 8pt;
    }
    table.items th,
    table.items td {
        font-size: 8pt;
        line-height: 1.2;
        padding: 2px 3px;
        word-wrap: break-word;
    }
    .info-table td {
        font-size: 10pt;
    }
    .tanda-tangan {
        margin-top: 8px;
    }
    .tanda-tangan > div {
        font-size: 10pt;
        line-height: 1.3;
    }
</style>
@endpush

@section('content')
    @include('pdf.components.info-table', ['items' => [
        ['label' => 'Nomor RKO', 'value' => $laporan->nomor_rko],   
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
                <th style="width: 3%; text-align: center;">No</th>
                <th style="width: 23%;">Nama Obat</th>
                <th style="width: 3.5%;">Satuan</th>
                <th style="width: 5.5%; text-align: right;">Pakai Th Lalu</th>
                <th style="width: 5.5%; text-align: right;">Rata²/Bln</th>
                <th style="width: 4.5%; text-align: right;">Sisa Stok</th>
                <th style="width: 5.5%; text-align: right;">Keb. 18 Bln</th>
                <th style="width: 5.5%; text-align: right;">Rencana Keb.</th>
                <th style="width: 4.5%; text-align: right;">Usulan</th>
                <th style="width: 3.5%; text-align: right;">Buffer%</th>
                <th style="width: 4.5%; text-align: right;">Buffer Qty</th>
                <th style="width: 5.5%; text-align: right;">Total Keb.</th>
                <th style="width: 6%; text-align: right;">Harga</th>
                <th style="width: 7%; text-align: right;">Total Harga</th>
                <th style="width: 13%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; $grandTotal = 0; @endphp
            @forelse($details as $detail)
            <tr>
                <td class="text-center">{{ $no++ }}</td>
                <td>{{ $detail->obat?->kode_obat ?? '-' }} <br/> {{ $detail->obat?->nama_obat ?? '-' }}</td>
                <td>{{ $detail->obat?->satuan ?? '-' }}</td>
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

@section('signature')
    <div style="width: 45%; margin-left: auto; text-align: left;">
        <div>{{ now()->format('d F Y') }}</div>
        <div>Mengetahui,</div>
        <div>---------------------------</div>
        <div style="margin-top: 50px;">___________________________</div>
        <div>NIP.</div>
    </div>
@endsection
