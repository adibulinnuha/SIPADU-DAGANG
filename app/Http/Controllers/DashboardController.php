<?php

namespace App\Http\Controllers;

use App\Models\Market;
use App\Models\Retribution;
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


        // Grafik Pendapatan 7 Hari

        $dailyRevenue = Retribution::selectRaw(
                'DATE(retribution_date) as date, SUM(amount) as total'
            )
            ->where(
                'retribution_date',
                '>=',
                now()->subDays(6)->startOfDay()
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();


        $chartLabels = [];
        $chartValues = [];


        foreach ($dailyRevenue as $item) {

            $chartLabels[] = date(
                'd M',
                strtotime($item->date)
            );

            $chartValues[] = (int) $item->total;

        }


        return view('dashboard', compact(
            'marketCount',
            'todayRetributionCount',
            'todayRetributionTotal',
            'monthRetributionTotal',
            'topMarkets',
            'notSubmittedMarkets',
            'recentTransactions',
            'chartLabels',
            'chartValues'
        ));
    }
}