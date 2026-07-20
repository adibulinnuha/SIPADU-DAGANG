<?php

namespace App\Http\Controllers;

use App\Models\Bendel;
use App\Models\Market;
use App\Models\Retribution;
use App\Models\Verification;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = today();

        $marketCount = Market::count();

        $todayRetributionCount = Retribution::whereDate(
            'retribution_date',
            $today
        )->count();

        $todayRetributionTotal = Retribution::whereDate(
            'retribution_date',
            $today
        )->sum('amount');


        $monthRetributionTotal = Retribution::where(
            'retribution_date',
            '>=',
            now()->startOfMonth()
        )->sum('amount');


        $topMarkets = Retribution::selectRaw(
                'market_id, SUM(amount) as total'
            )
            ->groupBy('market_id')
            ->orderByDesc('total')
            ->limit(5)
            ->with('market')
            ->get();


        $notSubmittedMarkets = Market::whereNotIn(
            'id',
            Retribution::whereDate(
                'retribution_date',
                $today
            )->pluck('market_id')
        )->get();


        $recentTransactions = Retribution::latest()
            ->with('market')
            ->limit(10)
            ->get();


        return view('dashboard', compact(
            'marketCount',
            'todayRetributionCount',
            'todayRetributionTotal',
            'monthRetributionTotal',
            'topMarkets',
            'notSubmittedMarkets',
            'recentTransactions'
        ));
    }
}