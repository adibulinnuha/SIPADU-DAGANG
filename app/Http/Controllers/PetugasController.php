<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PetugasController extends Controller
{
    public function index()
    {
        $petugas = User::where('role', UserRole::Petugas)
            ->orderBy('name')
            ->paginate(10);

        return view('petugas.index', compact('petugas'));
    }

    public function create()
    {
        return view('petugas.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => UserRole::Petugas,
        ]);

        return redirect()
            ->route('petugas.index')
            ->with('success', 'Petugas berhasil ditambahkan.');
    }

    public function show(User $petugas)
    {
        return redirect()->route('petugas.edit', $petugas);
    }

    public function edit(User $petugas)
    {
        return view('petugas.edit', compact('petugas'));
    }

    public function update(Request $request, User $petugas)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $petugas->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $petugas->update($data);

        return redirect()
            ->route('petugas.index')
            ->with('success', 'Data petugas berhasil diperbarui.');
    }

    public function destroy(User $petugas)
    {
        $petugas->delete();

        return redirect()
            ->route('petugas.index')
            ->with('success', 'Petugas berhasil dihapus.');
    }
}