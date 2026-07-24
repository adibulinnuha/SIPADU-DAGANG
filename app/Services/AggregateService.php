<?php

namespace App\Services;

use App\Models\Retribution;
use Carbon\Carbon;
use Illuminate\Support\Collection;

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

        $rows = Retribution::query()
            ->with('items')
            ->whereDate('retribution_date', $date)
            ->get();

        return (float) $rows->sum(fn (Retribution $retribution): float => $this->resolveRetributionTotal($retribution));
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

        $rows = Retribution::query()
            ->with('items')
            ->whereMonth('retribution_date', $date->month)
            ->whereYear('retribution_date', $date->year)
            ->get();

        return (float) $rows->sum(fn (Retribution $retribution): float => $this->resolveRetributionTotal($retribution));
    }

    public function getTopMarkets(): Collection
    {
        return Retribution::query()
            ->with('market', 'items')
            ->get()
            ->groupBy(fn ($retribution) => $retribution->market?->name ?? 'Tanpa Pasar')
            ->map(fn (Collection $retributions, string $marketName) => [
                'market' => $marketName,
                'total' => (float) $retributions->sum(fn (Retribution $retribution): float => $this->resolveRetributionTotal($retribution)),
            ])
            ->sortByDesc('total')
            ->take(5)
            ->values();
    }

    public function getDailyRevenueSeries(int $days = 7): Collection
    {
        $start = Carbon::today()->subDays($days - 1)->startOfDay();

        $rows = Retribution::query()
            ->with('items')
            ->where('retribution_date', '>=', $start)
            ->get();

        return $rows
            ->groupBy(fn ($retribution) => Carbon::parse($retribution->retribution_date)->toDateString())
            ->map(fn (Collection $items, string $date) => [
                'date' => $date,
                'total' => (float) $items->sum(fn (Retribution $retribution): float => $this->resolveRetributionTotal($retribution)),
            ])
            ->sortBy('date')
            ->values();
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