<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PengaturanController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('manage-settings');

        $counts = DB::selectOne(
            'SELECT '.
            '(SELECT COUNT(*) FROM users) as users_count, '.
            '(SELECT COUNT(*) FROM tbl_cabang) as branches_count, '.
            '(SELECT COUNT(*) FROM tbl_kategori) as categories_count, '.
            '(SELECT COUNT(*) FROM tbl_produk WHERE deleted_at IS NULL) as products_count, '.
            '(SELECT COUNT(*) FROM tbl_pelanggan) as customers_count, '.
            '(SELECT COUNT(*) FROM tbl_transaksi) as transactions_count'
        );

        return Inertia::render('Pengaturan/Index', [
            'settings' => [
                'app_name' => config('app.name'),
                'app_env' => config('app.env'),
                'timezone' => config('app.timezone'),
                'locale' => config('app.locale'),
                'storage_linked' => file_exists(public_path('storage')),
                'stats' => [
                    'users' => (int) ($counts->users_count ?? 0),
                    'branches' => (int) ($counts->branches_count ?? 0),
                    'categories' => (int) ($counts->categories_count ?? 0),
                    'products' => (int) ($counts->products_count ?? 0),
                    'customers' => (int) ($counts->customers_count ?? 0),
                    'transactions' => (int) ($counts->transactions_count ?? 0),
                ],
            ],
        ]);
    }
}
