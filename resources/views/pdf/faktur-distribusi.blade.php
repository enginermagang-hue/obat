@extends('pdf.layouts.base')

@section('title', 'Faktur Distribusi - ' . $distribusi->nomor_surat_jalan)

@section('subtitle', 'SURAT JALAN DISTRIBUSI OBAT')
@section('subtitle_desc', 'No: ' . $distribusi->nomor_surat_jalan)

@section('content')
    @include('pdf.components.info-table', ['items' => [
        ['label' => 'Nomor Surat Jalan', 'value' => $distribusi->nomor_surat_jalan],
        ['label' => 'Tanggal Kirim', 'value' => $distribusi->tanggal_kirim?->format('d F Y') ?? '-'],
        ['label' => 'Pengirim', 'value' => ($distribusi->fasilitasPengirim?->nama ?? 'Gudang Dinas Kesehatan') . ($distribusi->fasilitasPengirim?->alamat ? '<br>' . $distribusi->fasilitasPengirim->alamat : '')],
        ['label' => 'Penerima', 'value' => ($distribusi->fasilitasPenerima?->nama ?? '-') . ($distribusi->fasilitasPenerima?->alamat ? '<br>' . $distribusi->fasilitasPenerima->alamat : '')],
        ['label' => 'Tipe Distribusi', 'value' => match($distribusi->tipe_distribusi) { 'dinas_ke_puskesmas' => 'Dinas → Puskesmas', 'puskesmas_ke_pustu' => 'Puskesmas → Pustu', default => $distribusi->tipe_distribusi }],
        ['label' => 'Status', 'value' => '<span class="status-badge status-' . $distribusi->status . '">' . str_replace('_', ' ', ucfirst($distribusi->status)) . '</span>'],
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
            @forelse($distribusi->details as $detail)
            <tr>
                <td class="text-center">{{ $no++ }}</td>
                <td>{{ $detail->obat?->kode_obat ?? '-' }}</td>
                <td>{{ $detail->obat?->nama_obat ?? '-' }}</td>
                <td>{{ $detail->batch?->batch_number ?? '-' }}</td>
                <td>{{ $detail->batch?->tanggal_expired?->format('m/Y') ?? '-' }}</td>
                <td class="text-center">{{ number_format($detail->jumlah, 0, ',', '.') }}</td>
                <td>{{ $detail->obat?->satuan ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center" style="padding: 12px; color: #9ca3af;">
                    Tidak ada item dalam distribusi ini.
                </td>
            </tr>
            @endforelse
        </tbody>
        @if($distribusi->details->count() > 0)
        <tfoot>
            <tr>
                <td colspan="5" class="text-right">Total Item</td>
                <td class="text-center">{{ number_format($distribusi->details->sum('jumlah'), 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>

    @if($distribusi->catatan)
    <div class="catatan">
        <span class="label">Catatan:</span>
        <br>{{ $distribusi->catatan }}
    </div>
    @endif
@endsection

@section('signature')
    @include('pdf.components.tanda-tangan', ['columns' => [
        ['label' => 'Pengirim', 'nama' => $distribusi->pengirim?->name ?? 'Petugas Gudang', 'jabatan' => 'Petugas Gudang'],
        ['label' => 'Penerima', 'nama' => $distribusi->penerima?->name ?? '____________________', 'jabatan' => 'Petugas Penerima'],
        ['label' => 'Mengetahui', 'nama' => 'Kepala ' . ($distribusi->fasilitasPengirim?->nama ?? 'Dinas Kesehatan'), 'jabatan' => ''],
    ]])
@endsection
