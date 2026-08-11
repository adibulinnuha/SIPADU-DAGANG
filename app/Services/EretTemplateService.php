<?php

namespace App\Services;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;

class EretTemplateService
{
    protected string $template;

    protected array $marketRows = [
        'PASAR KARIMATA' => 6,
        'PASAR JOHAR' => 7,
        'PASAR BANGUNHARJO' => 8,
        'PASAR PEDURUNGAN' => 9,
    ];

    public function __construct()
    {
        $this->template = storage_path('app/templates/ERET JULI.xltx');

        if (! file_exists($this->template)) {
            throw new \Exception('Template ERET tidak ditemukan: '.$this->template);
        }
    }

    public function generate(Collection $retributions, string $date): string
    {
        $spreadsheet = IOFactory::load($this->template);
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue(
            'B2',
            'REKAP ERET TANGGAL '.date('d/m/Y', strtotime($date))
        );

        foreach ($retributions as $retribution) {
            $marketName = strtoupper($retribution->market->name ?? '');

            if (! isset($this->marketRows[$marketName])) {
                continue;
            }

           $items = $retribution->items ?? collect();
$row = $this->marketRows[$marketName];

if ($items->isEmpty()) {
    // fallback: gunakan total utama jika detail item belum ada
    $kios = (float) $retribution->amount;
    $los = 0;
    $dasaran = 0;
    $mck = 0;
    $sampah = 0;
    $listrik = 0;
    $total = (float) $retribution->amount;
} else {
    $kios = $items->where('type', 'kios')->sum('amount');
    $los = $items->where('type', 'los')->sum('amount');
    $dasaran = $items->where('type', 'dasaran')->sum('amount');
    $mck = $items->where('type', 'mck')->sum('amount');
    $sampah = $items->where('type', 'sampah')->sum('amount');
    $listrik = $items->where('type', 'listrik')->sum('amount');

    $total = $kios + $los + $dasaran + $mck + $sampah + $listrik;
}

            $sheet->setCellValue('C'.$row, $kios);
            $sheet->setCellValue('D'.$row, $los);
            $sheet->setCellValue('E'.$row, $dasaran);
            $sheet->setCellValue('F'.$row, $mck);
            $sheet->setCellValue('G'.$row, $sampah);
            $sheet->setCellValue('H'.$row, $listrik);
            $sheet->setCellValue('I'.$row, $total);
        }

        $filename = 'ERET-'.date('Ymd', strtotime($date)).'.xlsx';
        $output = storage_path('app/temp/'.$filename);

        if (! is_dir(dirname($output))) {
            mkdir(dirname($output), 0777, true);
        }

        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($output);

        return $output;
    }
}