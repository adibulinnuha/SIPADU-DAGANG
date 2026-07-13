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

    public function show(User $petuga)
    {
        return redirect()->route('petugas.edit', $petuga);
    }

    public function edit(User $petuga)
    {
        return view('petugas.edit', compact('petuga'));
    }

    public function update(Request $request, User $petuga)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $petuga->id,
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $petuga->update($validated);

        return redirect()->route('petugas.index');
    }

    public function destroy(User $petuga)
    {
        $petuga->delete();

        return redirect()->route('petugas.index');
    }
}
