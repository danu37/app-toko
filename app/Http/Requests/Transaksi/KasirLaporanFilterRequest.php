<?php

namespace App\Http\Requests\Transaksi;

use App\Models\Transaksi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class KasirLaporanFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('view-transaction-history');
    }

    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'cabang' => ['nullable', 'integer', 'min:1'],
            'kasir' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', Rule::in([Transaksi::STATUS_SELESAI, Transaksi::STATUS_DIBATALKAN])],
            'metode' => ['nullable', 'string', Rule::in(['tunai', 'qris', 'transfer', 'debit'])],
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Ambil filter ternormalisasi untuk laporan operasional kasir.
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
            'kasir' => isset($validated['kasir']) ? (int) $validated['kasir'] : null,
            'status' => $validated['status'] ?? null,
            'metode' => $validated['metode'] ?? null,
            'search' => isset($validated['search']) && trim($validated['search']) !== ''
                ? trim($validated['search'])
                : null,
        ];
    }
}
