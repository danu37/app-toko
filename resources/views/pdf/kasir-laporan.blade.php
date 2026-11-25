<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Laporan Operasional Kasir' }}</title>
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
            width: 25%;
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
        .mono {
            font-family: DejaVu Sans Mono, monospace;
            font-size: 8.5px;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 7.5px;
            font-weight: bold;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .badge-selesai {
            background-color: #d1fae5;
            color: #065f46;
        }
        .badge-batal {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .signature-table {
            width: 100%;
            margin-top: 24px;
            page-break-inside: avoid;
        }
        .signature-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 18px;
        }
        .signature-space {
            height: 54px;
        }
        .signature-line {
            border-top: 1px solid #525252;
            padding-top: 4px;
            font-size: 9px;
            font-weight: bold;
            color: #171717;
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

    <table class="kpi-table">
        <tr>
            <td>
                <div class="kpi-label">Total Transaksi</div>
                <div class="kpi-value">{{ number_format($aggregates['total_transaksi'], 0, ',', '.') }}</div>
                <div class="kpi-sub">Selesai: {{ $aggregates['total_selesai'] }} | Batal: {{ $aggregates['total_dibatalkan'] }}</div>
            </td>
            <td>
                <div class="kpi-label">Total Penjualan (Omzet)</div>
                <div class="kpi-value">Rp {{ number_format($aggregates['total_omzet'], 0, ',', '.') }}</div>
                <div class="kpi-sub">Tidak termasuk transaksi batal</div>
            </td>
            <td>
                <div class="kpi-label">Rata-Rata Transaksi</div>
                <div class="kpi-value">Rp {{ number_format($aggregates['rata_rata'], 0, ',', '.') }}</div>
                <div class="kpi-sub">Per transaksi selesai</div>
            </td>
            <td>
                <div class="kpi-label">Total Item Terjual</div>
                <div class="kpi-value">{{ number_format($aggregates['total_item_terjual'], 0, ',', '.') }}</div>
                <div class="kpi-sub">Akumulasi kuantitas barang</div>
            </td>
        </tr>
    </table>

    @if(!empty($paymentMethodRecap))
        <div class="section-title">Rekapitulasi Metode Pembayaran</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Metode Pembayaran</th>
                    <th class="text-right">Jumlah Transaksi</th>
                    <th class="text-right">Total Nominal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($paymentMethodRecap as $row)
                    <tr>
                        <td class="font-bold">{{ $row['metode'] === 'tidak_tercatat' ? 'Tidak Tercatat' : strtoupper($row['metode']) }}</td>
                        <td class="text-right">{{ number_format($row['jumlah_transaksi'], 0, ',', '.') }}</td>
                        <td class="text-right font-bold">Rp {{ number_format($row['total_nominal'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="section-title">Rincian Transaksi ({{ $transactions->count() }})</div>
    @if($transactions->isEmpty())
        <div class="empty-box">
            Tidak ada transaksi yang sesuai dengan filter pada periode ini.
        </div>
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 4%;" class="text-center">No</th>
                    <th style="width: 17%;">Kode Transaksi</th>
                    <th style="width: 11%;">Tanggal</th>
                    <th style="width: 22%;">Pelanggan</th>
                    <th style="width: 15%;">Kasir</th>
                    <th style="width: 9%;">Metode</th>
                    <th style="width: 9%;">Status</th>
                    <th style="width: 5%;" class="text-right">Item</th>
                    <th style="width: 13%;" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $idx => $trx)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td class="mono font-bold">{{ $trx->kode_transaksi ?: ('#TRX-' . $trx->id_transaksi) }}</td>
                        <td>{{ $trx->tanggal ? \Carbon\Carbon::parse($trx->tanggal)->format('d/m/Y') : '-' }}</td>
                        <td>{{ $trx->pelanggan?->nama_pelanggan ?? 'Pelanggan Umum' }}</td>
                        <td>{{ $trx->user?->name ?? $trx->nama_kasir ?? '-' }}</td>
                        <td>{{ $trx->metode_pembayaran ? strtoupper($trx->metode_pembayaran) : '-' }}</td>
                        <td>
                            @if($trx->status === \App\Models\Transaksi::STATUS_DIBATALKAN)
                                <span class="badge badge-batal">Batal</span>
                            @else
                                <span class="badge badge-selesai">Selesai</span>
                            @endif
                        </td>
                        <td class="text-right">{{ $trx->detail_count ?? 0 }}</td>
                        <td class="text-right font-bold">Rp {{ number_format((float) $trx->total_belanja, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table class="signature-table">
        <tr>
            <td>
                <div style="font-size: 9px; color: #525252;">Dibuat Oleh,</div>
                <div class="signature-space"></div>
                <div class="signature-line">
                    {{ $actor->name }} ({{ $actor->isAdmin() ? 'Administrator' : 'Kasir' }})
                </div>
            </td>
            <td>
                <div style="font-size: 9px; color: #525252;">Mengetahui,</div>
                <div class="signature-space"></div>
                <div class="signature-line">
                    Administrator / Supervisor
                </div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Dokumen ini digenerate otomatis oleh sistem {{ $storeInfo['name'] ?? 'TokoKita' }} pada {{ $generatedAt }}.
    </div>
</body>
</html>
