<?php

namespace App\Services;

use App\Models\Cabang;
use App\Models\Kategori;
use App\Models\Pelanggan;
use App\Models\Produk;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator as ConcretePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LaporanService
{
    public function getDashboardSummary(User $user): array
    {
        $today = now()->toDateString();

        $todayRow = Transaksi::query()
            ->where('tanggal', $today)
            ->selectRaw(
                'COUNT(*) as total_hari_ini, '.
                'COALESCE(SUM(CASE WHEN user_id = ? THEN 1 ELSE 0 END), 0) as kasir_hari_ini, '.
                'COUNT(DISTINCT CASE WHEN (status != ? OR status IS NULL) AND id_pelanggan IS NOT NULL THEN id_pelanggan END) as pelanggan_hari_ini, '.
                'COALESCE(SUM(CASE WHEN (status != ? OR status IS NULL) THEN total_belanja ELSE 0 END), 0) as omzet_hari_ini',
                [$user->id, Transaksi::STATUS_DIBATALKAN, Transaksi::STATUS_DIBATALKAN]
            )
            ->first();

        $transaksiHariIni = (int) ($todayRow->total_hari_ini ?? 0);
        $transaksiKasirHariIni = (int) ($todayRow->kasir_hari_ini ?? 0);
        $pelangganDilayaniHariIni = (int) ($todayRow->pelanggan_hari_ini ?? 0);

        $itemTerjualHariIni = (int) DB::table('tbl_detail_transaksi as d')
            ->join('tbl_transaksi as t', 'd.id_transaksi', '=', 't.id_transaksi')
            ->where('t.tanggal', $today)
            ->where(function (QueryBuilder $q) {
                $q->where('t.status', '!=', Transaksi::STATUS_DIBATALKAN)
                    ->orWhereNull('t.status');
            })
            ->sum('d.jumlah');

        $omzetHariIni = (float) ($todayRow->omzet_hari_ini ?? 0);

        $recentTransactions = Transaksi::query()
            ->with(['pelanggan:id_pelanggan,nama_pelanggan,kota', 'cabang:id_cabang,nama_cabang', 'user:id,name'])
            ->withCount('detail')
            ->orderByDesc('tanggal')
            ->orderByDesc('id_transaksi')
            ->limit(5)
            ->get();

        $produkCounts = Produk::query()
            ->selectRaw('COUNT(*) as total_produk, COALESCE(SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END), 0) as produk_aktif')
            ->first();

        $totalProduk = (int) ($produkCounts->total_produk ?? 0);
        $produkAktif = (int) ($produkCounts->produk_aktif ?? 0);

        $summary = [
            'total_produk' => $totalProduk,
            'produk_aktif' => $produkAktif,
            'total_pelanggan' => Pelanggan::query()->count(),
            'transaksi_hari_ini' => $transaksiHariIni,
            'transaksi_kasir_hari_ini' => $transaksiKasirHariIni,
            'pelanggan_dilayani_hari_ini' => $pelangganDilayaniHariIni,
            'item_terjual_hari_ini' => $itemTerjualHariIni,
        ];

        if ($user->can('view-daily-sales')) {
            $summary['omzet_hari_ini'] = $omzetHariIni;
        }

        if (! $user->isAdmin()) {
            return [
                'summary' => $summary,
                'recentTransactions' => $recentTransactions,
            ];
        }

        $nonCancelledAggregate = $this->applyNonCancelledFilter(Transaksi::query())
            ->selectRaw('COUNT(*) as total_transaksi_selesai, COALESCE(SUM(total_belanja), 0) as total_omzet, COALESCE(AVG(total_belanja), 0) as rata_rata')
            ->first();

        $summary['total_kategori'] = Kategori::query()->active()->count();
        $summary['total_cabang'] = Cabang::query()->count();
        $summary['total_pengguna'] = User::query()->count();
        $summary['total_transaksi'] = Transaksi::query()->count();
        $summary['total_transaksi_selesai'] = (int) ($nonCancelledAggregate->total_transaksi_selesai ?? 0);
        $summary['total_omzet'] = (float) ($nonCancelledAggregate->total_omzet ?? 0);
        $summary['rata_rata_transaksi'] = round((float) ($nonCancelledAggregate->rata_rata ?? 0), 2);

        return [
            'summary' => $summary,
            'recentTransactions' => $recentTransactions,
            'salesChart' => $this->getDashboardSalesChart(),
            'topProducts' => $this->getTopSellingProducts(5),
            'stockSummary' => $this->getStockSummary(),
        ];
    }

    public function getTransactionReport(
        ?string $startDate,
        ?string $endDate,
        ?int $cabangId,
        ?int $pelangganId = null,
        ?string $status = null,
        ?int $kasirId = null,
        ?string $search = null,
        int $perPage = 15,
    ): array {
        $baseQuery = $this->buildTransactionBaseQuery(
            $startDate,
            $endDate,
            $cabangId,
            $pelangganId,
            $status,
            $kasirId,
            $search,
        );

        $totalTransaksi = (clone $baseQuery)->count();

        $nonCancelledQuery = $this->applyNonCancelledFilter(clone $baseQuery);

        $nonCancelledAgg = (clone $nonCancelledQuery)
            ->selectRaw('COUNT(*) as total_selesai, COALESCE(SUM(total_belanja), 0) as total_omzet, COALESCE(AVG(total_belanja), 0) as rata_rata')
            ->first();

        $matchingIdsSub = (clone $nonCancelledQuery)
            ->select('tbl_transaksi.id_transaksi')
            ->toBase();

        $totalItemTerjual = (int) DB::table('tbl_detail_transaksi')
            ->whereIn('id_transaksi', $matchingIdsSub)
            ->sum('jumlah');

        /** @var LengthAwarePaginator $transactions */
        $transactions = (clone $baseQuery)
            ->with(['pelanggan:id_pelanggan,nama_pelanggan,kota', 'cabang:id_cabang,nama_cabang', 'user:id,name,role'])
            ->withCount('detail')
            ->orderByDesc('tanggal')
            ->orderByDesc('id_transaksi')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'aggregates' => [
                'total_transaksi' => $totalTransaksi,
                'total_selesai' => (int) ($nonCancelledAgg->total_selesai ?? 0),
                'total_dibatalkan' => max(0, $totalTransaksi - (int) ($nonCancelledAgg->total_selesai ?? 0)),
                'total_omzet' => (float) ($nonCancelledAgg->total_omzet ?? 0),
                'rata_rata' => round((float) ($nonCancelledAgg->rata_rata ?? 0), 2),
                'total_item_terjual' => $totalItemTerjual,
            ],
            'transactions' => $transactions,
        ];
    }

    public function getTransactionExportQuery(
        ?string $startDate,
        ?string $endDate,
        ?int $cabangId,
        ?int $pelangganId = null,
        ?string $status = null,
        ?int $kasirId = null,
        ?string $search = null,
    ): Builder {
        return $this->buildTransactionBaseQuery(
            $startDate,
            $endDate,
            $cabangId,
            $pelangganId,
            $status,
            $kasirId,
            $search,
        )
            ->with(['pelanggan:id_pelanggan,nama_pelanggan,kota', 'cabang:id_cabang,nama_cabang', 'user:id,name,role'])
            ->withCount('detail')
            ->orderByDesc('tanggal')
            ->orderByDesc('id_transaksi');
    }

    public function getSalesReport(
        ?string $startDate,
        ?string $endDate,
        ?int $cabangId,
        ?int $pelangganId = null,
        ?int $kasirId = null,
        ?string $productSearch = null,
    ): array {
        $filteredTransactions = $this->applyNonCancelledFilter(
            $this->buildTransactionBaseQuery(
                $startDate,
                $endDate,
                $cabangId,
                $pelangganId,
                null,
                $kasirId,
            )
        );

        $aggregatesRow = (clone $filteredTransactions)
            ->selectRaw('COUNT(*) as total_transaksi, COALESCE(SUM(total_belanja), 0) as total_pendapatan, COALESCE(AVG(total_belanja), 0) as rata_rata')
            ->first();

        $totalDibatalkan = $this->buildTransactionBaseQuery(
            $startDate,
            $endDate,
            $cabangId,
            $pelangganId,
            Transaksi::STATUS_DIBATALKAN,
            $kasirId,
        )->count();

        $matchingIdsSub = (clone $filteredTransactions)
            ->select('tbl_transaksi.id_transaksi')
            ->toBase();

        $totalItemTerjual = (int) DB::table('tbl_detail_transaksi')
            ->whereIn('id_transaksi', $matchingIdsSub)
            ->sum('jumlah');

        $salesChart = $this->buildSalesChartSeries($filteredTransactions, 30, 12);

        $dailyRecap = $this->buildDailyRecapQuery($filteredTransactions)
            ->paginate(10, ['*'], 'daily_page')
            ->withQueryString()
            ->through(fn ($row) => $this->mapDailyRecapRow($row));

        $paymentMethodRecap = (clone $filteredTransactions)
            ->select('metode_pembayaran')
            ->selectRaw('COUNT(*) as jumlah_transaksi')
            ->selectRaw('COALESCE(SUM(total_belanja), 0) as total_pendapatan')
            ->groupBy('metode_pembayaran')
            ->get()
            ->groupBy(fn ($row) => filled($row->metode_pembayaran) ? (string) $row->metode_pembayaran : 'tidak_tercatat')
            ->map(fn (Collection $group, string $metode) => [
                'metode' => $metode,
                'jumlah_transaksi' => (int) $group->sum('jumlah_transaksi'),
                'total_pendapatan' => (float) $group->sum('total_pendapatan'),
            ])
            ->sortByDesc('total_pendapatan')
            ->values()
            ->all();

        $branchRecap = DB::table('tbl_transaksi as t')
            ->leftJoin('tbl_cabang as c', 't.id_cabang', '=', 'c.id_cabang')
            ->when($startDate, fn ($q) => $q->where('t.tanggal', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('t.tanggal', '<=', $endDate))
            ->when($cabangId, fn ($q) => $q->where('t.id_cabang', $cabangId))
            ->when($pelangganId, fn ($q) => $q->where('t.id_pelanggan', $pelangganId))
            ->when($kasirId, fn ($q) => $q->where('t.user_id', $kasirId))
            ->where(function (QueryBuilder $q) {
                $q->where('t.status', '!=', Transaksi::STATUS_DIBATALKAN)
                    ->orWhereNull('t.status');
            })
            ->select(['t.id_cabang', 'c.nama_cabang', 'c.kota'])
            ->selectRaw('COUNT(*) as jumlah_transaksi')
            ->selectRaw('COALESCE(SUM(t.total_belanja), 0) as total_pendapatan')
            ->groupBy(['t.id_cabang', 'c.nama_cabang', 'c.kota'])
            ->orderByDesc('total_pendapatan')
            ->get()
            ->map(fn ($row) => [
                'nama_cabang' => $row->nama_cabang ? (string) $row->nama_cabang : 'Tanpa Cabang',
                'kota' => $row->kota ? (string) $row->kota : '-',
                'jumlah_transaksi' => (int) $row->jumlah_transaksi,
                'total_pendapatan' => (float) $row->total_pendapatan,
            ])
            ->all();

        $cashierRecap = DB::table('tbl_transaksi as t')
            ->leftJoin('users as u', 't.user_id', '=', 'u.id')
            ->when($startDate, fn ($q) => $q->where('t.tanggal', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('t.tanggal', '<=', $endDate))
            ->when($cabangId, fn ($q) => $q->where('t.id_cabang', $cabangId))
            ->when($pelangganId, fn ($q) => $q->where('t.id_pelanggan', $pelangganId))
            ->when($kasirId, fn ($q) => $q->where('t.user_id', $kasirId))
            ->where(function (QueryBuilder $q) {
                $q->where('t.status', '!=', Transaksi::STATUS_DIBATALKAN)
                    ->orWhereNull('t.status');
            })
            ->select(['t.user_id', 'u.name', 'u.role'])
            ->selectRaw('MAX(t.nama_kasir) as snapshot_name')
            ->selectRaw('COUNT(*) as jumlah_transaksi')
            ->selectRaw('COALESCE(SUM(t.total_belanja), 0) as total_pendapatan')
            ->groupBy(['t.user_id', 'u.name', 'u.role'])
            ->orderByDesc('total_pendapatan')
            ->get()
            ->map(fn ($row) => [
                'user_id' => $row->user_id ? (int) $row->user_id : null,
                'nama_kasir' => $row->name
                    ? (string) $row->name
                    : ($row->snapshot_name ? (string) $row->snapshot_name : 'Transaksi Legacy / Sistem'),
                'role' => $row->role ? (string) $row->role : 'legacy',
                'jumlah_transaksi' => (int) $row->jumlah_transaksi,
                'total_pendapatan' => (float) $row->total_pendapatan,
            ])
            ->all();

        $aggregatedProductRows = $this->aggregateProductSales(
            $startDate,
            $endDate,
            $cabangId,
            $pelangganId,
            'revenue',
            $kasirId,
            null,
            $productSearch,
        );

        $productPaginator = $this->paginateCollection($aggregatedProductRows, 10, 'product_page');

        return [
            'aggregates' => [
                'total_transaksi' => (int) ($aggregatesRow->total_transaksi ?? 0),
                'total_dibatalkan' => $totalDibatalkan,
                'total_pendapatan' => (float) ($aggregatesRow->total_pendapatan ?? 0),
                'rata_rata' => round((float) ($aggregatesRow->rata_rata ?? 0), 2),
                'total_item_terjual' => $totalItemTerjual,
            ],
            'salesChart' => $salesChart,
            'chartSeries' => $salesChart['daily'],
            'dailyRecap' => $dailyRecap,
            'paymentMethodRecap' => $paymentMethodRecap,
            'branchRecap' => $branchRecap,
            'cashierRecap' => $cashierRecap,
            'productSales' => $productPaginator,
        ];
    }

    public function getDailyRecapCollection(
        ?string $startDate,
        ?string $endDate,
        ?int $cabangId,
        ?int $pelangganId = null,
        ?int $kasirId = null,
    ): Collection {
        $filteredTransactions = $this->applyNonCancelledFilter(
            $this->buildTransactionBaseQuery(
                $startDate,
                $endDate,
                $cabangId,
                $pelangganId,
                null,
                $kasirId,
            )
        );

        return $this->buildDailyRecapQuery($filteredTransactions)
            ->get()
            ->map(fn ($row) => $this->mapDailyRecapRow($row));
    }

    public function getTopProductsReport(
        ?string $startDate,
        ?string $endDate,
        ?int $cabangId = null,
        ?int $pelangganId = null,
        ?int $kasirId = null,
        ?int $kategoriId = null,
        ?string $search = null,
        string $sortBy = 'qty',
        int $perPage = 15,
    ): array {
        $rows = $this->aggregateProductSales(
            $startDate,
            $endDate,
            $cabangId,
            $pelangganId,
            $sortBy,
            $kasirId,
            $kategoriId,
            $search,
        );

        $topRow = $rows->first();

        return [
            'aggregates' => [
                'total_sku_terjual' => $rows->count(),
                'total_unit_terjual' => (int) $rows->sum('total_terjual'),
                'total_pendapatan' => round((float) $rows->sum('total_pendapatan'), 2),
                'top_product_name' => $topRow['nama_produk'] ?? null,
                'top_product_qty' => (int) ($topRow['total_terjual'] ?? 0),
                'top_product_revenue' => (float) ($topRow['total_pendapatan'] ?? 0),
            ],
            'chartTopProducts' => $rows->take(10)->values()->all(),
            'products' => $this->paginateCollection($rows, $perPage, 'page'),
            'allRows' => $rows,
        ];
    }

    public function getCustomerReport(
        ?string $search = null,
        ?string $startDate = null,
        ?string $endDate = null,
        int $perPage = 15,
    ): array {
        $baseQuery = $this->buildCustomerReportQuery($search, $startDate, $endDate);

        $summarySub = DB::query()->fromSub((clone $baseQuery)->toBase(), 'customer_agg');
        $aggRow = $summarySub
            ->selectRaw('COUNT(*) as total_pelanggan')
            ->selectRaw('SUM(CASE WHEN total_transaksi > 0 THEN 1 ELSE 0 END) as pelanggan_aktif')
            ->selectRaw('COALESCE(SUM(total_transaksi), 0) as total_transaksi')
            ->selectRaw('COALESCE(SUM(total_belanja), 0) as total_belanja')
            ->first();

        /** @var LengthAwarePaginator $paginator */
        $paginator = (clone $baseQuery)
            ->orderByDesc('total_belanja')
            ->orderBy('tbl_pelanggan.nama_pelanggan')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'aggregates' => [
                'total_pelanggan' => (int) ($aggRow->total_pelanggan ?? 0),
                'pelanggan_aktif' => (int) ($aggRow->pelanggan_aktif ?? 0),
                'total_transaksi' => (int) ($aggRow->total_transaksi ?? 0),
                'total_belanja' => (float) ($aggRow->total_belanja ?? 0),
            ],
            'customers' => $paginator,
        ];
    }

    public function getCustomerReportQuery(
        ?string $search = null,
        ?string $startDate = null,
        ?string $endDate = null,
    ): Builder {
        return $this->buildCustomerReportQuery($search, $startDate, $endDate)
            ->orderByDesc('total_belanja')
            ->orderBy('tbl_pelanggan.nama_pelanggan');
    }

    private function buildTransactionBaseQuery(
        ?string $startDate,
        ?string $endDate,
        ?int $cabangId,
        ?int $pelangganId = null,
        ?string $status = null,
        ?int $kasirId = null,
        ?string $search = null,
    ): Builder {
        return Transaksi::query()
            ->when($startDate, fn ($q) => $q->where('tanggal', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('tanggal', '<=', $endDate))
            ->when($cabangId, fn ($q) => $q->where('id_cabang', $cabangId))
            ->when($pelangganId, fn ($q) => $q->where('id_pelanggan', $pelangganId))
            ->when($kasirId, fn ($q) => $q->where('user_id', $kasirId))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($search, function (Builder $query) use ($search) {
                $like = '%'.trim($search).'%';
                $query->where(function (Builder $q) use ($like) {
                    $q->where('kode_transaksi', 'like', $like)
                        ->orWhere('nama_kasir', 'like', $like)
                        ->orWhereHas('pelanggan', fn ($sub) => $sub->where('nama_pelanggan', 'like', $like))
                        ->orWhereHas('user', fn ($sub) => $sub->where('name', 'like', $like))
                        ->orWhereHas('detail', fn ($sub) => $sub->where('nama_produk', 'like', $like));
                });
            });
    }

    private function buildCustomerReportQuery(
        ?string $search = null,
        ?string $startDate = null,
        ?string $endDate = null,
    ): Builder {
        return Pelanggan::query()
            ->search($search)
            ->leftJoin('tbl_transaksi', function ($join) use ($startDate, $endDate) {
                $join->on('tbl_pelanggan.id_pelanggan', '=', 'tbl_transaksi.id_pelanggan')
                    ->when($startDate, fn ($q) => $q->where('tbl_transaksi.tanggal', '>=', $startDate))
                    ->when($endDate, fn ($q) => $q->where('tbl_transaksi.tanggal', '<=', $endDate))
                    ->where(function ($q) {
                        $q->where('tbl_transaksi.status', '!=', Transaksi::STATUS_DIBATALKAN)
                            ->orWhereNull('tbl_transaksi.status');
                    });
            })
            ->select([
                'tbl_pelanggan.id_pelanggan',
                'tbl_pelanggan.nama_pelanggan',
                'tbl_pelanggan.kota',
                'tbl_pelanggan.no_hp',
                DB::raw('COUNT(tbl_transaksi.id_transaksi) as total_transaksi'),
                DB::raw('COALESCE(SUM(tbl_transaksi.total_belanja), 0) as total_belanja'),
            ])
            ->groupBy([
                'tbl_pelanggan.id_pelanggan',
                'tbl_pelanggan.nama_pelanggan',
                'tbl_pelanggan.kota',
                'tbl_pelanggan.no_hp',
            ]);
    }

    private function buildDailyRecapQuery(Builder $filteredTransactions): Builder
    {
        return (clone $filteredTransactions)
            ->whereNotNull('tanggal')
            ->select('tanggal')
            ->selectRaw('COUNT(*) as jumlah_transaksi')
            ->selectRaw('COALESCE(SUM(total_belanja), 0) as total_pendapatan')
            ->selectRaw('COALESCE(AVG(total_belanja), 0) as rata_rata')
            ->groupBy('tanggal')
            ->orderByDesc('tanggal');
    }

    /**
     * @return array<string, mixed>
     */
    private function mapDailyRecapRow(object $row): array
    {
        $dateStr = $row->tanggal instanceof Carbon ? $row->tanggal->toDateString() : (string) $row->tanggal;

        return [
            'tanggal' => $dateStr,
            'jumlah_transaksi' => (int) $row->jumlah_transaksi,
            'total_pendapatan' => (float) $row->total_pendapatan,
            'rata_rata' => round((float) $row->rata_rata, 2),
        ];
    }

    private function applyNonCancelledFilter(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('status', '!=', Transaksi::STATUS_DIBATALKAN)
                ->orWhereNull('status');
        });
    }

    private function getDashboardSalesChart(): array
    {
        $base = $this->applyNonCancelledFilter(Transaksi::query());

        return $this->buildSalesChartSeries($base, 14, 12);
    }

    private function buildSalesChartSeries(Builder $filteredTransactions, int $dailyLimit = 30, int $monthlyLimit = 12): array
    {
        $daily = (clone $filteredTransactions)
            ->whereNotNull('tanggal')
            ->select('tanggal')
            ->selectRaw('COUNT(*) as total_transaksi')
            ->selectRaw('COALESCE(SUM(total_belanja), 0) as total_pendapatan')
            ->groupBy('tanggal')
            ->orderByDesc('tanggal')
            ->limit($dailyLimit)
            ->get()
            ->reverse()
            ->values()
            ->map(function ($row) {
                $dateStr = $row->tanggal instanceof Carbon ? $row->tanggal->toDateString() : (string) $row->tanggal;

                return [
                    'key' => $dateStr,
                    'label' => Carbon::parse($dateStr)->locale('id')->translatedFormat('d M'),
                    'full_label' => Carbon::parse($dateStr)->locale('id')->translatedFormat('d M Y'),
                    'total_transaksi' => (int) $row->total_transaksi,
                    'total_pendapatan' => (float) $row->total_pendapatan,
                ];
            })
            ->all();

        $monthly = (clone $filteredTransactions)
            ->whereNotNull('tanggal')
            ->selectRaw('YEAR(tanggal) as yr, MONTH(tanggal) as mo')
            ->selectRaw('COUNT(*) as total_transaksi')
            ->selectRaw('COALESCE(SUM(total_belanja), 0) as total_pendapatan')
            ->groupByRaw('YEAR(tanggal), MONTH(tanggal)')
            ->orderByDesc('yr')
            ->orderByDesc('mo')
            ->limit($monthlyLimit)
            ->get()
            ->reverse()
            ->values()
            ->map(function ($row) {
                $monthKey = sprintf('%04d-%02d', (int) $row->yr, (int) $row->mo);
                $carbon = Carbon::createFromFormat('Y-m', $monthKey);

                return [
                    'key' => $monthKey,
                    'label' => $carbon->locale('id')->translatedFormat('M Y'),
                    'full_label' => $carbon->locale('id')->translatedFormat('F Y'),
                    'total_transaksi' => (int) $row->total_transaksi,
                    'total_pendapatan' => (float) $row->total_pendapatan,
                ];
            })
            ->all();

        return [
            'daily' => $daily,
            'monthly' => $monthly,
        ];
    }

    private function getTopSellingProducts(int $limit = 5): array
    {
        return $this->aggregateProductSales(null, null, null, null, 'qty')
            ->take($limit)
            ->values()
            ->all();
    }

    private function aggregateProductSales(
        ?string $startDate,
        ?string $endDate,
        ?int $cabangId,
        ?int $pelangganId,
        string $sortBy = 'qty',
        ?int $kasirId = null,
        ?int $kategoriId = null,
        ?string $search = null,
    ): Collection {
        $rows = DB::table('tbl_detail_transaksi as d')
            ->join('tbl_transaksi as t', 'd.id_transaksi', '=', 't.id_transaksi')
            ->leftJoin('tbl_produk as p', 'd.id_produk', '=', 'p.id_produk')
            ->when($startDate, fn ($q) => $q->where('t.tanggal', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('t.tanggal', '<=', $endDate))
            ->when($cabangId, fn ($q) => $q->where('t.id_cabang', $cabangId))
            ->when($pelangganId, fn ($q) => $q->where('t.id_pelanggan', $pelangganId))
            ->when($kasirId, fn ($q) => $q->where('t.user_id', $kasirId))
            ->where(function (QueryBuilder $q) {
                $q->where('t.status', '!=', Transaksi::STATUS_DIBATALKAN)
                    ->orWhereNull('t.status');
            })
            ->select([
                'd.id_produk',
                'd.nama_produk as detail_nama',
                'p.nama_produk as master_nama',
            ])
            ->selectRaw('COALESCE(SUM(d.jumlah), 0) as total_terjual')
            ->selectRaw('COALESCE(SUM(d.jumlah * d.harga), 0) as total_pendapatan')
            ->selectRaw('COUNT(DISTINCT d.id_transaksi) as total_transaksi')
            ->groupBy(['d.id_produk', 'd.nama_produk', 'p.nama_produk'])
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $grouped = $rows
            ->groupBy(fn ($row) => (string) ($row->master_nama ?: $row->detail_nama))
            ->map(function (Collection $items, string $namaProduk) {
                $idProduk = $items->pluck('id_produk')->filter()->first();
                $totalTerjual = (int) $items->sum('total_terjual');
                $totalPendapatan = (float) $items->sum('total_pendapatan');
                $totalTransaksi = (int) $items->sum('total_transaksi');

                return [
                    'id_produk' => $idProduk ? (int) $idProduk : null,
                    'nama_produk' => $namaProduk,
                    'total_terjual' => $totalTerjual,
                    'total_pendapatan' => $totalPendapatan,
                    'harga_rata_rata' => $totalTerjual > 0 ? round($totalPendapatan / $totalTerjual, 2) : 0.0,
                    'jumlah_transaksi' => $totalTransaksi,
                    'total_transaksi' => $totalTransaksi,
                ];
            });

        $productIds = $grouped->pluck('id_produk')->filter()->unique()->values();
        $productNames = $grouped->pluck('nama_produk')->filter()->unique()->values();

        $products = Produk::query()
            ->withTrashed()
            ->with('kategori:id_kategori,nama_kategori')
            ->where(function (Builder $q) use ($productIds, $productNames) {
                if ($productIds->isNotEmpty()) {
                    $q->whereIn('id_produk', $productIds)
                        ->orWhereIn('nama_produk', $productNames);
                } else {
                    $q->whereIn('nama_produk', $productNames);
                }
            })
            ->get();

        $byId = $products->keyBy('id_produk');
        $byName = $products->keyBy('nama_produk');

        $enriched = $grouped->map(function (array $row) use ($byId, $byName) {
            $matched = $row['id_produk']
                ? $byId->get($row['id_produk'])
                : $byName->get($row['nama_produk']);

            return [
                ...$row,
                'id_produk' => $matched?->id_produk,
                'kode_produk' => $matched?->kode_produk,
                'id_kategori' => $matched?->id_kategori,
                'kategori' => $matched?->kategori?->nama_kategori,
                'stok' => $matched ? (int) $matched->stok : null,
            ];
        });

        if ($kategoriId !== null) {
            $enriched = $enriched->filter(
                fn (array $row) => (int) ($row['id_kategori'] ?? 0) === $kategoriId
            );
        }

        if ($search !== null && trim($search) !== '') {
            $needle = mb_strtolower(trim($search));
            $enriched = $enriched->filter(function (array $row) use ($needle) {
                $haystack = mb_strtolower(implode(' ', array_filter([
                    $row['nama_produk'] ?? '',
                    $row['kode_produk'] ?? '',
                    $row['kategori'] ?? '',
                ])));

                return str_contains($haystack, $needle);
            });
        }

        return $sortBy === 'revenue'
            ? $enriched->sort(fn (array $a, array $b) => [$b['total_pendapatan'], $b['total_terjual']] <=> [$a['total_pendapatan'], $a['total_terjual']])->values()
            : $enriched->sort(fn (array $a, array $b) => [$b['total_terjual'], $b['total_pendapatan']] <=> [$a['total_terjual'], $a['total_pendapatan']])->values();
    }

    private function paginateCollection(Collection $items, int $perPage, string $pageName = 'page'): ConcretePaginator
    {
        $page = max(1, (int) request()->query($pageName, 1));

        return (new ConcretePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => $pageName,
            ],
        ))->withQueryString();
    }

    private function getStockSummary(): array
    {
        $counts = Produk::query()
            ->selectRaw('COUNT(*) as total_sku')
            ->selectRaw('COALESCE(SUM(stok), 0) as total_unit')
            ->selectRaw('SUM(CASE WHEN stok > 5 THEN 1 ELSE 0 END) as stok_aman')
            ->selectRaw('SUM(CASE WHEN stok > 0 AND stok <= 5 THEN 1 ELSE 0 END) as stok_menipis')
            ->selectRaw('SUM(CASE WHEN stok <= 0 THEN 1 ELSE 0 END) as stok_habis')
            ->selectRaw('SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as produk_nonaktif')
            ->first();

        $lowStockProducts = Produk::query()
            ->with('kategori:id_kategori,nama_kategori')
            ->where('stok', '<=', 5)
            ->orderBy('stok')
            ->orderBy('nama_produk')
            ->limit(5)
            ->get(['id_produk', 'id_kategori', 'kode_produk', 'nama_produk', 'stok', 'harga', 'is_active'])
            ->map(fn (Produk $p) => [
                'id_produk' => $p->id_produk,
                'kode_produk' => $p->kode_produk,
                'nama_produk' => $p->nama_produk,
                'kategori' => $p->kategori?->nama_kategori,
                'stok' => (int) $p->stok,
                'harga' => (float) $p->harga,
                'is_active' => (bool) $p->is_active,
            ])
            ->all();

        $lowestStockProducts = Produk::query()
            ->with('kategori:id_kategori,nama_kategori')
            ->active()
            ->orderBy('stok')
            ->orderBy('nama_produk')
            ->limit(5)
            ->get(['id_produk', 'id_kategori', 'kode_produk', 'nama_produk', 'stok', 'harga', 'is_active'])
            ->map(fn (Produk $p) => [
                'id_produk' => $p->id_produk,
                'kode_produk' => $p->kode_produk,
                'nama_produk' => $p->nama_produk,
                'kategori' => $p->kategori?->nama_kategori,
                'stok' => (int) $p->stok,
                'harga' => (float) $p->harga,
                'is_active' => (bool) $p->is_active,
            ])
            ->all();

        return [
            'total_sku' => (int) ($counts->total_sku ?? 0),
            'total_unit' => (int) ($counts->total_unit ?? 0),
            'stok_aman' => (int) ($counts->stok_aman ?? 0),
            'stok_menipis' => (int) ($counts->stok_menipis ?? 0),
            'stok_habis' => (int) ($counts->stok_habis ?? 0),
            'produk_nonaktif' => (int) ($counts->produk_nonaktif ?? 0),
            'low_stock_products' => $lowStockProducts,
            'lowest_stock_products' => $lowestStockProducts,
        ];
    }
}
