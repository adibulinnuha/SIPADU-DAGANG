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

        $todayRetributions = Retribution::with('market')
            ->whereDate('retribution_date', $today)
            ->get();

        $todayRetributionCount = $todayRetributions->count();

        $todayRetributionTotal = $todayRetributions->sum('amount');

        $submittedMarkets = $todayRetributions
            ->pluck('market')
            ->unique('id');

        $submittedMarketIds = $submittedMarkets
            ->pluck('id');


        $notSubmittedMarkets = Market::whereNotIn(
                'id',
                $submittedMarketIds
            )
            ->orderBy('name')
            ->get();


        return view('dashboard', [

            'marketCount' => $marketCount,

            'todayRetributionCount' => $todayRetributionCount,

            'todayRetributionTotal' => $todayRetributionTotal,

            'submittedMarkets' => $submittedMarkets,

            'notSubmittedMarkets' => $notSubmittedMarkets,

        ]);
    }
}