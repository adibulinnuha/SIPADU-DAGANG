<?php

namespace App\Http\Controllers;

use App\Models\Market;
use App\Models\Retribution;
use App\Models\Trader;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RetributionController extends Controller
{
    public function index(Request $request)
    {
        $markets = Market::orderBy('name')->get();

        $query = Retribution::with(['market', 'trader', 'recorder'])
            ->orderByDesc('retribution_date');

        if ($request->filled('search')) {
            $query->whereHas('trader', function ($builder) use ($request) {
                $builder->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('stall_number', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('market_id')) {
            $query->where('market_id', $request->market_id);
        }

        if ($request->filled('date_start')) {
            $query->whereDate('retribution_date', '>=', $request->date_start);
        }

        if ($request->filled('date_end')) {
            $query->whereDate('retribution_date', '<=', $request->date_end);
        }

        $retributions = $query->paginate(15)->withQueryString();

        return view('retributions.index', compact('markets', 'retributions'));
    }

    public function create()
    {
        $markets = Market::orderBy('name')->get();
        $traders = Trader::orderBy('name')->get();

        return view('retributions.create', compact('markets', 'traders'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'market_id' => 'required|exists:markets,id',
            'trader_id' => 'required|exists:traders,id',
            'retribution_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        Retribution::create([
            'market_id' => $validated['market_id'],
            'trader_id' => $validated['trader_id'],
            'recorded_by' => auth()->id(),
            'retribution_date' => $validated['retribution_date'],
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('retributions.index')
            ->with('success', 'Retribusi berhasil ditambahkan.');
    }

    public function show(Retribution $retribution)
    {
        return redirect()->route('retributions.edit', $retribution);
    }

    public function edit(Retribution $retribution)
    {
        $markets = Market::orderBy('name')->get();
        $traders = Trader::orderBy('name')->get();

        return view('retributions.edit', compact('retribution', 'markets', 'traders'));
    }

    public function update(Request $request, Retribution $retribution)
    {
        $validated = $request->validate([
            'market_id' => 'required|exists:markets,id',
            'trader_id' => 'required|exists:traders,id',
            'retribution_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $retribution->update($validated);

        return redirect()->route('retributions.index')
            ->with('success', 'Retribusi berhasil diperbarui.');
    }

    public function destroy(Retribution $retribution)
    {
        $retribution->delete();

        return redirect()->route('retributions.index')
            ->with('success', 'Retribusi berhasil dihapus.');
    }
}
