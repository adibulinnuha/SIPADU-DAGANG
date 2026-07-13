<?php

namespace App\Http\Controllers;

use App\Models\Market;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    public function index()
    {
        $markets = Market::all();

        return view('markets.index', compact('markets'));
    }

    public function create()
    {
        return view('markets.create');
    }

    public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required',
        'code' => 'required',
        'address' => 'required',
        'phone' => 'nullable',
        'is_active' => 'required',
    ]);

    Market::create($validated);

    return redirect()->route('markets.index');
}
    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
{
    $market = Market::findOrFail($id);

    return view('markets.edit', compact('market'));
}

    public function update(Request $request, string $id)
{
    $market = Market::findOrFail($id);

    $validated = $request->validate([
        'name' => 'required',
        'code' => 'required',
        'address' => 'required',
        'phone' => 'nullable',
        'is_active' => 'required',
    ]);

    $market->update($validated);

    return redirect()->route('markets.index');
}

    public function destroy(string $id)
{
    $market = Market::findOrFail($id);

    $market->delete();

    return redirect()->route('markets.index');
}
}