<?php

namespace App\Http\Requests\Pelanggan;

use App\Services\ImageCompressionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePelangganOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return (bool) ($user && $user->is_active && $user->isPelanggan());
    }

    public function rules(): array
    {
        $imageRules = array_values(array_filter(
            ImageCompressionService::validationRules(),
            fn ($rule) => $rule !== 'nullable',
        ));

        return [
            'id_cabang' => ['nullable', 'integer', 'exists:tbl_cabang,id_cabang'],
            'metode_pembayaran' => ['required', 'string', Rule::in(['qris', 'transfer'])],
            'bukti_pembayaran' => array_merge(['required'], $imageRules),
            'catatan' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_produk' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('tbl_produk', 'id_produk')->where(
                    fn ($query) => $query->where('is_active', true)->whereNull('deleted_at'),
                ),
            ],
            'items.*.jumlah' => ['required', 'integer', 'min:1', 'max:100000'],
        ];
    }

    public function messages(): array
    {
        return [
            'metode_pembayaran.required' => 'Pilih metode pembayaran (QRIS atau Transfer Bank).',
            'metode_pembayaran.in' => 'Metode pembayaran hanya mendukung QRIS atau Transfer Bank.',
            'bukti_pembayaran.required' => 'Bukti pembayaran wajib diunggah.',
            'bukti_pembayaran.image' => 'Bukti pembayaran harus berupa file gambar (JPG, PNG, atau WEBP).',
            'items.required' => 'Pilih minimal 1 barang untuk dibeli.',
            'items.min' => 'Pilih minimal 1 barang untuk dibeli.',
        ];
    }
}
