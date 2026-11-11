<?php

namespace App\Http\Requests\Kategori;

use App\Models\Kategori;
use App\Services\ImageCompressionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKategoriRequest extends FormRequest
{
    public function authorize(): bool
    {
        $kategori = $this->route('kategori');

        return $kategori instanceof Kategori && (bool) $this->user()?->can('update', $kategori);
    }

    public function rules(): array
    {
        $kategori = $this->route('kategori');
        $id = is_object($kategori) ? $kategori->getKey() : $kategori;

        return [
            'nama_kategori' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tbl_kategori', 'nama_kategori')->ignore($id, 'id_kategori'),
            ],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'gambar' => ImageCompressionService::validationRules(),
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
