@extends('pdf.layouts.base')

@section('title', 'Faktur Permintaan - ' . $permintaan->nomor_permintaan)

@section('subtitle', 'SURAT PERMINTAAN OBAT')
@section('subtitle_desc', 'No: ' . $permintaan->nomor_permintaan)

@section('content')
    @include('pdf.components.info-table', ['items' => [
        ['label' => 'Nomor Permintaan', 'value' => $permintaan->nomor_permintaan],
        ['label' => 'Tanggal Permintaan', 'value' => $permintaan->tanggal_permintaan?->format('d F Y') ?? '-'],
        ['label' => 'Tipe', 'value' => match($permintaan->tipe_permintaan) {
            'pustu_ke_puskesmas' => 'Pustu → Puskesmas',
            'puskesmas_ke_dinas' => 'Puskesmas → Dinas',
            default => $permintaan->tipe_permintaan,
        }],
        ['label' => 'Pengirim', 'value' => ($permintaan->fasilitasPengirim?->nama ?? '-') . ($permintaan->fasilitasPengirim?->alamat ? '<br>' . $permintaan->fasilitasPengirim->alamat : '')],
        ['label' => 'Tujuan', 'value' => ($permintaan->fasilitasTujuan?->nama ?? 'Dinas Kesehatan') . ($permintaan->fasilitasTujuan?->alamat ? '<br>' . $permintaan->fasilitasTujuan->alamat : '')],
        ['label' => 'Status', 'value' => '<span class="status-badge status-' . $permintaan->status . '">' . match($permintaan->status) {
            'draft' => 'Draft',
            'menunggu_persetujuan' => 'Menunggu Persetujuan',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
            'sedang_didistribusi' => 'Sedang Didistribusi',
            'diterima' => 'Diterima',
            'dibatalkan' => 'Dibatalkan',
            default => $permintaan->status,
        } . '</span>'],
    ]])

    <table class="items">
        <thead>
            <tr>
                <th style="width: 24px; text-align: center;">No</th>
                <th style="width: 60px;">Kode Obat</th>
                <th>Nama Obat</th>
                <th style="width: 55px;">Kategori</th>
                <th style="width: 40px;">Satuan</th>
                <th style="width: 50px; text-align: center;">Jumlah Diminta</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @forelse($permintaan->details as $detail)
            <tr>
                <td class="text-center">{{ $no++ }}</td>
                <td>{{ $detail->obat?->kode_obat ?? '-' }}</td>
                <td>{{ $detail->obat?->nama_obat ?? '-' }}</td>
                <td>{{ $detail->obat?->kategori ?? '-' }}</td>
                <td>{{ $detail->obat?->satuan ?? '-' }}</td>
                <td class="text-center">{{ number_format($detail->jumlah_diminta, 0, ',', '.') }}</td>
                <td>{{ $detail->catatan ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center" style="padding: 12px; color: #9ca3af;">
                    Tidak ada item dalam permintaan ini.
                </td>
            </tr>
            @endforelse
        </tbody>
        @if($permintaan->details->count() > 0)
        <tfoot>
            <tr>
                <td colspan="4" class="text-right">Total Item</td>
                <td>{{ $permintaan->details->count() }}</td>
                <td class="text-center">{{ number_format($permintaan->details->sum('jumlah_diminta'), 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>

    @if(filled($permintaan->catatan))
    <div class="catatan">
        <span class="label">Catatan:</span>
        <br>{{ $permintaan->catatan }}
    </div>
    @endif

    @if(filled($permintaan->alasan_penolakan))
    <div class="catatan">
        <span class="label">Alasan Penolakan:</span>
        <br>{{ $permintaan->alasan_penolakan }}
    </div>
    @endif
@endsection

@section('signature')
    @include('pdf.components.tanda-tangan', ['columns' => [
        [
            'label' => 'Yang Mengajukan',
            'nama' => $permintaan->disetujuiOleh?->name ?? '____________________',
            'jabatan' => 'Petugas ' . ($permintaan->fasilitasPengirim?->nama ?? 'Faskes'),
        ],
        [
            'label' => 'Mengetahui \n ',
            'nama' => 'Kepala ' . ($permintaan->fasilitasPengirim?->nama ?? 'Faskes Pengirim'),
            'jabatan' => '',
        ],
        [
            'label' => 'Mengetahui \n Kepala Dinas Kesehatan',
            'nama' => '__________________________',
            'jabatan' => '',
        ],
    ]])
@endsection
