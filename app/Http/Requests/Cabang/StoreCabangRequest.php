<?php

namespace App\Http\Requests\Cabang;

use App\Models\Cabang;
use Illuminate\Foundation\Http\FormRequest;

class StoreCabangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', Cabang::class);
    }

    public function rules(): array
    {
        return [
            'nama_cabang' => ['required', 'string', 'max:100'],
            'kota' => ['required', 'string', 'max:50'],
        ];
    }
}
