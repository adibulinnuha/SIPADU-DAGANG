<?php

namespace App\Http\Controllers;

use App\Models\Bendel;
use App\Models\Market;
use App\Models\Retribution;
use App\Models\RetributionItem;
use App\Models\User;
use App\Services\AggregateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected AggregateService $aggregateService
    ) {}

    public function __invoke(Request $request): View
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

        // ══════════════════════════════════════════
        // 13. DASHBOARD ERET — SPREADSHEET
        // ══════════════════════════════════════════

        // --- Filter defaults ---
        $filters = [
            'tanggal' => $request->filled('tanggal') ? $request->input('tanggal') : $today->toDateString(),
            'market_id' => $request->filled('market_id') ? $request->integer('market_id') : null,
            'q' => $request->filled('q') ? trim($request->input('q')) : null,
        ];

        // --- Sorting whitelist (safe) ---
        $sortable = [
            'market_name',
            'petugas',
            'nomor_setor',
            'retribution_date',
            'total',
        ];

        $sort = $request->input('sort', 'retribution_date');
        if (! in_array($sort, $sortable, true)) {
            $sort = 'retribution_date';
        }

        $dir = strtolower($request->input('dir', 'desc'));
        if (! in_array($dir, ['asc', 'desc'], true)) {
            $dir = 'desc';
        }

        // --- Base query for ERET Harian (Eloquent) ---
        $eretQuery = Retribution::query()
            ->with(['market', 'recorder', 'items'])
            ->whereDate('retribution_date', $filters['tanggal']);

        if ($filters['market_id']) {
            $eretQuery->where('market_id', $filters['market_id']);
        }

        if ($filters['q']) {
            $eretQuery->where(function ($query) use ($filters) {
                $query->whereHas('market', fn ($q) => $q->where('name', 'like', "%{$filters['q']}%"))
                    ->orWhere('nomor_setor', 'like', "%{$filters['q']}%")
                    ->orWhereHas('recorder', fn ($q) => $q->where('name', 'like', "%{$filters['q']}%"));
            });
        }

        // --- Apply sorting ---
        switch ($sort) {
            case 'market_name':
                $eretQuery->orderBy(Market::select('name')->whereColumn('markets.id', 'retributions.market_id'), $dir);
                break;

            case 'petugas':
                $eretQuery->orderBy(User::select('name')->whereColumn('users.id', 'retributions.recorded_by'), $dir);
                break;

            case 'nomor_setor':
                $eretQuery->orderBy('nomor_setor', $dir);
                break;

            case 'total':
                $eretQuery->orderByDesc('amount');
                break;

            default:
                $eretQuery->orderBy('retribution_date', $dir);
                break;
        }

        // --- Pagination ---
        $perPage = (int) config('eret.dashboard_per_page', 15);
        $eretTransactions = $eretQuery->paginate($perPage)->withQueryString();

        // --- Map each transaction to spreadsheet columns ---
        $dashboardColumns = config('eret.dashboard_columns', []);
        $colKeys = array_keys($dashboardColumns);

$eretRows = $eretTransactions->getCollection()->map(function (Retribution $retribution) use ($colKeys, $dashboardColumns) {
            // Build lookup: jenis_retribusi -> amount (from items or fallback)
            $itemAmounts = [];
            $total = 0.0;

            if ($retribution->items->isNotEmpty()) {
                foreach ($retribution->items as $item) {
                    $key = strtolower(trim((string) $item->jenis_retribusi));
                    $itemAmounts[$key] = (float) $item->amount;
                    $total += (float) $item->amount;
                }
            } else {
                $key = strtolower(trim((string) $retribution->jenis_retribusi));
                $itemAmounts[$key] = (float) $retribution->amount;
                $total = (float) $retribution->amount;
            }

            $values = [];
            foreach ($colKeys as $colKey) {
                $sources = (array) ($dashboardColumns[$colKey]['sources'] ?? []);
                $value = 0.0;
                foreach ($sources as $source) {
                    $value += (float) ($itemAmounts[strtolower($source)] ?? 0);
                }
                $values[$colKey] = $value;
            }

            return [
                'id' => $retribution->id,
                'market' => $retribution->market?->name ?? '-',
                'petugas' => $retribution->recorder?->name ?? '-',
                'nomor_setor' => $retribution->nomor_setor ?? '-',
                'tanggal' => $retribution->retribution_date,
                'status' => $retribution->status,
                'values' => $values,
                'total' => $total,
            ];
        });

        $eretTransactions = $eretTransactions->setCollection($eretRows);

        // --- Footer Grand Total (all matching records, not just page) ---
        $grandTotalQuery = Retribution::query()
            ->whereDate('retribution_date', $filters['tanggal']);

        if ($filters['market_id']) {
            $grandTotalQuery->where('market_id', $filters['market_id']);
        }

        if ($filters['q']) {
            $grandTotalQuery->where(function ($query) use ($filters) {
                $query->whereHas('market', fn ($q) => $q->where('name', 'like', "%{$filters['q']}%"))
                    ->orWhere('nomor_setor', 'like', "%{$filters['q']}%")
                    ->orWhereHas('recorder', fn ($q) => $q->where('name', 'like', "%{$filters['q']}%"));
            });
        }

        // Build per-column grand totals for the footer (Excel-style).
        $grandTotals = array_fill_keys($colKeys, 0.0);
        $grandTotal = 0.0;

