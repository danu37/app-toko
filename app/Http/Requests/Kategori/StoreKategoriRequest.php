<?php

namespace App\Http\Requests\Kategori;

use App\Models\Kategori;
use App\Services\ImageCompressionService;
use Illuminate\Foundation\Http\FormRequest;

class StoreKategoriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', Kategori::class);
    }

    public function rules(): array
    {
        return [
            'nama_kategori' => ['required', 'string', 'max:100', 'unique:tbl_kategori,nama_kategori'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'gambar' => ImageCompressionService::validationRules(),
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
