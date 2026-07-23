<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class EretTemplateService
{
    public function __construct(
        protected EretService $eretService
    ) {}

    public function generate(string $sheetName, string $date): Spreadsheet
    {
        $template = storage_path('app/templates/ERET JULI.xltx');

        $spreadsheet = IOFactory::load($template);

        $sheet = $spreadsheet->getSheetByName($sheetName);

        if ($sheet === null) {
            $sheet = $spreadsheet->getActiveSheet();
        }

        $spreadsheet->setActiveSheetIndex(
            $spreadsheet->getIndex($sheet)
        );

        $rows = $this->eretService->getDailyRecap($date);

        foreach ($rows as $row) {
            $sheet->setCellValue('B24', $row['kios']);
            $sheet->setCellValue('C24', $row['los']);
            $sheet->setCellValue('D24', $row['dasaran_terbuka']);
            $sheet->setCellValue('E24', $row['kebersihan']);

            break;
        }

        return $spreadsheet;
    }
}