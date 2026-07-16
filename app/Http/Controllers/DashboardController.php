<?php

namespace App\Http\Controllers;

use App\Models\Market;
use App\Models\Retribution;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = today();
        $startMonth = now()->startOfMonth();

        $marketCount = Market::count();

        $todayRetributions = Retribution::with('market')
            ->whereDate('retribution_date', $today)
            ->get();

        $todayRetributionCount = $todayRetributions->count();
        $todayRetributionTotal = $todayRetributions->sum('amount');

        $monthRetributionTotal = Retribution::whereBetween(
            'retribution_date',
            [$startMonth, now()]
        )->sum('amount');

        $submittedMarkets = $todayRetributions
            ->pluck('market')
            ->unique('id');

        $submittedMarketIds = $submittedMarkets->pluck('id');

        $notSubmittedMarkets = Market::whereNotIn('id', $submittedMarketIds)
            ->orderBy('name')
            ->get();

        $recentTransactions = Retribution::with('market')
            ->latest('created_at')
            ->take(10)
            ->get();

        $topMarkets = Retribution::select(
                'market_id',
                DB::raw('SUM(amount) as total')
            )
            ->with('market')
            ->groupBy('market_id')
            ->orderByDesc('total')
            ->take(5)
            ->get();

        return view('dashboard', [
            'marketCount' => $marketCount,
            'todayRetributionCount' => $todayRetributionCount,
            'todayRetributionTotal' => $todayRetributionTotal,
            'monthRetributionTotal' => $monthRetributionTotal,
            'submittedMarkets' => $submittedMarkets,
            'notSubmittedMarkets' => $notSubmittedMarkets,
            'recentTransactions' => $recentTransactions,
            'topMarkets' => $topMarkets,
        ]);
    }
}