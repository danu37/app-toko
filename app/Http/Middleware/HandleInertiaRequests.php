<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $user?->loadMissing('cabang:id_cabang,nama_cabang,kota');

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'id_cabang' => $user->id_cabang,
                    'is_active' => $user->is_active,
                    'cabang' => $user->cabang ? [
                        'id_cabang' => $user->cabang->id_cabang,
                        'nama_cabang' => $user->cabang->nama_cabang,
                        'kota' => $user->cabang->kota,
                    ] : null,
                ] : null,
                'can' => $user && $user->is_active ? [
                    'isAdmin' => $user->isAdmin(),
                    'isKasir' => $user->isKasir(),
                    'viewDashboard' => $user->isAdmin() || $user->isKasir(),
                    'viewProducts' => $user->isAdmin() || $user->isKasir(),
                    'createProducts' => $user->isAdmin() || $user->isKasir(),
                    'editProducts' => $user->isAdmin(),
                    'deleteProducts' => $user->isAdmin(),
                    'manageCategories' => $user->isAdmin(),
                    'viewCustomers' => $user->isAdmin() || $user->isKasir(),
                    'createCustomers' => $user->isAdmin() || $user->isKasir(),
                    'editCustomers' => $user->isAdmin(),
                    'deleteCustomers' => $user->isAdmin(),
                    'viewTransactions' => $user->isAdmin() || $user->isKasir(),
                    'viewTransactionHistory' => $user->can('view-transaction-history'),
                    'viewDailySales' => $user->can('view-daily-sales'),
                    'createTransactions' => $user->isAdmin() || $user->isKasir(),
                    'manageTransactions' => $user->isAdmin(),
                    'manageBranches' => $user->isAdmin(),
                    'viewReports' => $user->isAdmin(),
                    'manageUsers' => $user->isAdmin(),
                    'manageSettings' => $user->isAdmin(),
                ] : [],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
