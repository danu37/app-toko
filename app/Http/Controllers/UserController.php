<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pengguna\StoreUserRequest;
use App\Http\Requests\Pengguna\UpdateUserRequest;
use App\Http\Requests\Pengguna\UpdateUserRoleRequest;
use App\Http\Resources\CabangResource;
use App\Http\Resources\UserResource;
use App\Models\Cabang;
use App\Models\Pelanggan;
use App\Models\RfidCard;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $search = $request->string('search')->toString();
        $role = $request->string('role')->toString();
        $status = $request->string('status')->toString();

        $users = User::query()
            ->with(['cabang', 'pelanggan', 'rfidCards'])
            ->when($search, function ($query) use ($search) {
                $like = '%'.trim($search).'%';
                $query->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('rfid_uid', 'like', $like)
                        ->orWhereHas('rfidCards', fn ($rc) => $rc->where('uid', 'like', $like));
                });
            })
            ->when(in_array($role, User::ROLES, true), fn ($q) => $q->where('role', $role))
            ->when($status === 'aktif', fn ($q) => $q->where('is_active', true))
            ->when($status === 'nonaktif', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $statsRow = User::query()
            ->selectRaw(
                'COUNT(*) as total, '.
                'COALESCE(SUM(CASE WHEN role = ? THEN 1 ELSE 0 END), 0) as admin_count, '.
                'COALESCE(SUM(CASE WHEN role = ? THEN 1 ELSE 0 END), 0) as kasir_count, '.
                'COALESCE(SUM(CASE WHEN role = ? THEN 1 ELSE 0 END), 0) as pelanggan_count, '.
                'COALESCE(SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END), 0) as aktif_count, '.
                'COALESCE(SUM(CASE WHEN rfid_uid IS NOT NULL AND rfid_uid != \'\' THEN 1 ELSE 0 END), 0) as rfid_count',
                [User::ROLE_ADMIN, User::ROLE_KASIR, User::ROLE_PELANGGAN]
            )
            ->first();

        $roleStats = [
            'total' => (int) ($statsRow->total ?? 0),
            'admin' => (int) ($statsRow->admin_count ?? 0),
            'kasir' => (int) ($statsRow->kasir_count ?? 0),
            'pelanggan' => (int) ($statsRow->pelanggan_count ?? 0),
            'aktif' => (int) ($statsRow->aktif_count ?? 0),
            'rfid' => (int) ($statsRow->rfid_count ?? 0),
        ];

        return Inertia::render('Pengguna/Index', [
            'users' => UserResource::collection($users),
            'branches' => CabangResource::collection(Cabang::query()->orderBy('nama_cabang')->get()),
            'roles' => User::ROLES,
            'roleStats' => $roleStats,
            'filters' => [
                'search' => $search,
                'role' => $role,
                'status' => $status,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('Pengguna/Create', [
            'branches' => CabangResource::collection(Cabang::query()->orderBy('nama_cabang')->get()),
            'customers' => Pelanggan::query()->orderBy('nama_pelanggan')->get(['id_pelanggan', 'nama_pelanggan', 'no_hp', 'kota']),
            'roles' => User::ROLES,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $rfidUid = filled($validated['rfid_uid'] ?? null) ? trim($validated['rfid_uid']) : null;
        $idPelanggan = $validated['role'] === User::ROLE_PELANGGAN ? ($validated['id_pelanggan'] ?? null) : null;

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'email_verified_at' => now(),
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'rfid_uid' => $rfidUid,
            'id_cabang' => $validated['id_cabang'] ?? null,
            'id_pelanggan' => $idPelanggan,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if ($rfidUid) {
            RfidCard::create([
                'user_id' => $user->id,
                'uid' => $rfidUid,
                'status' => RfidCard::STATUS_AKTIF,
                'registered_at' => now(),
            ]);
        }

        return redirect()
            ->route('pengguna.index')
            ->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $pengguna): Response
    {
        $this->authorize('update', $pengguna);

        $pengguna->load(['cabang', 'pelanggan', 'rfidCards']);

        return Inertia::render('Pengguna/Edit', [
            'user' => UserResource::make($pengguna),
            'branches' => CabangResource::collection(Cabang::query()->orderBy('nama_cabang')->get()),
            'customers' => Pelanggan::query()->orderBy('nama_pelanggan')->get(['id_pelanggan', 'nama_pelanggan', 'no_hp', 'kota']),
            'roles' => User::ROLES,
        ]);
    }

    public function update(UpdateUserRequest $request, User $pengguna): RedirectResponse
    {
        $validated = $request->validated();
        $idPelanggan = $validated['role'] === User::ROLE_PELANGGAN ? ($validated['id_pelanggan'] ?? null) : null;

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'id_cabang' => $validated['id_cabang'] ?? null,
            'id_pelanggan' => $idPelanggan,
            'is_active' => $validated['is_active'] ?? $pengguna->is_active,
        ];

        if (array_key_exists('rfid_uid', $validated)) {
            $newUid = filled($validated['rfid_uid']) ? trim($validated['rfid_uid']) : null;
            $payload['rfid_uid'] = $newUid;

            if ($newUid) {
                $existingCard = $pengguna->rfidCards()->first();
                if ($existingCard) {
                    $existingCard->update([
                        'uid' => $newUid,
                        'status' => RfidCard::STATUS_AKTIF,
                    ]);
                } else {
                    RfidCard::create([
                        'user_id' => $pengguna->id,
                        'uid' => $newUid,
                        'status' => RfidCard::STATUS_AKTIF,
                        'registered_at' => now(),
                    ]);
                }
            } else {
                $pengguna->rfidCards()->delete();
            }
        }

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $pengguna->update($payload);

        return redirect()
            ->route('pengguna.index')
            ->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function destroyRfid(User $pengguna): RedirectResponse
    {
        $this->authorize('update', $pengguna);

        $pengguna->rfidCards()->delete();
        $pengguna->update(['rfid_uid' => null]);

        return redirect()
            ->back()
            ->with('success', "Kartu RFID untuk pengguna {$pengguna->name} berhasil dilepas.");
    }

    public function toggleCardStatus(User $pengguna, RfidCard $card): RedirectResponse
    {
        $this->authorize('update', $pengguna);

        if ($card->user_id !== $pengguna->id) {
            abort(404);
        }

        $newStatus = $card->isActive() ? RfidCard::STATUS_NONAKTIF : RfidCard::STATUS_AKTIF;
        $card->update(['status' => $newStatus]);

        return redirect()
            ->back()
            ->with('success', "Status kartu RFID ({$card->uid}) berhasil diubah menjadi ".ucfirst($newStatus).'.');
    }

    public function destroyCard(User $pengguna, RfidCard $card): RedirectResponse
    {
        $this->authorize('update', $pengguna);

        if ($card->user_id !== $pengguna->id) {
            abort(404);
        }

        if ($pengguna->rfid_uid === $card->uid) {
            $otherCard = $pengguna->rfidCards()->where('id', '!=', $card->id)->first();
            $pengguna->update(['rfid_uid' => $otherCard?->uid]);
        }

        $card->delete();

        return redirect()
            ->back()
            ->with('success', "Kartu RFID ({$card->uid}) berhasil dihapus dari akun {$pengguna->name}.");
    }

    public function storeCard(Request $request, User $pengguna): RedirectResponse
    {
        $this->authorize('update', $pengguna);

        $validated = $request->validate([
            'uid' => ['required', 'string', 'max:100', 'unique:rfid_cards,uid', 'unique:users,rfid_uid'],
        ]);

        $uid = trim($validated['uid']);

        $card = RfidCard::create([
            'user_id' => $pengguna->id,
            'uid' => $uid,
            'status' => RfidCard::STATUS_AKTIF,
            'registered_at' => now(),
        ]);

        if (empty($pengguna->rfid_uid)) {
            $pengguna->update(['rfid_uid' => $uid]);
        }

        return redirect()
            ->back()
            ->with('success', "Kartu RFID ({$uid}) berhasil didaftarkan untuk {$pengguna->name}.");
    }

    public function updateRole(UpdateUserRoleRequest $request, User $pengguna): RedirectResponse
    {
        $validated = $request->validated();

        $pengguna->update([
            'role' => $validated['role'],
            'id_cabang' => array_key_exists('id_cabang', $validated)
                ? $validated['id_cabang']
                : $pengguna->id_cabang,
            'is_active' => $validated['is_active'] ?? $pengguna->is_active,
        ]);

        return redirect()
            ->route('pengguna.index')
            ->with('success', "Role untuk {$pengguna->name} berhasil diperbarui menjadi ".ucfirst($pengguna->role).'.');
    }

    public function destroy(User $pengguna): RedirectResponse
    {
        $this->authorize('delete', $pengguna);

        try {
            $pengguna->delete();
        } catch (QueryException) {
            return redirect()
                ->route('pengguna.index')
                ->with('error', 'Pengguna tidak dapat dihapus karena masih terhubung dengan data operasional.');
        }

        return redirect()
            ->route('pengguna.index')
            ->with('success', 'Pengguna berhasil dihapus.');
    }
}
