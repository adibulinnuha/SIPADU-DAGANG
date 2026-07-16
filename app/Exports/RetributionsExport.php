<?php

namespace App\Exports;

use App\Models\Retribution;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RetributionsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithColumnFormatting
{
    public function __construct(
        protected $marketId = null,
        protected ?string $dateStart = null,
        protected ?string $dateEnd = null,
    ) {
    }

    public function query(): Builder
    {
        $query = Retribution::query()
            ->with(['market', 'recorder'])
            ->orderBy('retribution_date');

        if ($this->marketId) {
            $query->where('market_id', $this->marketId);
        }

        if ($this->dateStart) {
            $query->whereDate('retribution_date', '>=', $this->dateStart);
        }

        if ($this->dateEnd) {
            $query->whereDate('retribution_date', '<=', $this->dateEnd);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Pasar',
            'Jenis Retribusi',
            'Nominal',
            'Metode Pembayaran',
            'Petugas',
            'Catatan',
        ];
    }

    public function map($row): array
    {
        return [
            $row->retribution_date ? $row->retribution_date->format('d-m-Y') : '-',
            $row->market?->name,
            $row->jenis_retribusi,
            (float) $row->amount,
            $row->payment_method,
            $row->recorder?->name,
            $row->notes,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->freezePane('A2');

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => '"Rp" #,##0',
        ];
    }
}
