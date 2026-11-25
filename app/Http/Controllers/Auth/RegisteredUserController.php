<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Pelanggan;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Tampilkan halaman registrasi khusus Pelanggan.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Proses registrasi akun Pelanggan baru beserta data master pelanggan terhubung.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'kota' => ['nullable', 'string', 'max:100'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $pelanggan = Pelanggan::create([
                'nama_pelanggan' => $validated['name'],
                'no_hp' => $validated['no_hp'] ?? null,
                'kota' => $validated['kota'] ?? null,
                'alamat' => $validated['alamat'] ?? null,
            ]);

            return User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => User::ROLE_PELANGGAN,
                'id_pelanggan' => $pelanggan->id_pelanggan,
                'is_active' => true,
            ]);
        });

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect(route('pelanggan.dashboard', absolute: false));
    }
}
