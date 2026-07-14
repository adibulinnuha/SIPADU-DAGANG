<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\UserRole;
use Illuminate\Http\Request;

class PetugasController extends Controller
{
    public function index()
    {
        $petugas = User::where('role', UserRole::Petugas)
            ->orderBy('name')
            ->get();

        return view('petugas.index', compact('petugas'));
    }

    public function create()
    {
        return view('petugas.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $validated['role'] = UserRole::Petugas;

        User::create($validated);

        return redirect()->route('petugas.index');
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $petugas->id,
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $petugas->update($validated);

        return redirect()->route('petugas.index');
    }

    public function destroy(User $petugas)
    {
        $petugas->delete();

        return redirect()->route('petugas.index');
    }
}
