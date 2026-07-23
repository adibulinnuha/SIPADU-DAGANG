<?php

namespace App\Exports;

use App\Models\Retribution;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RekapHarianExport implements FromCollection, WithHeadings
{
    protected string $tanggal;

    public function __construct(string $tanggal)
    {
        $this->tanggal = $tanggal;
    }

    public function collection()
    {
        return Retribution::query()
            ->join('markets', 'markets.id', '=', 'retributions.market_id')
            ->leftJoin('retribution_items', 'retribution_items.retribution_id', '=', 'retributions.id')
            ->whereDate('retributions.retribution_date', $this->tanggal)
            ->groupBy('markets.id', 'markets.name')
            ->select(
                'markets.name as pasar',
                DB::raw('COUNT(DISTINCT retributions.id) as total_transaksi'),
                DB::raw('COALESCE(SUM(retribution_items.amount), SUM(retributions.amount)) as total_nominal')
            )
            ->orderBy('markets.name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Pasar',
            'Jumlah Transaksi',
            'Total Nominal',
        ];
    }
}