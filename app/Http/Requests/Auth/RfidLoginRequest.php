<?php

namespace App\Http\Requests\Auth;

use App\Models\RfidCard;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RfidLoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rfid_uid' => ['required', 'string', 'max:100'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'rfid_uid' => 'UID Kartu RFID',
        ];
    }

    /**
     * Attempt to authenticate the user (Admin, Kasir, or Pelanggan) using RFID card UID.
     *
     * @throws ValidationException
     */
    public function authenticate(): User
    {
        $this->ensureIsNotRateLimited();

        $rawUid = trim($this->string('rfid_uid')->toString());

        // 1. Cari kartu di tabel rfid_cards
        $card = RfidCard::query()
            ->with('user')
            ->where('uid', $rawUid)
            ->orWhereRaw('LOWER(uid) = ?', [strtolower($rawUid)])
            ->first();

        // Fallback untuk pencarian langsung di tabel users (kompatibilitas historis)
        $user = $card?->user;
        if (! $user && ! $card) {
            $user = User::query()
                ->where('rfid_uid', $rawUid)
                ->orWhereRaw('LOWER(rfid_uid) = ?', [strtolower($rawUid)])
                ->first();
        }

        // 2. Jika UID tidak terdaftar
        if (! $card && ! $user) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'rfid_uid' => 'Kartu RFID tidak dikenali.',
            ]);
        }

        // 3. Periksa status keaktifan kartu
        if ($card && ! $card->isActive()) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'rfid_uid' => 'Kartu RFID ini sedang dinonaktifkan. Hubungi administrator.',
            ]);
        }

        // 4. Periksa status keaktifan akun pengguna
        if (! $user || ! $user->is_active) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'rfid_uid' => 'Akun pengguna sedang dinonaktifkan. Hubungi administrator.',
            ]);
        }

        // Catat waktu penggunaan terakhir kartu
        if ($card) {
            $card->update(['last_used_at' => now()]);
        }

        RateLimiter::clear($this->throttleKey());

        Auth::login($user, $this->boolean('remember'));

        return $user;
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'rfid_uid' => 'Terlalu banyak percobaan login RFID. Silakan tunggu '.$seconds.' detik sebelum mencoba kembali.',
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate('rfid_login|'.$this->ip());
    }
}
