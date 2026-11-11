<?php

namespace App\Http\Requests\Pelanggan;

use App\Models\Pelanggan;
use Illuminate\Foundation\Http\FormRequest;

class StorePelangganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', Pelanggan::class);
    }

    public function rules(): array
    {
        return [
            'nama_pelanggan' => ['required', 'string', 'max:100'],
            'kota' => ['required', 'string', 'max:50'],
            'no_hp' => ['nullable', 'string', 'max:20', 'unique:tbl_pelanggan,no_hp'],
            'alamat' => ['nullable', 'string', 'max:1000'],
            'from' => ['nullable', 'string', 'in:pos'],
        ];
    }
}
