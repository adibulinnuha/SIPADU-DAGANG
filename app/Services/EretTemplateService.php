<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class EretTemplateService
{
    public function __construct(
        protected AggregateService $aggregateService
    ) {}

    public function generate(string $sheetName, string $date): Spreadsheet
    {
        $template = config('eret.template');

        $spreadsheet = IOFactory::load($template);

        $sheet = $spreadsheet->getSheetByName($sheetName);

        if ($sheet === null) {
            $sheet = $spreadsheet->getActiveSheet();
        }

        $spreadsheet->setActiveSheetIndex(
            $spreadsheet->getIndex($sheet)
        );

        $marketRows = config('eret.market_rows');
        $columns = config('eret.columns');

        $rows = $this->aggregateService->getDailyRecap($date);

        foreach ($rows as $row) {
            $excelRow = $marketRows[$row['market']] ?? null;

            if ($excelRow === null) {
                continue;
            }

            $sheet->setCellValue($columns['kios'].$excelRow, $row['kios']);
            $sheet->setCellValue($columns['los'].$excelRow, $row['los']);
            $sheet->setCellValue($columns['dasaran_terbuka'].$excelRow, $row['dasaran_terbuka']);
            $sheet->setCellValue($columns['kebersihan'].$excelRow, $row['kebersihan']);
            $sheet->setCellValue($columns['mck'].$excelRow, $row['mck']);
            $sheet->setCellValue($columns['listrik'].$excelRow, $row['listrik']);
            $sheet->setCellValue($columns['total'].$excelRow, $row['total']);
        }

        return $spreadsheet;
    }
}
