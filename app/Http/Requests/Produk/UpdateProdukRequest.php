<?php

namespace App\Http\Requests\Produk;

use App\Models\Produk;
use App\Services\ImageCompressionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProdukRequest extends FormRequest
{
    public function authorize(): bool
    {
        $produk = $this->route('produk');

        return $produk instanceof Produk && (bool) $this->user()?->can('update', $produk);
    }

    public function rules(): array
    {
        $produk = $this->route('produk');
        $id = is_object($produk) ? $produk->getKey() : $produk;

        return [
            'id_kategori' => ['nullable', 'integer', 'exists:tbl_kategori,id_kategori'],
            'kode_produk' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('tbl_produk', 'kode_produk')->ignore($id, 'id_produk'),
            ],
            'nama_produk' => ['required', 'string', 'max:100'],
            'harga' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'stok' => ['required', 'integer', 'min:0'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'gambar' => ImageCompressionService::validationRules(),
            'is_featured' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
