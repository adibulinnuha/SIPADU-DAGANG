<?php

namespace App\Exports;

use App\Services\AggregateService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RekapHarianExport implements FromCollection, WithHeadings
{
    protected string $tanggal;

    protected AggregateService $aggregateService;

    public function __construct(string $tanggal)
    {
        $this->tanggal = $tanggal;
        $this->aggregateService = app(AggregateService::class);
    }

    public function collection(): Collection
    {
        return $this->aggregateService
            ->getMarketSummary($this->tanggal)
            ->map(function (array $row) {
                return (object) [
                    'pasar' => $row['market'],
                    'total_transaksi' => $row['total_transaksi'],
                    'total_nominal' => $row['total_nominal'],
                ];
            });
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
