<?php

namespace App\Http\Requests\Pengguna;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', User::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'string', Rule::in(User::ROLES)],
            'rfid_uid' => ['nullable', 'string', 'max:100', 'unique:users,rfid_uid', 'unique:rfid_cards,uid'],
            'id_cabang' => ['nullable', 'integer', 'exists:tbl_cabang,id_cabang'],
            'id_pelanggan' => ['nullable', 'integer', 'exists:tbl_pelanggan,id_pelanggan'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
