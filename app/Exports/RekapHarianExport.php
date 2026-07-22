<?php

namespace App\Exports;

use App\Models\Retribution;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RekapHarianExport implements FromCollection, WithHeadings
{
    protected $tanggal;

    public function __construct($tanggal)
    {
        $this->tanggal = $tanggal;
    }

    public function collection()
    {
        return Retribution::query()
            ->join('markets', 'markets.id', '=', 'retributions.market_id')
            ->select(
                'markets.name as pasar',
                DB::raw('COUNT(retributions.id) as total_transaksi'),
                DB::raw('SUM(retributions.amount) as total_nominal')
            )
            ->whereDate(
                'retributions.retribution_date',
                $this->tanggal
            )
            ->groupBy(
                'markets.id',
                'markets.name'
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
