<table class="info-table">
    @foreach($items as $item)
    <tr>
        <td class="label">{{ $item['label'] }}</td>
        <td class="separator">:</td>
        <td>{!! $item['value'] ?? '-' !!}</td>
    </tr>
    @endforeach
</table>
