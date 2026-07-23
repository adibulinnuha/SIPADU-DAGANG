<?php

namespace App\Http\Controllers;

use App\Models\Market;
use App\Models\Retribution;
use App\Services\AggregateService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected AggregateService $aggregateService
    ) {}

    public function __invoke(): View
    {
        $today = today();

        $marketCount = Market::count();

        $todayRetributionCount = Retribution::whereDate(
            'retribution_date',
            $today
        )->count();

        $todayRetributionTotal = $this->aggregateService->getGrandTotal($today);

        $monthRetributionTotal = $this->aggregateService->getMonthlyTotal($today);

        $topMarkets = $this->aggregateService->getTopMarkets();

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

        $dailyRevenue = $this->aggregateService->getDailyRevenueSeries(7);

        $chartLabels = [];
        $chartValues = [];

        foreach ($dailyRevenue as $item) {
            $chartLabels[] = date(
                'd M',
                strtotime($item['date'])
            );

            $chartValues[] = (int) $item['total'];
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
