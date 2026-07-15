<?php

namespace App\Http\Controllers;

use App\Models\Bendel;
use App\Models\Market;
use Illuminate\Http\Request;

class BendelController extends Controller
{
    public function index()
    {
        $bendels = Bendel::with('market')
            ->latest()
            ->paginate(10);

        return view('bendels.index', compact('bendels'));
    }


    public function create()
    {
        $markets = Market::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('bendels.create', compact('markets'));
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'market_id' => 'required',
            'nomor_bendel' => 'required',
            'tanggal' => 'required|date',
            'periode' => 'required',
            'status' => 'required',
            'keterangan' => 'nullable',
        ]);

        Bendel::create($validated);

        return redirect()
            ->route('bendels.index')
            ->with('success', 'Bendel berhasil disimpan.');
    }


    public function show(Bendel $bendel)
    {
        return view('bendels.show', compact('bendel'));
    }


    public function edit(Bendel $bendel)
    {
        $markets = Market::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('bendels.edit', compact('bendel', 'markets'));
    }


    public function update(Request $request, Bendel $bendel)
    {
        $validated = $request->validate([
            'market_id' => 'required',
            'nomor_bendel' => 'required',
            'tanggal' => 'required|date',
            'periode' => 'required',
            'status' => 'required',
            'keterangan' => 'nullable',
        ]);

        $bendel->update($validated);

        return redirect()
            ->route('bendels.index')
            ->with('success', 'Bendel berhasil diperbarui.');
    }


    public function destroy(Bendel $bendel)
    {
        $bendel->delete();

        return redirect()
            ->route('bendels.index')
            ->with('success', 'Bendel berhasil dihapus.');
    }
}