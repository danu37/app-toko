<?php

namespace App\Http\Controllers;

use App\Http\Requests\Laporan\LaporanFilterRequest;
use App\Http\Resources\CabangResource;
use App\Http\Resources\KategoriResource;
use App\Http\Resources\PelangganResource;
use App\Http\Resources\TransaksiResource;
use App\Http\Resources\UserResource;
use App\Models\Cabang;
use App\Models\Kategori;
use App\Models\Pelanggan;
use App\Models\User;
use App\Services\LaporanService;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanController extends Controller
{
    public function __construct(
        private readonly LaporanService $laporanService,
    ) {}

    public function index(LaporanFilterRequest $request): Response
    {
        Gate::authorize('view-reports');

        $filters = $request->filters();

        $salesReport = $this->laporanService->getSalesReport(
            $filters['start_date'],
            $filters['end_date'],
            $filters['cabang'],
            $filters['pelanggan'],
            $filters['kasir'],
            $filters['search'],
        );

        return Inertia::render('Laporan/Index', [
            'aggregates' => $salesReport['aggregates'],
            'salesChart' => $salesReport['salesChart'],
            'chartSeries' => $salesReport['chartSeries'],
            'dailyRecap' => $salesReport['dailyRecap'],
            'paymentMethodRecap' => $salesReport['paymentMethodRecap'],
            'branchRecap' => $salesReport['branchRecap'],
            'cashierRecap' => $salesReport['cashierRecap'],
            'productSales' => $salesReport['productSales'],
            'branches' => $this->getBranchOptions(),
            'customers' => $this->getCustomerOptions(),
            'cashiers' => $this->getCashierOptions(),
            'storeInfo' => ['name' => config('store.name', 'TokoKita')],
            'filters' => [
                'start_date' => $filters['start_date'],
                'end_date' => $filters['end_date'],
                'cabang' => $filters['cabang'],
                'pelanggan' => $filters['pelanggan'],
                'kasir' => $filters['kasir'],
                'search' => $filters['search'],
            ],
        ]);
    }

    public function transaksi(LaporanFilterRequest $request): Response
    {
        Gate::authorize('view-reports');

        $filters = $request->filters();

        $report = $this->laporanService->getTransactionReport(
            $filters['start_date'],
            $filters['end_date'],
            $filters['cabang'],
            $filters['pelanggan'],
            $filters['status'],
            $filters['kasir'],
            $filters['search'],
        );

        return Inertia::render('Laporan/Transaksi', [
            'aggregates' => $report['aggregates'],
            'transactions' => TransaksiResource::collection($report['transactions']),
            'branches' => $this->getBranchOptions(),
            'customers' => $this->getCustomerOptions(),
            'cashiers' => $this->getCashierOptions(),
            'storeInfo' => ['name' => config('store.name', 'TokoKita')],
            'filters' => [
                'start_date' => $filters['start_date'],
                'end_date' => $filters['end_date'],
                'cabang' => $filters['cabang'],
                'pelanggan' => $filters['pelanggan'],
                'kasir' => $filters['kasir'],
                'status' => $filters['status'],
                'search' => $filters['search'],
            ],
        ]);
    }

    public function produk(LaporanFilterRequest $request): Response
    {
        Gate::authorize('view-reports');

        $filters = $request->filters();

        $report = $this->laporanService->getTopProductsReport(
            $filters['start_date'],
            $filters['end_date'],
            $filters['cabang'],
            $filters['pelanggan'],
            $filters['kasir'],
            $filters['kategori'],
            $filters['search'],
            $filters['sort'],
        );

        return Inertia::render('Laporan/ProdukTerlaris', [
            'aggregates' => $report['aggregates'],
            'chartTopProducts' => $report['chartTopProducts'],
            'products' => $report['products'],
            'branches' => $this->getBranchOptions(),
            'customers' => $this->getCustomerOptions(),
            'cashiers' => $this->getCashierOptions(),
            'categories' => KategoriResource::collection(
                Kategori::query()->orderBy('nama_kategori')->get(['id_kategori', 'nama_kategori'])
            ),
            'storeInfo' => ['name' => config('store.name', 'TokoKita')],
            'filters' => [
                'start_date' => $filters['start_date'],
                'end_date' => $filters['end_date'],
                'cabang' => $filters['cabang'],
                'pelanggan' => $filters['pelanggan'],
                'kasir' => $filters['kasir'],
                'kategori' => $filters['kategori'],
                'search' => $filters['search'],
                'sort' => $filters['sort'],
            ],
        ]);
    }

    public function pelanggan(LaporanFilterRequest $request): Response
    {
        Gate::authorize('view-reports');

        $filters = $request->filters();

        $report = $this->laporanService->getCustomerReport(
            $filters['search'],
            $filters['start_date'],
            $filters['end_date'],
        );

        return Inertia::render('Laporan/Pelanggan', [
            'aggregates' => $report['aggregates'],
            'customers' => $report['customers'],
            'storeInfo' => ['name' => config('store.name', 'TokoKita')],
            'filters' => [
                'search' => $filters['search'],
                'start_date' => $filters['start_date'],
                'end_date' => $filters['end_date'],
            ],
        ]);
    }

    public function export(LaporanFilterRequest $request): StreamedResponse
    {
        Gate::authorize('view-reports');

        $filters = $request->filters();
        $reportType = $filters['report'];
        $timestamp = now()->format('Ymd-His');
        $filename = "laporan-{$reportType}-{$timestamp}.csv";

        return response()->streamDownload(function () use ($reportType, $filters): void {
            $handle = fopen('php://output', 'wb');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM agar karakter Indonesia & angka terbaca rapi di Excel
            fwrite($handle, "\xEF\xBB\xBF");

            match ($reportType) {
                'transactions' => $this->writeTransactionsCsv($handle, $filters),
                'products' => $this->writeProductsCsv($handle, $filters),
                'customers' => $this->writeCustomersCsv($handle, $filters),
                default => $this->writeSalesRecapCsv($handle, $filters),
            };

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * @param  resource  $handle
     * @param  array<string, mixed>  $filters
     */
    private function writeSalesRecapCsv($handle, array $filters): void
    {
        fputcsv($handle, [
            'Tanggal',
            'Jumlah Transaksi',
            'Rata-Rata Transaksi (Rp)',
            'Total Pendapatan (Rp)',
        ]);

        $rows = $this->laporanService->getDailyRecapCollection(
            $filters['start_date'],
            $filters['end_date'],
            $filters['cabang'],
            $filters['pelanggan'],
            $filters['kasir'],
        );

        foreach ($rows as $row) {
            fputcsv($handle, [
                $this->sanitizeCsvCell($row['tanggal']),
                (int) $row['jumlah_transaksi'],
                number_format((float) $row['rata_rata'], 2, '.', ''),
                number_format((float) $row['total_pendapatan'], 2, '.', ''),
            ]);
        }
    }

    /**
     * @param  resource  $handle
     * @param  array<string, mixed>  $filters
     */
    private function writeTransactionsCsv($handle, array $filters): void
    {
        fputcsv($handle, [
            'Kode Transaksi',
            'Tanggal',
            'Pelanggan',
            'Cabang',
            'Kasir / Staff',
            'Metode Pembayaran',
            'Jumlah Item',
            'Status',
            'Total Belanja (Rp)',
        ]);

        $this->laporanService->getTransactionExportQuery(
            $filters['start_date'],
            $filters['end_date'],
            $filters['cabang'],
            $filters['pelanggan'],
            $filters['status'],
            $filters['kasir'],
            $filters['search'],
        )->chunk(200, function ($transactions) use ($handle): void {
            foreach ($transactions as $trx) {
                fputcsv($handle, [
                    $this->sanitizeCsvCell($trx->kode_transaksi ?: "#TRX-{$trx->id_transaksi}"),
                    $this->sanitizeCsvCell($trx->tanggal?->format('Y-m-d') ?? '-'),
                    $this->sanitizeCsvCell($trx->pelanggan?->nama_pelanggan ?? 'Umum'),
                    $this->sanitizeCsvCell($trx->cabang?->nama_cabang ?? '-'),
                    $this->sanitizeCsvCell($trx->nama_kasir ?: ($trx->user?->name ?? 'Transaksi Legacy')),
                    $this->sanitizeCsvCell($trx->metode_pembayaran ? strtoupper((string) $trx->metode_pembayaran) : '-'),
                    (int) ($trx->detail_count ?? 0),
                    $this->sanitizeCsvCell($trx->status ?? 'selesai'),
                    number_format((float) ($trx->total_belanja ?? 0), 2, '.', ''),
                ]);
            }
        });
    }

    /**
     * @param  resource  $handle
     * @param  array<string, mixed>  $filters
     */
    private function writeProductsCsv($handle, array $filters): void
    {
        fputcsv($handle, [
            'Peringkat',
            'Kode Produk',
            'Nama Produk',
            'Kategori',
            'Harga Rata-Rata (Rp)',
            'Jumlah Transaksi',
            'Qty Terjual',
            'Total Pendapatan (Rp)',
            'Sisa Stok',
        ]);

        $report = $this->laporanService->getTopProductsReport(
            $filters['start_date'],
            $filters['end_date'],
            $filters['cabang'],
            $filters['pelanggan'],
            $filters['kasir'],
            $filters['kategori'],
            $filters['search'],
            $filters['sort'],
        );

        foreach ($report['allRows'] as $index => $row) {
            fputcsv($handle, [
                $index + 1,
                $this->sanitizeCsvCell($row['kode_produk'] ?? '-'),
                $this->sanitizeCsvCell($row['nama_produk']),
                $this->sanitizeCsvCell($row['kategori'] ?? 'Umum / Legacy'),
                number_format((float) ($row['harga_rata_rata'] ?? 0), 2, '.', ''),
                (int) $row['jumlah_transaksi'],
                (int) $row['total_terjual'],
                number_format((float) $row['total_pendapatan'], 2, '.', ''),
                $row['stok'] !== null ? (int) $row['stok'] : '-',
            ]);
        }
    }

    /**
     * @param  resource  $handle
     * @param  array<string, mixed>  $filters
     */
    private function writeCustomersCsv($handle, array $filters): void
    {
        fputcsv($handle, [
            'Nama Pelanggan',
            'Kota',
            'No. HP',
            'Jumlah Transaksi',
            'Akumulasi Belanja (Rp)',
        ]);

        $this->laporanService->getCustomerReportQuery(
            $filters['search'],
            $filters['start_date'],
            $filters['end_date'],
        )->chunk(200, function ($customers) use ($handle): void {
            foreach ($customers as $customer) {
                fputcsv($handle, [
                    $this->sanitizeCsvCell($customer->nama_pelanggan),
                    $this->sanitizeCsvCell($customer->kota ?? '-'),
                    $this->sanitizeCsvCell($customer->no_hp ?? '-'),
                    (int) ($customer->total_transaksi ?? 0),
                    number_format((float) ($customer->total_belanja ?? 0), 2, '.', ''),
                ]);
            }
        });
    }

    private function sanitizeCsvCell(mixed $value): string
    {
        $text = (string) ($value ?? '');
        if ($text !== '' && in_array($text[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$text;
        }

        return $text;
    }

    private function getBranchOptions()
    {
        return CabangResource::collection(Cabang::query()->orderBy('nama_cabang')->get());
    }

    private function getCustomerOptions()
    {
        return PelangganResource::collection(
            Pelanggan::query()->orderBy('nama_pelanggan')->get(['id_pelanggan', 'nama_pelanggan', 'kota'])
        );
    }

    private function getCashierOptions()
    {
        return UserResource::collection(
            User::query()->orderBy('name')->get(['id', 'name', 'email', 'role', 'id_cabang', 'is_active'])
        );
    }
}
