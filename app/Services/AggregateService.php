<?php

namespace App\Services;

use App\Models\Retribution;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AggregateService
{
    public function getDailyRecap(string $date): Collection
    {
        $date = Carbon::parse($date)->toDateString();

        $rows = Retribution::query()
            ->with(['market', 'items'])
            ->whereDate('retribution_date', $date)
            ->get();

        return $rows
            ->groupBy(fn ($row) => $row->market?->name ?? 'Tanpa Pasar')
            ->map(function (Collection $items, string $marketName) {
                $summary = [
                    'market' => $marketName,
                    'kios' => 0,
                    'los' => 0,
                    'dasaran_terbuka' => 0,
                    'mck' => 0,
                    'kebersihan' => 0,
                    'listrik' => 0,
                    'total' => 0,
                ];

                foreach ($items as $retribution) {
                    if ($retribution->items->isNotEmpty()) {
                        foreach ($retribution->items as $item) {
                            $this->addItemToSummary($summary, $item);
                        }

                        continue;
                    }

                    $this->addItemToSummary($summary, $retribution);
                }

                return $summary;
            })
            ->values();
    }

    public function getGrandTotal(string $date): float
    {
        $date = Carbon::parse($date)->toDateString();

        $result = Retribution::query()
            ->selectRaw('COALESCE(SUM(COALESCE(item_totals.total, retributions.amount)), 0) as grand_total')
            ->leftJoin(
                DB::raw('(SELECT retribution_id, SUM(amount) as total FROM retribution_items GROUP BY retribution_id) as item_totals'),
                'item_totals.retribution_id',
                '=',
                'retributions.id'
            )
            ->whereDate('retribution_date', $date)
            ->first();

        return (float) ($result?->grand_total ?? 0);
    }

    public function getTransactionCount(string $date): int
    {
        return Retribution::query()
            ->whereDate('retribution_date', Carbon::parse($date)->toDateString())
            ->count();
    }

    public function getMonthlyTotal(string $date): float
    {
        $date = Carbon::parse($date);

        $result = Retribution::query()
            ->selectRaw('COALESCE(SUM(COALESCE(item_totals.total, retributions.amount)), 0) as monthly_total')
            ->leftJoin(
                DB::raw('(SELECT retribution_id, SUM(amount) as total FROM retribution_items GROUP BY retribution_id) as item_totals'),
                'item_totals.retribution_id',
                '=',
                'retributions.id'
            )
            ->whereMonth('retribution_date', $date->month)
            ->whereYear('retribution_date', $date->year)
            ->first();

        return (float) ($result?->monthly_total ?? 0);
    }

    public function getTopMarkets(): Collection
    {
        $rows = Retribution::query()
            ->selectRaw('COALESCE(m.name, ?) as market_name', ['Tanpa Pasar'])
            ->selectRaw('SUM(COALESCE(item_totals.total, retributions.amount)) as total_amount')
            ->leftJoin('markets as m', 'm.id', '=', 'retributions.market_id')
            ->leftJoin(
                DB::raw('(SELECT retribution_id, SUM(amount) as total FROM retribution_items GROUP BY retribution_id) as item_totals'),
                'item_totals.retribution_id',
                '=',
                'retributions.id'
            )
            ->groupBy('m.name')
            ->orderByDesc('total_amount')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'market' => $row->market_name,
                'total' => (float) $row->total_amount,
            ]);

        return $rows;
    }

    public function getDailyRevenueSeries(int $days = 7): Collection
    {
        $start = Carbon::today()->subDays($days - 1)->startOfDay();

        $rows = Retribution::query()
            ->selectRaw('DATE(retribution_date) as date')
            ->selectRaw('COALESCE(SUM(COALESCE(item_totals.total, retributions.amount)), 0) as total')
            ->leftJoin(
                DB::raw('(SELECT retribution_id, SUM(amount) as total FROM retribution_items GROUP BY retribution_id) as item_totals'),
                'item_totals.retribution_id',
                '=',
                'retributions.id'
            )
            ->where('retribution_date', '>=', $start)
            ->groupBy(DB::raw('DATE(retribution_date)'))
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'total' => (float) $row->total,
            ]);

        return $rows;
    }

    private function resolveRetributionTotal(Retribution $retribution): float
    {
        if ($retribution->items->isNotEmpty()) {
            return (float) $retribution->items->sum('amount');
        }

        return (float) $retribution->amount;
    }

    private function addItemToSummary(array &$summary, mixed $item): void
    {
        $jenis = strtolower((string) ($item->jenis_retribusi ?? ''));
        $amount = (float) ($item->amount ?? 0);

        switch ($jenis) {
            case 'kios':
                $summary['kios'] += $amount;
                break;

            case 'los':
                $summary['los'] += $amount;
                break;

            case 'dasaran terbuka':
            case 'dasaran_terbuka':
                $summary['dasaran_terbuka'] += $amount;
                break;

            case 'mck':
                $summary['mck'] += $amount;
                break;

            case 'kebersihan':
                $summary['kebersihan'] += $amount;
                break;

            case 'listrik':
                $summary['listrik'] += $amount;
                break;
        }

        $summary['total'] += $amount;
    }

    public function getMarketSummary(string $date): Collection
    {
        $date = Carbon::parse($date)->toDateString();

        $rows = Retribution::query()
            ->with(['market', 'items'])
            ->whereDate('retribution_date', $date)
            ->get();

        return $rows
            ->groupBy(fn (Retribution $retribution) => $retribution->market?->name ?? 'Tanpa Pasar')
            ->map(fn (Collection $items, string $marketName): array => [
                'market' => $marketName,
                'total_transaksi' => $items->count(),
                'total_nominal' => (float) $items->sum(
                    fn (Retribution $retribution) => $this->resolveRetributionTotal($retribution)
                ),
            ])
            ->sortBy('market')
            ->values();
    }
}
