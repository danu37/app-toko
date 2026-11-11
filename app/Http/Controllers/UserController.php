<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pengguna\StoreUserRequest;
use App\Http\Requests\Pengguna\UpdateUserRequest;
use App\Http\Requests\Pengguna\UpdateUserRoleRequest;
use App\Http\Resources\CabangResource;
use App\Http\Resources\UserResource;
use App\Models\Cabang;
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
            ->with('cabang')
            ->when($search, function ($query) use ($search) {
                $like = '%'.trim($search).'%';
                $query->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like);
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
                'COALESCE(SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END), 0) as aktif_count',
                [User::ROLE_ADMIN, User::ROLE_KASIR]
            )
            ->first();

        $roleStats = [
            'total' => (int) ($statsRow->total ?? 0),
            'admin' => (int) ($statsRow->admin_count ?? 0),
            'kasir' => (int) ($statsRow->kasir_count ?? 0),
            'aktif' => (int) ($statsRow->aktif_count ?? 0),
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
            'roles' => User::ROLES,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'email_verified_at' => now(),
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'id_cabang' => $validated['id_cabang'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()
            ->route('pengguna.index')
            ->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $pengguna): Response
    {
        $this->authorize('update', $pengguna);

        $pengguna->load('cabang');

        return Inertia::render('Pengguna/Edit', [
            'user' => UserResource::make($pengguna),
            'branches' => CabangResource::collection(Cabang::query()->orderBy('nama_cabang')->get()),
            'roles' => User::ROLES,
        ]);
    }

    public function update(UpdateUserRequest $request, User $pengguna): RedirectResponse
    {
        $validated = $request->validated();

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'id_cabang' => $validated['id_cabang'] ?? null,
            'is_active' => $validated['is_active'] ?? $pengguna->is_active,
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $pengguna->update($payload);

        return redirect()
            ->route('pengguna.index')
            ->with('success', 'Data pengguna berhasil diperbarui.');
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
