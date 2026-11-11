<?php

namespace App\Http\Requests\Cabang;

use App\Models\Cabang;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCabangRequest extends FormRequest
{
    public function authorize(): bool
    {
        $cabang = $this->route('cabang');

        return $cabang instanceof Cabang && (bool) $this->user()?->can('update', $cabang);
    }

    public function rules(): array
    {
        return [
            'nama_cabang' => ['required', 'string', 'max:100'],
            'kota' => ['required', 'string', 'max:50'],
        ];
    }
}
