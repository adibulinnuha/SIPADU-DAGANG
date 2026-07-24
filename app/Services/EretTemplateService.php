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

            foreach ($columns as $field => $column) {
                $sheet->setCellValue(
                    $column . $excelRow,
                    $row[$field] ?? 0
                );
            }
        }

        return $spreadsheet;
    }
}