<div class="kop">
    @if($kop['logo_path'] ?? false)
        <img src="{{ $kop['logo_path'] }}" alt="Logo" style="height: 50px; margin-bottom: 6px;">
    @endif
    @if($kop['baris_1'] ?? false)
        <p class="badge-kab">{{ $kop['baris_1'] }}</p>
    @endif
    @if($kop['baris_2'] ?? false)
        <h1>{{ $kop['baris_2'] }}</h1>
    @endif
    @if($kop['alamat'] ?? false)
        <p class="alamat">{{ $kop['alamat'] }}</p>
    @endif
</div>
