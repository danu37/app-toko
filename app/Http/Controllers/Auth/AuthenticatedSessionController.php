<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RfidLoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $intended = $request->session()->pull('url.intended');
        $targetRoute = $this->redirectRouteFor($request->user());

        if (! $intended || $intended === url('/') || $intended === route('home') || $intended === route('login')) {
            return redirect()->route($targetRoute);
        }

        return redirect()->to($intended);
    }

    /**
     * Handle an incoming RFID authentication request.
     */
    public function storeRfid(RfidLoginRequest $request): RedirectResponse
    {
        $user = $request->authenticate();

        $request->session()->regenerate();

        $intended = $request->session()->pull('url.intended');
        $targetRoute = $this->redirectRouteFor($user);

        if (! $intended || $intended === url('/') || $intended === route('home') || $intended === route('login')) {
            return redirect()->route($targetRoute)
                ->with('success', 'Selamat datang kembali, '.$user->name.'! Berhasil masuk via RFID.');
        }

        return redirect()->to($intended)
            ->with('success', 'Selamat datang kembali, '.$user->name.'! Berhasil masuk via RFID.');
    }

    private function redirectRouteFor(?User $user): string
    {
        return match ($user?->role) {
            User::ROLE_ADMIN => 'dashboard',
            User::ROLE_KASIR => 'dashboard',
            User::ROLE_PELANGGAN => 'pelanggan.dashboard',
            default => 'home',
        };
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
