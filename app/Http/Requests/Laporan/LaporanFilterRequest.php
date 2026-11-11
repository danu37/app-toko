<?php

namespace App\Http\Requests\Laporan;

use App\Models\Transaksi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class LaporanFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('view-reports');
    }

    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'cabang' => ['nullable', 'integer', 'min:1'],
            'pelanggan' => ['nullable', 'integer', 'min:1'],
            'kasir' => ['nullable', 'integer', 'min:1'],
            'kategori' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', Rule::in([Transaksi::STATUS_SELESAI, Transaksi::STATUS_DIBATALKAN])],
            'sort' => ['nullable', 'string', Rule::in(['qty', 'revenue'])],
            'search' => ['nullable', 'string', 'max:100'],
            'report' => ['nullable', 'string', Rule::in(['sales', 'transactions', 'products', 'customers'])],
        ];
    }

    /**
     * Ambil filter ternormalisasi yang aman dipakai service dan controller.
     *
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'start_date' => ! empty($validated['start_date'])
                ? Carbon::parse((string) $validated['start_date'])->toDateString()
                : null,
            'end_date' => ! empty($validated['end_date'])
                ? Carbon::parse((string) $validated['end_date'])->toDateString()
                : null,
            'cabang' => isset($validated['cabang']) ? (int) $validated['cabang'] : null,
            'pelanggan' => isset($validated['pelanggan']) ? (int) $validated['pelanggan'] : null,
            'kasir' => isset($validated['kasir']) ? (int) $validated['kasir'] : null,
            'kategori' => isset($validated['kategori']) ? (int) $validated['kategori'] : null,
            'status' => $validated['status'] ?? null,
            'sort' => $validated['sort'] ?? 'qty',
            'search' => isset($validated['search']) && trim($validated['search']) !== ''
                ? trim($validated['search'])
                : null,
            'report' => $validated['report'] ?? 'sales',
        ];
    }
}
