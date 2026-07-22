<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;

class EretTemplateService
{
    public function __construct(
        protected EretService $eretService
    ) {}

    public function generate(string $sheetName, string $date): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($sheetName);

        // Header
        $sheet->fromArray([
            [
                'Pasar',
                'Kios',
                'Los',
                'Dasaran Terbuka',
                'MCK',
                'Kebersihan',
                'Listrik',
                'Total',
            ],
        ]);

        // Data dari service
        $rows = $this->eretService->getDailyRecap($date);

        foreach ($rows as $row) {
            $sheet->fromArray([
                [
                    $row['market'],
                    $row['kios'],
                    $row['los'],
                    $row['dasaran_terbuka'],
                    $row['mck'],
                    $row['kebersihan'],
                    $row['listrik'],
                    $row['total'],
                ],
            ], null, 'A'.($sheet->getHighestRow() + 1));
        }

        return $spreadsheet;
    }
}
