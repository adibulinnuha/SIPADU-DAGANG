<!DOCTYPE html>

<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan ERET Harian</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }

    h1, h2 {
        text-align: center;
        margin: 0;
    }

    p {
        text-align: center;
        margin-bottom: 20px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    th, td {
        border: 1px solid #000;
        padding: 6px;
    }

    th {
        background: #f3f4f6;
    }

    .text-right { text-align: right; }
    .text-center { text-align: center; }

    .section-title {
        margin-top: 25px;
        font-weight: bold;
        font-size: 14px;
    }

    .footer {
        margin-top: 40px;
        display: flex;
        justify-content: space-between;
    }
</style>

</head>
<body onload="window.print()">

<h1>LAPORAN ERET HARIAN</h1>
<h2>SIPADU-DAGANG</h2>
<p>Tanggal: <strong>{{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}</strong></p>

@php
    $manualMarkets = ['REJOMULYO', 'TAMBAK LOROK', 'WARU INDAH', 'REJOMULYO IB', 'DARGO', 'BUBAKAN'];
    $eretMarkets = ['KARIMATA 1', 'KARIMATA 2', 'DARGO', 'LANGGAR', 'WARU INDAH 1', 'WARU INDAH 2'];

    $manual = [];
    $eret = [];

    foreach ($retributions as $r) {
        $marketName = strtoupper($r->market->name ?? '');

        $items = [
            'kios' => 0,
            'los' => 0,
            'dasaran' => 0,
            'sampah' => 0,
            'mck' => 0,
            'listrik' => 0,
        ];

        foreach ($r->items as $item) {
            $jenis = strtolower($item->jenis_retribusi);
            if (isset($items[$jenis])) {
                $items[$jenis] += $item->amount;
            }
        }

        if (in_array($marketName, $manualMarkets)) {
            $manual[$marketName] = $items;
        } elseif (in_array($marketName, $eretMarkets)) {
            $eret[$marketName] = $items;
        }
    }

    $fmt = fn($v) => 'Rp ' . number_format($v, 0, ',', '.');
@endphp

<div class="section-title">RETRIBUSI MANUAL</div>
<table>
    <thead>
        <tr>
            <th>Pasar</th>
            <th>Kios</th>
            <th>Los</th>
            <th>Dasaran</th>
            <th>Sampah</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($manualMarkets as $name)
            @php
                $d = $manual[$name] ?? ['kios'=>0,'los'=>0,'dasaran'=>0,'sampah'=>0,'mck'=>0,'listrik'=>0];
                $total = $d['kios'] + $d['los'] + $d['dasaran'] + $d['sampah'];
            @endphp
            <tr>
                <td>{{ $name }}</td>
                <td class="text-right">{{ $fmt($d['kios']) }}</td>
                <td class="text-right">{{ $fmt($d['los']) }}</td>
                <td class="text-right">{{ $fmt($d['dasaran']) }}</td>
                <td class="text-right">{{ $fmt($d['sampah']) }}</td>
                <td class="text-right"><strong>{{ $fmt($total) }}</strong></td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="section-title">E-RETRIBUSI</div>
<table>
    <thead>
        <tr>
            <th>Pasar</th>
            <th>Kios</th>
            <th>Los</th>
            <th>Dasaran</th>
            <th>Sampah</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($eretMarkets as $name)
            @php
                $d = $eret[$name] ?? ['kios'=>0,'los'=>0,'dasaran'=>0,'sampah'=>0,'mck'=>0,'listrik'=>0];
                $total = $d['kios'] + $d['los'] + $d['dasaran'] + $d['sampah'];
            @endphp
            <tr>
                <td>{{ $name }}</td>
                <td class="text-right">{{ $fmt($d['kios']) }}</td>
                <td class="text-right">{{ $fmt($d['los']) }}</td>
                <td class="text-right">{{ $fmt($d['dasaran']) }}</td>
                <td class="text-right">{{ $fmt($d['sampah']) }}</td>
                <td class="text-right"><strong>{{ $fmt($total) }}</strong></td>
            </tr>
        @endforeach
    </tbody>
</table>

@php
    $totalMck = collect($manual)->sum('mck') + collect($eret)->sum('mck');
    $totalListrik = collect($manual)->sum('listrik') + collect($eret)->sum('listrik');
    $grandTotal = 0;

    foreach ($manual as $d) {
        $grandTotal += $d['kios'] + $d['los'] + $d['dasaran'] + $d['sampah'] + $d['mck'] + $d['listrik'];
    }

    foreach ($eret as $d) {
        $grandTotal += $d['kios'] + $d['los'] + $d['dasaran'] + $d['sampah'] + $d['mck'] + $d['listrik'];
    }
@endphp

<div class="section-title">RINGKASAN</div>
<table>
    <tr>
        <th>Total MCK</th>
        <td class="text-right">{{ $fmt($totalMck) }}</td>
    </tr>
    <tr>
        <th>Total Listrik</th>
        <td class="text-right">{{ $fmt($totalListrik) }}</td>
    </tr>
    <tr>
        <th>GRAND TOTAL KESELURUHAN</th>
        <th class="text-right">{{ $fmt($grandTotal) }}</th>
    </tr>
</table>

<div class="footer">
    <div class="text-center">
        Mengetahui,<br><br><br><br>
        <strong>Koordinator Wilayah</strong>
    </div>

    <div class="text-center">
        Semarang, {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}<br><br><br><br>
        <strong>Petugas ERET</strong>
    </div>
</div>

</body>
</html>
