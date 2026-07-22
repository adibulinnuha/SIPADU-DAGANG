<?php

namespace App\Services;

use App\Models\Retribution;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EretService
{
    public function getDailyRecap(string $date): Collection
    {
        $date = Carbon::parse($date)->toDateString();

        $rows = Retribution::query()
            ->with('market')
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

                foreach ($items as $item) {
                    $jenis = strtolower($item->jenis_retribusi);
                    $amount = (float) $item->amount;

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

                return $summary;
            })
            ->values();
    }

    public function getGrandTotal(string $date): float
    {
        return (float) Retribution::query()
            ->whereDate('retribution_date', $date)
            ->sum('amount');
    }

    public function getTransactionCount(string $date): int
    {
        return Retribution::query()
            ->whereDate('retribution_date', $date)
            ->count();
    }
}
