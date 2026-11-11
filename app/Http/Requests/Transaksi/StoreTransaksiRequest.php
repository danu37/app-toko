<?php

namespace App\Http\Requests\Transaksi;

use App\Models\Transaksi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransaksiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', Transaksi::class);
    }

    public function rules(): array
    {
        return [
            'id_pelanggan' => ['required', 'integer', 'exists:tbl_pelanggan,id_pelanggan'],
            'id_cabang' => ['nullable', 'integer', 'exists:tbl_cabang,id_cabang'],
            'tanggal' => ['nullable', 'date'],
            'metode_pembayaran' => ['nullable', 'string', Rule::in(['tunai', 'transfer', 'qris', 'debit'])],
            'uang_bayar' => [
                Rule::requiredIf(fn () => ($this->input('metode_pembayaran') ?? 'tunai') === 'tunai'),
                'nullable',
                'numeric',
                'min:0',
                'max:999999999',
            ],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_produk' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('tbl_produk', 'id_produk')->where(fn ($query) => $query->where('is_active', true)->whereNull('deleted_at')),
            ],
            'items.*.jumlah' => ['required', 'integer', 'min:1', 'max:100000'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_pelanggan.required' => 'Pelanggan wajib dipilih sebelum memproses transaksi.',
            'id_pelanggan.exists' => 'Pelanggan yang dipilih tidak ditemukan di database.',
            'uang_bayar.required' => 'Nominal pembayaran wajib dimasukkan untuk pembayaran tunai.',
            'uang_bayar.numeric' => 'Nominal pembayaran harus berupa angka yang valid.',
            'uang_bayar.min' => 'Nominal pembayaran tidak boleh bernilai negatif.',
            'items.required' => 'Minimal satu barang harus ditambahkan ke transaksi.',
            'items.min' => 'Minimal satu barang harus ditambahkan ke transaksi.',
            'items.*.id_produk.distinct' => 'Produk yang sama tidak boleh diduplikasi dalam daftar item.',
            'items.*.id_produk.exists' => 'Produk yang dipilih tidak valid atau sudah tidak aktif.',
            'items.*.jumlah.min' => 'Jumlah item minimal 1.',
        ];
    }
}
