<?php

namespace App\Exports;

use App\Models\Retribution;
use PhpOffice\PhpSpreadsheet\IOFactory;

class RetributionTemplateExport
{
    public function generate()
    {
        $template = storage_path(
            'app/templates/ERET JULI.xltx'
        );

        $spreadsheet = IOFactory::load($template);

        $sheet = $spreadsheet->getActiveSheet();

        $date = now()->format('d F Y');

        // tanggal
        $sheet->setCellValue(
            'A1',
            'Tanggal '.$date
        );

        $data = Retribution::with('market')
            ->whereDate(
                'retribution_date',
                today()
            )
            ->get();

        /*
        Mapping sementara:

        Excel:
        Kios = B
        Los = C
        DT = D
        Kebersihan = E
        Total = F

        */

        $rowMapping = [

            'REJOMULYO' => 5,
            'TAMBAK LOROK' => 7,
            'WARU INDAH' => 9,
            'REJOMULYO IB' => 11,
            'DARGO' => 13,
            'BUBAKAN' => 15,

            'KARIMATA 1' => 24,
            'KARIMATA 2' => 25,
            'DARGO 2' => 27,
            'LANGGAR' => 29,
            'WARU INDAH 1' => 31,
            'WARU INDAH 2' => 32,

        ];

        foreach ($data as $item) {

            $market = strtoupper(
                $item->market->name
            );

            if (! isset($rowMapping[$market])) {
                continue;
            }

            $row = $rowMapping[$market];

            switch ($item->jenis_retribusi) {

                case 'Kios':
                    $col = 'B';
                    break;

                case 'Los':
                    $col = 'C';
                    break;

                case 'Dasaran Terbuka':
                    $col = 'D';
                    break;

                case 'Kebersihan':
                    $col = 'E';
                    break;

                default:
                    continue 2;

            }

            $old = $sheet->getCell(
                $col.$row
            )->getValue();

            $old = is_numeric($old)
                ? $old
                : 0;

            $sheet->setCellValue(
                $col.$row,
                $old + $item->amount
            );

            // Total kolom F

            $total = 0;

            foreach (['B', 'C', 'D', 'E'] as $c) {
                $value = $sheet->getCell(
                    $c.$row
                )->getValue();

                $total += is_numeric($value)
                    ? $value
                    : 0;
            }

            $sheet->setCellValue(
                'F'.$row,
                $total
            );

        }

        return $spreadsheet;

    }
}