$grandTotalQuery->with('items')
            ->get()
            ->each(function (Retribution $retribution) use (&$grandTotals, &$grandTotal, $colKeys, $dashboardColumns) {
                $itemAmounts = [];

                if ($retribution->items->isNotEmpty()) {
                    foreach ($retribution->items as $item) {
                        $key = strtolower(trim((string) $item->jenis_retribusi));
                        $itemAmounts[$key] = (float) $item->amount;
                        $grandTotal += (float) $item->amount;
                    }
                } else {
                    $key = strtolower(trim((string) $retribution->jenis_retribusi));
                    $itemAmounts[$key] = (float) $retribution->amount;
                    $grandTotal += (float) $retribution->amount;
                }

                foreach ($colKeys as $colKey) {
                    $sources = (array) ($dashboardColumns[$colKey]['sources'] ?? []);
                    $value = 0.0;
                    foreach ($sources as $source) {
                        $value += (float) ($itemAmounts[strtolower($source)] ?? 0);
                    }
                    $grandTotals[$colKey] += $value;
                }
            });

        $grandTotal = (float) $grandTotal;

        // --- REKAP: per-market summary with status breakdown ---
        $rekapQuery = Retribution::query()
            ->with(['market', 'items'])
            ->whereDate('retribution_date', $filters['tanggal']);

        if ($filters['market_id']) {
            $rekapQuery->where('market_id', $filters['market_id']);
        }

        if ($filters['q']) {
            $rekapQuery->where(function ($query) use ($filters) {
                $query->whereHas('market', fn ($q) => $q->where('name', 'like', "%{$filters['q']}%"))
                    ->orWhere('nomor_setor', 'like', "%{$filters['q']}%")
                    ->orWhereHas('recorder', fn ($q) => $q->where('name', 'like', "%{$filters['q']}%"));
            });
        }

        $rekapRows = $rekapQuery->get()
            ->groupBy(fn ($item) => $item->market?->name ?? 'Tanpa Pasar')
            ->map(function ($items, $marketName) {
                $statusCounts = [
                    'draft' => 0,
                    'submitted' => 0,
                    'verified' => 0,
                    'approved' => 0,
                    'locked' => 0,
                ];

                $totalTransaksi = $items->count();
                $totalRetribusi = 0.0;

                foreach ($items as $item) {
                    $status = $item->status ?? 'draft';
                    if (isset($statusCounts[$status])) {
                        $statusCounts[$status]++;
                    }

                    $totalRetribusi += $item->items->isNotEmpty()
                        ? (float) $item->items->sum('amount')
                        : (float) $item->amount;
                }

                return [
                    'market' => $marketName,
                    'total_transaksi' => $totalTransaksi,
                    'total_retribusi' => $totalRetribusi,
                    'status' => $statusCounts,
                ];
            })
            ->sortBy('market')
            ->values();

        // --- Market list for filter dropdown ---
        $markets = Market::orderBy('name')->get();

        // --- Render View ---
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
            'systemInfo',
            // ERET spreadsheet
            'filters',
            'sort',
            'dir',
            'sortable',
            'dashboardColumns',
            'colKeys',
'eretTransactions',
            'grandTotal',
            'grandTotals',
            'rekapRows',
            'markets'
        ));
    }

}
