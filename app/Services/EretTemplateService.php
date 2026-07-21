<?php

namespace App\Services;

use App\Models\Retribution;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EretTemplateService
{
    /**
     * Mapping nama pasar => baris pada template ERET.
     */
    protected array $marketRows = [
        'REJOMULYO'    => 5,
        'TAMBAK LOROK' => 7,
        'WARU INDAH'   => 9,
        'REJOMULYO IB' => 11,
        'DARGO'        => 13,
        'BUBAKAN'      => 15,

        'KARIMATA 1'   => 24,
        'KARIMATA 2'   => 25,
        'LANGGAR'      => 29,
        'WARU INDAH 1' => 31,
        'WARU INDAH 2' => 32,
    ];

    protected string $template;

    public function __construct()
    {
        $this->template = storage_path('app/templates/ERET JULI.xltx');
    }

    protected function getRetributionSummary(string $date): array
    {
        return Retribution::query()
            ->with('market')
            ->whereDate('retribution_date', $date)
            ->get()
            ->groupBy(function ($item) {
                return strtoupper(trim($item->market->name));
            })
            ->map(function ($items) {
                return [
                    'Kios' => $items->where('jenis_retribusi', 'Kios')->sum('amount'),
                    'Los' => $items->where('jenis_retribusi', 'Los')->sum('amount'),
                    'DT' => $items->where('jenis_retribusi', 'Dasaran Terbuka')->sum('amount'),
                    'Kebersihan' => $items->where('jenis_retribusi', 'Kebersihan')->sum('amount'),
                ];
            })
            ->toArray();
    }

    public function generate(string $sheetName, string $date): Spreadsheet
    {
        $spreadsheet = IOFactory::load($this->template);

        /** @var Worksheet|null $sheet */
        $sheet = $spreadsheet->getSheetByName($sheetName);

        if (! $sheet) {
            throw new \Exception("Sheet {$sheetName} tidak ditemukan.");
        }

        $summary = $this->getRetributionSummary($date);

        foreach ($this->marketRows as $market => $row) {

            $data = $summary[$market] ?? [
                'Kios' => 0,
                'Los' => 0,
                'DT' => 0,
                'Kebersihan' => 0,
            ];

            $sheet->setCellValue("B{$row}", $data['Kios']);
            $sheet->setCellValue("C{$row}", $data['Los']);
            $sheet->setCellValue("D{$row}", $data['DT']);
            $sheet->setCellValue("E{$row}", $data['Kebersihan']);
        }

        return $spreadsheet;
    }
}