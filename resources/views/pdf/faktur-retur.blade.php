@extends('pdf.layouts.base')

@section('title', 'Faktur Retur Obat - ' . $retur->nomor_retur)

@section('subtitle', 'FAKTUR RETUR OBAT')
@section('subtitle_desc', 'No: ' . $retur->nomor_retur)

@section('content')
    @php
        $tipeLabel = match($retur->tipe_retur) {
            'puskesmas_ke_gudang' => 'Puskesmas → Gudang',
            'pustu_ke_puskesmas' => 'Pustu → Puskesmas',
            'gudang_ke_supplier' => 'Gudang → Supplier',
            default => $retur->tipe_retur,
        };
        $alasanLabel = match($retur->alasan) {
            'expired' => 'Kedaluwarsa',
            'rusak' => 'Rusak',
            'kelebihan_stok' => 'Kelebihan Stok',
            'salah_kirim' => 'Salah Kirim',
            'recall' => 'Recall',
            'near_expiry' => 'Mendekati Kedaluwarsa',
            'lainnya' => 'Lainnya',
            default => $retur->alasan,
        };
        $statusLabel = str_replace('_', ' ', ucfirst($retur->status));
    @endphp

    @include('pdf.components.info-table', ['items' => [
        ['label' => 'Nomor Retur', 'value' => $retur->nomor_retur],
        ['label' => 'Tanggal Retur', 'value' => $retur->tanggal_retur?->format('d F Y') ?? '-'],
        ['label' => 'Tipe Retur', 'value' => $tipeLabel],
        ['label' => 'Pengirim', 'value' => ($retur->fasilitasPengirim?->nama ?? ($retur->supplier?->nama ?? '-')) . ($retur->fasilitasPengirim?->alamat ? '<br>' . $retur->fasilitasPengirim->alamat : '')],
        ['label' => 'Penerima', 'value' => ($retur->fasilitasPenerima?->nama ?? ($retur->supplier?->nama ?? '-')) . ($retur->fasilitasPenerima?->alamat ? '<br>' . $retur->fasilitasPenerima->alamat : '')],
        ['label' => 'Alasan Retur', 'value' => $alasanLabel . ($retur->alasan_lainnya ? ': ' . $retur->alasan_lainnya : '')],
        ['label' => 'Status', 'value' => '<span class="status-badge status-' . $retur->status . '">' . $statusLabel . '</span>'],
    ]])

    <table class="items">
        <thead>
            <tr>
                <th style="width: 24px; text-align: center;">No</th>
                <th style="width: 60px;">Kode Obat</th>
                <th>Nama Obat</th>
                <th style="width: 55px;">No. Batch</th>
                <th style="width: 55px;">Expired</th>
                <th style="width: 40px; text-align: center;">Jumlah</th>
                <th style="width: 40px;">Satuan</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @forelse($retur->details as $detail)
            <tr>
                <td class="text-center">{{ $no++ }}</td>
                <td>{{ $detail->obat?->kode_obat ?? '-' }}</td>
                <td>{{ $detail->obat?->nama_obat ?? '-' }}</td>
                <td>{{ $detail->batch?->batch_number ?? '-' }}</td>
                <td>{{ $detail->batch?->tanggal_expired?->format('m/Y') ?? '-' }}</td>
                <td class="text-center">{{ number_format($detail->jumlah_retur, 0, ',', '.') }}</td>
                <td>{{ $detail->obat?->satuan ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center" style="padding: 12px; color: #9ca3af;">
                    Tidak ada item dalam retur ini.
                </td>
            </tr>
            @endforelse
        </tbody>
        @if($retur->details->count() > 0)
        <tfoot>
            <tr>
                <td colspan="5" class="text-right">Total Item</td>
                <td class="text-center">{{ number_format($retur->details->sum('jumlah_retur'), 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>

    @if($retur->catatan)
    <div class="catatan">
        <span class="label">Catatan:</span>
        <br>{{ $retur->catatan }}
    </div>
    @endif
@endsection

@section('signature')
    @include('pdf.components.tanda-tangan', ['columns' => [
        ['label' => 'Pengirim', 'nama' => $retur->fasilitasPengirim?->nama ?? 'Petugas Pengirim', 'jabatan' => 'Petugas Pengirim'],
        ['label' => 'Penerima', 'nama' => $retur->fasilitasPenerima?->nama ?? ($retur->supplier?->nama ?? 'Petugas Penerima'), 'jabatan' => 'Petugas Penerima'],
        ['label' => 'Mengetahui', 'nama' => $retur->disetujuiOleh?->name ?? '____________________', 'jabatan' => 'Admin Dinas'],
    ]])
@endsection
