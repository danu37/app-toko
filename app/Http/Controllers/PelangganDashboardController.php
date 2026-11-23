<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransaksiResource;
use App\Models\RfidCard;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PelangganDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $user->load(['pelanggan', 'rfidCards']);

        $hasLinkedCustomer = ! empty($user->id_pelanggan);

        $transaksiQuery = Transaksi::query()
            ->with(['cabang', 'detail.produk'])
            ->when($hasLinkedCustomer, fn ($q) => $q->where('id_pelanggan', $user->id_pelanggan))
            ->when(! $hasLinkedCustomer, fn ($q) => $q->whereRaw('1 = 0'))
            ->latest('tanggal')
            ->latest('id_transaksi');

        $totalBelanja = $hasLinkedCustomer ? (int) (clone $transaksiQuery)->sum('total_belanja') : 0;
        $totalTransaksi = $hasLinkedCustomer ? (int) (clone $transaksiQuery)->count() : 0;
        $transactions = $transaksiQuery->paginate(10)->withQueryString();

        return Inertia::render('Pelanggan/Dashboard', [
            'customer' => $user->pelanggan,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'rfid_uid' => $user->rfid_uid,
                'is_active' => (bool) $user->is_active,
                'created_at' => $user->created_at?->toISOString(),
            ],
            'rfidCards' => $user->rfidCards->map(fn (RfidCard $c) => [
                'id' => $c->id,
                'uid' => $c->uid,
                'status' => $c->status,
                'is_active' => $c->isActive(),
                'registered_at' => $c->registered_at?->toISOString(),
                'last_used_at' => $c->last_used_at?->toISOString(),
            ]),
            'stats' => [
                'total_transaksi' => $totalTransaksi,
                'total_belanja' => $totalBelanja,
                'transaksi_terakhir' => $transactions->first()?->tanggal,
            ],
            'transactions' => TransaksiResource::collection($transactions),
        ]);
    }
}
