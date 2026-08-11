@extends('pdf.layouts.base')

@section('title', 'Faktur Penerimaan - ' . $penerimaan->nomor_penerimaan)

@section('subtitle', 'FAKTUR PENERIMAAN OBAT')
@section('subtitle_desc', 'No: ' . $penerimaan->nomor_penerimaan)

@section('content')
    @include('pdf.components.info-table', ['items' => [
        ['label' => 'Nomor Penerimaan', 'value' => $penerimaan->nomor_penerimaan],
        ['label' => 'Tanggal Penerimaan', 'value' => $penerimaan->tanggal_penerimaan?->format('d F Y') ?? '-'],
        ['label' => 'Tipe', 'value' => match($penerimaan->tipe) {
            'pembelian' => 'Pembelian',
            'hibah' => 'Hibah',
            'stok_awal' => 'Stok Awal',
            'penyesuaian' => 'Penyesuaian',
            'distribusi' => 'Distribusi',
            'manual' => 'Manual',
            default => $penerimaan->tipe,
        }],
        ['label' => 'Supplier', 'value' => $penerimaan->supplier?->nama ?? '-'],
        ['label' => 'Sumber Dana', 'value' => $penerimaan->sumberDana?->nama ?? '-'],
        ['label' => 'Fasilitas', 'value' => $penerimaan->fasilitas?->nama ?? 'Gudang'],
        ['label' => 'Petugas', 'value' => $penerimaan->user?->name ?? '-'],
    ]])

    @if ($penerimaan->tipe === 'distribusi' && $penerimaan->distribusi)
        @php $dist = $penerimaan->distribusi; @endphp
        @include('pdf.components.info-table', ['items' => [
            ['label' => 'No. Surat Jalan', 'value' => $dist->nomor_surat_jalan],
            ['label' => 'Tanggal Kirim', 'value' => $dist->tanggal_kirim?->format('d F Y') ?? '-'],
            ['label' => 'Pengirim', 'value' => $dist->fasilitasPengirim?->nama ?? '-'],
        ]])
    @endif

    <table class="items">
        <thead>
            <tr>
                <th style="width: 24px; text-align: center;">No</th>
                <th style="width: 55px;">Kode</th>
                <th>Nama Obat</th>
                <th style="width: 50px;">Batch</th>
                <th style="width: 45px;">Expired</th>
                <th style="width: 40px; text-align: center;">Jumlah</th>
                <th style="width: 65px; text-align: right;">Harga Satuan</th>
                <th style="width: 70px; text-align: right;">Sub Total</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @forelse($penerimaan->details as $detail)
            <tr>
                <td class="text-center">{{ $no++ }}</td>
                <td>{{ $detail->obat?->kode_obat ?? '-' }}</td>
                <td>{{ $detail->obat?->nama_obat ?? '-' }}</td>
                <td>{{ $detail->batch_number ?? '-' }}</td>
                <td>{{ $detail->tanggal_expired?->format('m/Y') ?? '-' }}</td>
                <td class="text-center">{{ number_format($detail->jumlah, 0, ',', '.') }}</td>
                <td class="text-right">{{ $detail->harga_satuan ? 'Rp ' . number_format($detail->harga_satuan, 0, ',', '.') : '-' }}</td>
                <td class="text-right">{{ $detail->sub_total ? 'Rp ' . number_format($detail->sub_total, 0, ',', '.') : '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center" style="padding: 12px; color: #9ca3af;">
                    Tidak ada item dalam penerimaan ini.
                </td>
            </tr>
            @endforelse
        </tbody>
        @if($penerimaan->details->count() > 0)
        <tfoot>
            <tr>
                <td colspan="5" class="text-right">Total</td>
                <td class="text-center">{{ number_format($totalQuantity, 0, ',', '.') }}</td>
                <td></td>
                <td class="text-right">{{ 'Rp ' . number_format($penerimaan->details->sum('sub_total'), 0, ',', '.') }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    @if($penerimaan->catatan)
    <div class="catatan">
        <span class="label">Catatan:</span>
        <br>{{ $penerimaan->catatan }}
    </div>
    @endif
@endsection

@section('signature')
    @include('pdf.components.tanda-tangan', ['columns' => [
        ['label' => 'Penerima', 'nama' => $penerimaan->user?->name ?? '____________________', 'jabatan' => 'Petugas Penerima'],
        ['label' => 'Mengetahui', 'nama' => 'Kepala ' . ($penerimaan->fasilitas?->nama ?? 'Gudang'), 'jabatan' => ''],
    ]])
@endsection
