<?php

namespace App\Http\Controllers;

use App\Models\Bendel;
use App\Models\Market;
use App\Models\Retribution;
use App\Models\RetributionItem;
use App\Models\User;
use App\Services\AggregateService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected AggregateService $aggregateService
    ) {}

    public function __invoke(): View
    {
        $today = today();

        // ──────────────────────────────────────────
        // 1. Core KPIs
        // ──────────────────────────────────────────

        $marketCount = Market::count();

        $todayRetributionCount = Retribution::whereDate('retribution_date', $today)->count();

        $todayRetributionTotal = $this->aggregateService->getGrandTotal($today->toDateString());

        $monthRetributionTotal = $this->aggregateService->getMonthlyTotal($today->toDateString());

        // ──────────────────────────────────────────
        // 2. Workflow Stats — single grouped query
        // ──────────────────────────────────────────

        $workflowStats = Retribution::query()
            ->selectRaw("COALESCE(status, 'draft') as status")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $defaultStatuses = ['draft' => 0, 'submitted' => 0, 'verified' => 0, 'approved' => 0, 'locked' => 0];
        $workflowStats = array_merge($defaultStatuses, $workflowStats);

        $pendingVerificationCount = $workflowStats['submitted'];
        $approvedVerificationCount = $workflowStats['verified'] + $workflowStats['approved'];

        // ──────────────────────────────────────────
        // 3. Bendel & User counts
        // ──────────────────────────────────────────

        $totalBendelCount = Bendel::count();

        $petugasCount = User::where('role', 'petugas')->count();

        // ──────────────────────────────────────────
        // 4. Progress Today
        // ──────────────────────────────────────────

        $activeMarketCount = Market::where('is_active', true)->count();

        $marketWithInputToday = Retribution::whereDate('retribution_date', $today)
            ->distinct('market_id')
            ->count('market_id');

        $progressToday = $activeMarketCount > 0
            ? round(($marketWithInputToday / $activeMarketCount) * 100)
            : 0;

        // ──────────────────────────────────────────
        // 5. Top Markets (reuse existing service)
        // ──────────────────────────────────────────

        $topMarkets = $this->aggregateService->getTopMarkets();

        // ──────────────────────────────────────────
        // 6. Markets not yet input today
        // ──────────────────────────────────────────

        $notSubmittedMarkets = Market::whereNotIn(
            'id',
            Retribution::whereDate('retribution_date', $today)->pluck('market_id')
        )->get();

        // ──────────────────────────────────────────
        // 7. Recent Transactions (enhanced with relations)
        // ──────────────────────────────────────────

        $recentTransactions = Retribution::with(['market', 'recorder'])
            ->latest()
            ->limit(10)
            ->get();

        // ──────────────────────────────────────────
        // 8. Daily Revenue Series (line chart)
        // ──────────────────────────────────────────

        $dailyRevenue = $this->aggregateService->getDailyRevenueSeries(7);

        $chartLabels = [];
        $chartValues = [];

        foreach ($dailyRevenue as $item) {
            $chartLabels[] = date('d M', strtotime($item['date']));
            $chartValues[] = (int) $item['total'];
        }

        // ──────────────────────────────────────────
        // 9. Bar Chart — Revenue by Market (top 8 this month)
        // ──────────────────────────────────────────

        $revenueByMarket = Retribution::query()
            ->selectRaw('COALESCE(m.name, \'Tanpa Pasar\') as market_name')
            ->selectRaw('SUM(COALESCE(item_totals.total, retributions.amount)) as total')
            ->leftJoin('markets as m', 'm.id', '=', 'retributions.market_id')
            ->leftJoin(
                DB::raw('(SELECT retribution_id, SUM(amount) as total FROM retribution_items GROUP BY retribution_id) as item_totals'),
                'item_totals.retribution_id',
                '=',
                'retributions.id'
            )
            ->whereMonth('retribution_date', $today->month)
            ->whereYear('retribution_date', $today->year)
            ->groupBy('m.name')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $barChartLabels = $revenueByMarket->pluck('market_name')->toArray();
        $barChartValues = $revenueByMarket->pluck('total')->map(fn ($v) => (float) $v)->toArray();

        // ──────────────────────────────────────────
        // 10. Donut Chart — Distribution by jenis_retribusi
        // ──────────────────────────────────────────

        $distribution = RetributionItem::query()
            ->selectRaw('jenis_retribusi')
            ->selectRaw('SUM(amount) as total')
            ->groupBy('jenis_retribusi')
            ->orderByDesc('total')
            ->get();

        $donutLabels = $distribution->pluck('jenis_retribusi')->toArray();
        $donutValues = $distribution->pluck('total')->map(fn ($v) => (float) $v)->toArray();

        // Fallback to main retributions table if no items data
        if (empty($donutLabels)) {
            $fallbackDist = Retribution::query()
                ->selectRaw('jenis_retribusi')
                ->selectRaw('SUM(amount) as total')
                ->groupBy('jenis_retribusi')
                ->orderByDesc('total')
                ->get();

            $donutLabels = $fallbackDist->pluck('jenis_retribusi')->toArray();
            $donutValues = $fallbackDist->pluck('total')->map(fn ($v) => (float) $v)->toArray();
        }

        // ──────────────────────────────────────────
        // 11. Market Summary — per-market target vs realization
        //     Optimized: 2 queries total instead of 2N queries
        // ──────────────────────────────────────────

        $monthlyTotals = $this->aggregateService
            ->getMarketsMonthlyTotals($today->toDateString())
            ->keyBy('market_id');

        $lastMonthTotals = $this->aggregateService
            ->getMarketsLastMonthTotals($today->toDateString())
            ->keyBy('market_id');

        $marketSummaries = Market::where('is_active', true)
            ->get()
            ->map(function ($market) use ($monthlyTotals, $lastMonthTotals) {
                $realization = (float) ($monthlyTotals[$market->id]['total'] ?? 0);
                $lastTotal = (float) ($lastMonthTotals[$market->id]['total'] ?? 0);
                $target = $lastTotal > 0 ? $lastTotal : ($realization > 0 ? round($realization * 1.1, 2) : 1000000);
                $percentage = $target > 0 ? round(($realization / $target) * 100, 1) : 0;

                return [
                    'market' => $market->name,
                    'target' => $target,
                    'realization' => $realization,
                    'percentage' => min($percentage, 100),
                ];
            });

        // ──────────────────────────────────────────
        // 12. System Information
        // ──────────────────────────────────────────

        $systemInfo = [
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'database' => config('database.default'),
            'last_backup' => __('Tidak tersedia'),
            'environment' => app()->environment(),
        ];

        // ──────────────────────────────────────────
        // Render View
        // ──────────────────────────────────────────

        return view('dashboard', compact(
            'marketCount',
            'todayRetributionCount',
            'todayRetributionTotal',
            'monthRetributionTotal',
            'pendingVerificationCount',
            'approvedVerificationCount',
            'totalBendelCount',
            'petugasCount',
            'progressToday',
            'topMarkets',
            'notSubmittedMarkets',
            'recentTransactions',
            'chartLabels',
            'chartValues',
            'barChartLabels',
            'barChartValues',
            'donutLabels',
            'donutValues',
            'workflowStats',
            'marketSummaries',
            'systemInfo'
        ));
    }

}
