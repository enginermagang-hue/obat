<table>
    <tr>
        @foreach($columns as $col)
        <td>
            <div>{{ $col['label'] }}</div>
            <div class="ttd"></div>
            <div class="nama">{{ $col['nama'] }}</div>
            @if($col['jabatan'] ?? false)
            <div class="jabatan">{{ $col['jabatan'] }}</div>
            @endif
        </td>
        @endforeach
    </tr>
</table>
