<?php

namespace App\Http\Requests\Pelanggan;

use App\Models\Pelanggan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePelangganRequest extends FormRequest
{
    public function authorize(): bool
    {
        $pelanggan = $this->route('pelanggan');

        return $pelanggan instanceof Pelanggan && (bool) $this->user()?->can('update', $pelanggan);
    }

    public function rules(): array
    {
        $pelanggan = $this->route('pelanggan');
        $id = is_object($pelanggan) ? $pelanggan->getKey() : $pelanggan;

        return [
            'nama_pelanggan' => ['required', 'string', 'max:100'],
            'kota' => ['required', 'string', 'max:50'],
            'no_hp' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('tbl_pelanggan', 'no_hp')->ignore($id, 'id_pelanggan'),
            ],
            'alamat' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
