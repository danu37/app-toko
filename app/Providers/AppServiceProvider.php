<?php

namespace App\Providers;

use App\Models\Cabang;
use App\Models\Kategori;
use App\Models\Pelanggan;
use App\Models\Produk;
use App\Models\Transaksi;
use App\Models\User;
use App\Policies\CabangPolicy;
use App\Policies\KategoriPolicy;
use App\Policies\PelangganPolicy;
use App\Policies\ProdukPolicy;
use App\Policies\TransaksiPolicy;
use App\Policies\UserPolicy;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        JsonResource::withoutWrapping();

        Gate::policy(Kategori::class, KategoriPolicy::class);
        Gate::policy(Produk::class, ProdukPolicy::class);
        Gate::policy(Pelanggan::class, PelangganPolicy::class);
        Gate::policy(Cabang::class, CabangPolicy::class);
        Gate::policy(Transaksi::class, TransaksiPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Gate::define('view-reports', function (User $user): bool {
            return $user->is_active && $user->isAdmin();
        });

        Gate::define('view-daily-sales', function (User $user): bool {
            return $user->is_active && $user->hasRole([User::ROLE_ADMIN, User::ROLE_KASIR]);
        });

        Gate::define('view-transaction-history', function (User $user): bool {
            return $user->is_active && $user->hasRole([User::ROLE_ADMIN, User::ROLE_KASIR]);
        });

        Gate::define('manage-settings', function (User $user): bool {
            return $user->is_active && $user->isAdmin();
        });
    }
}
