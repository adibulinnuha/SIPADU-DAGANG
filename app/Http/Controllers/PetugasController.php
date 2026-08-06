<?php

namespace App\Http\Controllers;

use App\Models\Market;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PetugasController extends Controller
{
    /**
     * Build the query of petugas records (role = Petugas, the existing Korwil
     * role) used across the master index and exports.
     */
    protected function baseQuery()
    {
        return User::query()
            ->with('market')
            ->where('role', UserRole::Petugas);
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q'));

        $petugas = $this->baseQuery()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('nip', 'like', "%{$search}%")
                        ->orWhere('jabatan', 'like', "%{$search}%")
                        ->orWhereHas('market', fn ($m) => $m->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('petugas.index', compact('petugas', 'search'));
    }

    public function create(): View
    {
        $markets = Market::orderBy('name')->get();

        return view('petugas.create', compact('markets'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateData($request, null);

        $petugas = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => UserRole::Petugas,
            'market_id' => $validated['market_id'] ?? null,
            'nip' => $validated['nip'] ?? null,
            'rank' => $validated['rank'] ?? null,
            'jabatan' => $validated['jabatan'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'is_juru_pungut' => $validated['is_juru_pungut'] ?? false,
        ]);

        return redirect()
            ->route('petugas.index')
            ->with('success', "Petugas {$petugas->name} berhasil ditambahkan.");
    }

    public function show(User $petugas): RedirectResponse
    {
        return redirect()->route('petugas.edit', $petugas);
    }

    public function edit(User $petugas): View
    {
        $markets = Market::orderBy('name')->get();

        return view('petugas.edit', compact('petugas', 'markets'));
    }

    public function update(Request $request, User $petugas): RedirectResponse
    {
        $validated = $this->validateData($request, $petugas);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'market_id' => $validated['market_id'] ?? null,
            'nip' => $validated['nip'] ?? null,
            'rank' => $validated['rank'] ?? null,
            'jabatan' => $validated['jabatan'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'is_juru_pungut' => $validated['is_juru_pungut'] ?? false,
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $petugas->update($data);

        return redirect()
            ->route('petugas.index')
            ->with('success', "Data petugas {$petugas->name} berhasil diperbarui.");
    }

    public function destroy(User $petugas): RedirectResponse
    {
        $name = $petugas->name;
        $petugas->delete();

        return redirect()
            ->route('petugas.index')
            ->with('success', "Petugas {$name} berhasil dihapus.");
    }

    /**
     * Lightweight JSON endpoint for the ERET dropdown.
     *
     * Returns only the active Juru Pungut assigned to the given market,
     * ordered by name. Admin/Supervisor users are intentionally excluded from
     * this list — they can still create ERET records via the client record
     * manually. Response shape: [{ id, nama, nip }]
     */
    public function activePetugas(Market $market): JsonResponse
    {
        $petugas = User::activeJuruPungut($market->id)
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'nama' => $u->name,
                'nip' => $u->nip,
            ])
            ->values();

        return response()->json($petugas);
    }

    /**
     * Shared validation for create/update. Optionally ignores an existing
     * email when an id is provided.
     */
    protected function validateData(Request $request, ?User $petugas): array
    {
        $uniqueEmail = 'unique:users,email';
        if ($petugas) {
            $uniqueEmail = 'unique:users,email,'.$petugas->id;
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', $uniqueEmail],
            'password' => $petugas
                ? ['nullable', 'string', 'min:8', 'confirmed']
                : ['required', 'string', 'min:8', 'confirmed'],
            'nip' => ['nullable', 'string', 'max:50'],
            'rank' => ['nullable', 'string', 'max:255'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'market_id' => ['nullable', 'integer', 'exists:markets,id'],
            'phone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'is_juru_pungut' => ['sometimes', 'boolean'],
        ]);
    }
}
