<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Dokumen')</title>
    <style>
        @page {
            margin: {{ $layout['margin_top'] }}mm {{ $layout['margin_right'] }}mm {{ $layout['margin_bottom'] }}mm {{ $layout['margin_left'] }}mm;
        }
        body {
            font-family: '{{ $layout['font_family'] }}', sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            color: #1f2937;
        }
        .kop {
            text-align: center;
            margin-bottom: 18px;
            padding-bottom: 10px;
            border-bottom: 2px solid #374151;
        }
        .kop h1 {
            font-size: 13pt;
            line-height: 14pt;
            margin: 0 0 2px;
            letter-spacing: 1px;
        }
        .kop .alamat {
            @php $alamatSize = 9; @endphp
            font-size: 9pt;
            line-height: 10pt;
            margin: 0;
            color: #4b5563;
        }
        .kop .badge-kab {
            font-size: 10pt;
            margin: 0 0 2px;
        }
        @php
            $bodySize = 11;
            $infoSize = 11;
            $itemsSize = 11;
            $itemsHeaderSize = 11;
            $subtitleSize = 13;
            $subtitleDescSize = 11;
        @endphp

        @hasSection('subtitle')
        .subtitle {
            text-align: center;
            margin-bottom: 16px;
            line-height: 12pt;
        }
        .subtitle h2 {
            font-size: {{ $subtitleSize }}pt;
            margin: 0;
            text-transform: uppercase;
            text-decoration: underline;
        }
        .subtitle p {
            font-size: {{ $subtitleDescSize }}pt;
            margin: 3px 0 0;
            color: #6b7280;
        }
        @endif
        .info-table {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 2px 4px;
            font-size: {{ $infoSize }}pt;
            vertical-align: top;
        }
        .info-table .label {
            /*width: 120px;*/
            font-weight: bold;
        }
        .info-table .separator {
            width: 12px;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            font-size: {{ $itemsSize }}pt;
        }
        table.items thead {
            display: table-header-group;
        }
        table.items th {
            background-color: #374151;
            color: #ffffff;
            padding: 5px 4px;
            text-align: left;
            font-weight: bold;
            font-size: {{ $itemsHeaderSize }}pt;
        }
        table.items td {
            padding: 4px;
            border-bottom: 1px solid #d1d5db;
            vertical-align: middle;
            font-size: {{ $itemsSize }}pt;
        }
        table.items tbody tr {
            orphans: 2;
            widows: 2;
        }
        table.items tr:nth-child(even) td {
            background-color: #f9fafb;
        }
        table.items tfoot td {
            font-weight: bold;
            border-top: 2px solid #374151;
            border-bottom: none;
            background-color: #f3f4f6;
        }
        .tanda-tangan {
            page-break-inside: avoid;
            margin-top: 18px;
        }
        .tanda-tangan table {
            width: 100%;
            border-collapse: collapse;
        }
        @php
            $signatureSize = 11;
            $namaSize = 12;
            $jabatanSize = 10;
        @endphp
        .tanda-tangan td {
            text-align: center;
            font-size: {{ $signatureSize }}pt;
            vertical-align: top;
            padding: 0 10px;
        }
        .tanda-tangan .ttd {
            margin-top: 40px;
        }
        .tanda-tangan .nama {
            font-weight: bold;
            margin-top: 4px;
            font-size: {{ $namaSize }}pt;
        }
        .tanda-tangan .jabatan {
            font-size: {{ $jabatanSize }}pt;
            color: #6b7280;
        }
        .catatan {
            margin-bottom: 10px;
            font-size: {{ $infoSize }}pt;
        }
        .catatan .label {
            font-weight: bold;
        }
        .status-badge {
            display: inline-block;
            padding: 1px 8px;
            font-size: {{ $itemsSize }}pt;
            font-weight: bold;
            border-radius: 2px;
        }
        .status-dalam_pengiriman { background-color: #fef3c7; color: #92400e; }
        .status-diterima { background-color: #d1fae5; color: #065f46; }
        .status-ditolak { background-color: #fee2e2; color: #991b1b; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        @stack('styles')
    </style>
</head>
<body>
    @include('pdf.components.kop-surat', ['kop' => $kop])

    @hasSection('subtitle')
    <div class="subtitle">
        <h2>@yield('subtitle')</h2>
        @hasSection('subtitle_desc')
        <p>@yield('subtitle_desc')</p>
        @endif
    </div>
    @endif

    @yield('content')

    @hasSection('signature')
    <div class="tanda-tangan">
        @yield('signature')
    </div>
    @endif
</body>
</html>
