<?php

namespace App\Http\Controllers;

use App\Models\Market;
use App\Models\Retribution;
use Illuminate\Http\Request;

class RetributionController extends Controller
{
    public function index(Request $request)
    {
        $markets = Market::orderBy('name')->get();

        $query = Retribution::with(['market', 'recorder'])
            ->orderByDesc('retribution_date');

        if ($request->filled('market_id')) {
            $query->where('market_id', $request->market_id);
        }

        if ($request->filled('date_start')) {
            $query->whereDate('retribution_date', '>=', $request->date_start);
        }

        if ($request->filled('date_end')) {
            $query->whereDate('retribution_date', '<=', $request->date_end);
        }

        $totalTransactions = (clone $query)->count();
        $totalAmount = (clone $query)->sum('amount');
        $totalMarkets = (clone $query)
            ->distinct('market_id')
            ->count('market_id');

        $retributions = $query->paginate(15)->withQueryString();

        return view('retributions.index', compact(
            'markets',
            'retributions',
            'totalTransactions',
            'totalAmount',
            'totalMarkets'
        ));
    }

    public function create()
    {
        $markets = Market::orderBy('name')->get();

        return view('retributions.create', compact('markets'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'market_id' => 'required|exists:markets,id',
            'jenis_retribusi' => 'required|string|max:100',
            'retribution_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:100',
            'notes' => 'nullable|string',
        ]);

        Retribution::create([
            'market_id' => $validated['market_id'],
            'recorded_by' => auth()->id(),
            'jenis_retribusi' => $validated['jenis_retribusi'],
            'retribution_date' => $validated['retribution_date'],
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('retributions.index')
            ->with('success', 'Retribusi berhasil ditambahkan.');
    }

    public function show(Retribution $retribution)
    {
        return redirect()->route('retributions.edit', $retribution);
    }

    public function edit(Retribution $retribution)
    {
        $markets = Market::orderBy('name')->get();

        return view('retributions.edit', compact(
            'retribution',
            'markets'
        ));
    }

    public function update(Request $request, Retribution $retribution)
    {
        $validated = $request->validate([
            'market_id' => 'required|exists:markets,id',
            'jenis_retribusi' => 'required|string|max:100',
            'retribution_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $retribution->update($validated);

        return redirect()
            ->route('retributions.index')
            ->with('success', 'Retribusi berhasil diperbarui.');
    }

    public function destroy(Retribution $retribution)
    {
        $retribution->delete();

        return redirect()
            ->route('retributions.index')
            ->with('success', 'Retribusi berhasil dihapus.');
    }
}