<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Laporan Admin' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 14mm 12mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #171717;
            line-height: 1.45;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #171717;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .store-name {
            font-size: 16px;
            font-weight: bold;
            color: #171717;
            margin: 0;
        }
        .report-title {
            font-size: 13px;
            font-weight: bold;
            color: #262626;
            margin: 3px 0 0 0;
        }
        .meta-text {
            font-size: 9px;
            color: #525252;
            margin: 2px 0 0 0;
        }
        .section-title {
            font-size: 10.5px;
            font-weight: bold;
            color: #171717;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin: 14px 0 6px 0;
        }
        .kpi-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .kpi-table td {
            border: 1px solid #d4d4d4;
            background-color: #fafafa;
            padding: 8px 10px;
            vertical-align: top;
        }
        .kpi-label {
            font-size: 8.5px;
            text-transform: uppercase;
            color: #737373;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .kpi-value {
            font-size: 13px;
            font-weight: bold;
            color: #171717;
        }
        .kpi-sub {
            font-size: 8px;
            color: #737373;
            margin-top: 2px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        table.data-table th {
            background-color: #f5f5f5;
            color: #404040;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            border: 1px solid #d4d4d4;
            padding: 6px 7px;
            text-align: left;
        }
        table.data-table td {
            border: 1px solid #e5e5e5;
            padding: 5px 7px;
            font-size: 9px;
            color: #262626;
            vertical-align: top;
        }
        table.data-table tr:nth-child(even) td {
            background-color: #fcfcfc;
        }
        .text-right {
            text-align: right !important;
        }
        .text-center {
            text-align: center !important;
        }
        .font-bold {
            font-weight: bold;
        }
        .empty-box {
            border: 1px dashed #d4d4d4;
            padding: 16px;
            text-align: center;
            color: #737373;
            font-size: 9.5px;
        }
        .footer-note {
            margin-top: 14px;
            font-size: 8px;
            color: #737373;
            text-align: center;
            border-top: 1px solid #e5e5e5;
            padding-top: 6px;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 65%; vertical-align: top;">
                <p class="store-name">{{ $storeInfo['name'] ?? 'TokoKita' }}</p>
                <p class="report-title">{{ $title }}</p>
                @if(!empty($filterSummary))
                    <p class="meta-text">{{ implode(' | ', $filterSummary) }}</p>
                @endif
            </td>
            <td style="width: 35%; vertical-align: top; text-align: right;">
                <p class="meta-text"><strong>Periode:</strong> {{ $periodLabel }}</p>
                <p class="meta-text"><strong>Dicetak pada:</strong> {{ $generatedAt }}</p>
                <p class="meta-text"><strong>Oleh:</strong> {{ $actor->name }} ({{ strtoupper($actor->role) }})</p>
            </td>
        </tr>
    </table>

    @if($reportType === 'sales')
        <table class="kpi-table">
            <tr>
                <td style="width: 25%;">
                    <div class="kpi-label">Total Transaksi</div>
                    <div class="kpi-value">{{ number_format($aggregates['total_transaksi'] ?? 0, 0, ',', '.') }}</div>
                    <div class="kpi-sub">Batal: {{ $aggregates['total_dibatalkan'] ?? 0 }}</div>
                </td>
                <td style="width: 25%;">
                    <div class="kpi-label">Total Pendapatan</div>
                    <div class="kpi-value">Rp {{ number_format($aggregates['total_pendapatan'] ?? 0, 0, ',', '.') }}</div>
                    <div class="kpi-sub">Transaksi selesai</div>
                </td>
                <td style="width: 25%;">
                    <div class="kpi-label">Rata-Rata Transaksi</div>
                    <div class="kpi-value">Rp {{ number_format($aggregates['rata_rata'] ?? 0, 0, ',', '.') }}</div>
                    <div class="kpi-sub">Per transaksi</div>
                </td>
                <td style="width: 25%;">
                    <div class="kpi-label">Total Item Terjual</div>
                    <div class="kpi-value">{{ number_format($aggregates['total_item_terjual'] ?? 0, 0, ',', '.') }}</div>
                    <div class="kpi-sub">Unit terjual</div>
                </td>
            </tr>
        </table>

        <div class="section-title">Rekapitulasi Pendapatan Harian</div>
        @if($dailyRecap->isEmpty())
            <div class="empty-box">Tidak ada data penjualan pada periode ini.</div>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th class="text-right">Jumlah Transaksi</th>
                        <th class="text-right">Rata-Rata Transaksi</th>
                        <th class="text-right">Total Pendapatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dailyRecap as $row)
                        <tr>
                            <td class="font-bold">{{ \Carbon\Carbon::parse($row['tanggal'])->format('d/m/Y') }}</td>
                            <td class="text-right">{{ number_format($row['jumlah_transaksi'], 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($row['rata_rata'], 0, ',', '.') }}</td>
                            <td class="text-right font-bold">Rp {{ number_format($row['total_pendapatan'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if(!empty($cashierRecap))
            <div class="section-title">Rekapitulasi Per Kasir</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nama Kasir / Staff</th>
                        <th class="text-right">Jumlah Transaksi</th>
                        <th class="text-right">Total Pendapatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cashierRecap as $row)
                        <tr>
                            <td class="font-bold">{{ $row['nama_kasir'] ?? '-' }}</td>
                            <td class="text-right">{{ number_format($row['jumlah_transaksi'], 0, ',', '.') }}</td>
                            <td class="text-right font-bold">Rp {{ number_format($row['total_pendapatan'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

    @elseif($reportType === 'products')
        <table class="kpi-table">
            <tr>
                <td style="width: 33.3%;">
                    <div class="kpi-label">Total SKU Terjual</div>
                    <div class="kpi-value">{{ number_format($aggregates['total_produk_terjual'] ?? 0, 0, ',', '.') }}</div>
                </td>
                <td style="width: 33.3%;">
                    <div class="kpi-label">Total Unit Terjual</div>
                    <div class="kpi-value">{{ number_format($aggregates['total_unit_terjual'] ?? 0, 0, ',', '.') }}</div>
                </td>
                <td style="width: 33.4%;">
                    <div class="kpi-label">Total Omzet Produk</div>
                    <div class="kpi-value">Rp {{ number_format($aggregates['total_pendapatan'] ?? 0, 0, ',', '.') }}</div>
                </td>
            </tr>
        </table>

        <div class="section-title">Daftar Produk Terlaris</div>
        @if($rows->isEmpty())
            <div class="empty-box">Tidak ada data produk terjual pada periode ini.</div>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 5%;" class="text-center">No</th>
                        <th style="width: 14%;">Kode</th>
                        <th style="width: 28%;">Nama Produk</th>
                        <th style="width: 15%;">Kategori</th>
                        <th style="width: 11%;" class="text-right">Qty</th>
                        <th style="width: 16%;" class="text-right">Total Omzet</th>
                        <th style="width: 11%;" class="text-right">Sisa Stok</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $idx => $row)
                        <tr>
                            <td class="text-center">{{ $idx + 1 }}</td>
                            <td>{{ $row['kode_produk'] ?? '-' }}</td>
                            <td class="font-bold">{{ $row['nama_produk'] }}</td>
                            <td>{{ $row['kategori'] ?? 'Umum' }}</td>
                            <td class="text-right font-bold">{{ number_format($row['total_terjual'], 0, ',', '.') }}</td>
                            <td class="text-right font-bold">Rp {{ number_format($row['total_pendapatan'], 0, ',', '.') }}</td>
                            <td class="text-right">{{ $row['stok'] !== null ? number_format($row['stok'], 0, ',', '.') : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

    @elseif($reportType === 'customers')
        <table class="kpi-table">
            <tr>
                <td style="width: 33.3%;">
                    <div class="kpi-label">Total Pelanggan</div>
                    <div class="kpi-value">{{ number_format($aggregates['total_pelanggan'] ?? 0, 0, ',', '.') }}</div>
                </td>
                <td style="width: 33.3%;">
                    <div class="kpi-label">Pelanggan Aktif</div>
                    <div class="kpi-value">{{ number_format($aggregates['pelanggan_aktif'] ?? 0, 0, ',', '.') }}</div>
                </td>
                <td style="width: 33.4%;">
                    <div class="kpi-label">Total Belanja</div>
                    <div class="kpi-value">Rp {{ number_format($aggregates['total_pendapatan'] ?? 0, 0, ',', '.') }}</div>
                </td>
            </tr>
        </table>

        <div class="section-title">Laporan Aktivitas Pelanggan</div>
        @if($rows->isEmpty())
            <div class="empty-box">Tidak ada data pelanggan yang sesuai dengan filter.</div>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 6%;" class="text-center">No</th>
                        <th style="width: 30%;">Nama Pelanggan</th>
                        <th style="width: 20%;">Kota</th>
                        <th style="width: 16%;">No. HP</th>
                        <th style="width: 11%;" class="text-right">Transaksi</th>
                        <th style="width: 17%;" class="text-right">Akumulasi Belanja</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $idx => $customer)
                        <tr>
                            <td class="text-center">{{ $idx + 1 }}</td>
                            <td class="font-bold">{{ $customer->nama_pelanggan }}</td>
                            <td>{{ $customer->kota ?? '-' }}</td>
                            <td>{{ $customer->no_hp ?? '-' }}</td>
                            <td class="text-right">{{ number_format($customer->total_transaksi ?? 0, 0, ',', '.') }}</td>
                            <td class="text-right font-bold">Rp {{ number_format($customer->total_belanja ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

    <div class="footer-note">
        Dokumen ini digenerate otomatis oleh sistem {{ $storeInfo['name'] ?? 'TokoKita' }} pada {{ $generatedAt }}.
    </div>
</body>
</html>
