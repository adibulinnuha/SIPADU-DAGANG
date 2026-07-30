<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * WorkbookEngine — Low-level workbook operations for the ERET template.
 *
 * Responsibilities:
 *  - Load an existing Excel template workbook
 *  - Select worksheets by name or by date header
 *  - Read/write cell values with FORMULA PROTECTION
 *  - Never overwrite formulas, merged cells, styles, or structure
 *  - Save workbook to file
 */
class WorkbookEngine
{
    private ?Spreadsheet $spreadsheet = null;
    private ?Worksheet $sheet = null;
    private string $templatePath = '';
    private array $mergedCells = [];

    /**
     * Load a workbook from the ERET template file.
     *
     * @throws \RuntimeException When template file does not exist
     */
    public function load(string $templatePath): self
    {
        if (! file_exists($templatePath)) {
            throw new \RuntimeException(
                'Template ERET tidak ditemukan: '.$templatePath
            );
        }

        $this->templatePath = $templatePath;
        $this->spreadsheet = IOFactory::load($templatePath);
        $this->sheet = null;

        Log::info('WorkbookEngine: Workbook loaded', [
            'path' => $templatePath,
            'sheets' => $this->getSheetNames(),
        ]);

        return $this;
    }

    /**
     * Select a worksheet by its exact name.
     *
     * @throws \RuntimeException When sheet name is not found
     */
    public function selectSheet(string $sheetName): self
    {
        $this->assertLoaded();

        $sheet = $this->spreadsheet->getSheetByName($sheetName);

        if ($sheet === null) {
            throw new \RuntimeException(
                "Worksheet '{$sheetName}' tidak ditemukan."
            );
        }

        $this->sheet = $sheet;
        $this->mergedCells = $sheet->getMergeCells();
        $this->spreadsheet->setActiveSheetIndex(
            $this->spreadsheet->getIndex($sheet)
        );

        Log::info('WorkbookEngine: Sheet selected', [
            'sheet' => $sheetName,
            'highest_row' => $sheet->getHighestRow(),
            'highest_col' => $sheet->getHighestColumn(),
            'merged_cells' => count($this->mergedCells),
        ]);

        return $this;
    }

    /**
     * Find a worksheet by searching the header text (Row 1) for a given date.
     * This allows dynamic matching: "01 JULI 2026" -> sheet "01 Juli".
     *
     * For combined-date sheets (e.g., "03,04,05 Juli"), the header only contains
     * the first day number. As a fallback, the sheet name itself is also checked
     * for the day number — allowing days 4 and 5 to match the combined sheet.
     *
     * @param  string      $date  Date string (Y-m-d format)
     * @return string|null        The matched sheet name, or null if not found
     */
    public function findSheetByDateHeader(string $date): ?string
    {
        $this->assertLoaded();

        $timestamp = strtotime($date);
        if ($timestamp === false) {
            Log::warning('WorkbookEngine: Invalid date format for header search', [
                'date' => $date,
            ]);
            return null;
        }

        $day = date('d', $timestamp);
        $monthNum = date('m', $timestamp);
        $monthFull = strtoupper(date('F', $timestamp));
        $year = date('Y', $timestamp);

        $indonesianMonths = [
            '01' => 'JANUARI', '02' => 'FEBRUARI', '03' => 'MARET',
            '04' => 'APRIL',   '05' => 'MEI',      '06' => 'JUNI',
            '07' => 'JULI',    '08' => 'AGUSTUS',  '09' => 'SEPTEMBER',
            '10' => 'OKTOBER', '11' => 'NOVEMBER', '12' => 'DESEMBER',
        ];

        $monthName = $indonesianMonths[$monthNum] ?? $monthFull;

        // Build regex patterns for precise matching
        // Use word boundaries to prevent "02" matching inside "2026"
        $dayRegex = '/(?<!\d)' . preg_quote($day, '/') . '(?!\d)/';
        $monthRegex = '/' . preg_quote($monthName, '/') . '/';
        $yearRegex = '/' . preg_quote($year, '/') . '/';

        foreach ($this->spreadsheet->getSheetNames() as $sheetName) {
            $sheet = $this->spreadsheet->getSheetByName($sheetName);
            if ($sheet === null) {
                continue;
            }

            $headerValue = $sheet->getCell('A1')->getCalculatedValue();
            if ($headerValue === null) {
                continue;
            }

            $headerUpper = strtoupper(trim((string) $headerValue));
            $sheetNameUpper = strtoupper(trim($sheetName));

            // Primary: header contains standalone day + month + year
            if (preg_match($dayRegex, $headerUpper)
                && preg_match($monthRegex, $headerUpper)
                && preg_match($yearRegex, $headerUpper)) {
                Log::info('WorkbookEngine: Sheet found by date header', [
                    'date' => $date,
                    'sheet' => $sheetName,
                    'header' => $headerValue,
                ]);
                return $sheetName;
            }

            // Fallback: sheet NAME contains the day number (combined-date sheets)
            if (preg_match($yearRegex, $headerUpper)
                && preg_match('/JULI/', $headerUpper)
                && preg_match($dayRegex, $sheetNameUpper)) {
                Log::info('WorkbookEngine: Sheet found by date header (name fallback)', [
                    'date' => $date,
                    'sheet' => $sheetName,
                    'header' => $headerValue,
                ]);
                return $sheetName;
            }
        }

        Log::warning('WorkbookEngine: No sheet found for date header', [
            'date' => $date,
            'search_day' => $day,
            'search_month' => $monthName,
            'search_year' => $year,
        ]);
        return null;
    }

    /**
     * Read the raw value of a cell (not calculated, to detect formulas).
     */
    public function getCellRawValue(string $cell): mixed
    {
        $this->assertSheetSelected();
        return $this->sheet->getCell($cell)->getValue();
    }

    /**
     * Read the calculated value of a cell.
     */
    public function getCellValue(string $cell): mixed
    {
        $this->assertSheetSelected();
        return $this->sheet->getCell($cell)->getCalculatedValue();
    }

    /**
     * Check whether a cell contains an Excel formula.
     */
    public function isFormulaCell(string $cell): bool
    {
        $this->assertSheetSelected();
        return $this->sheet->getCell($cell)->isFormula();
    }

    /**
     * Check whether a cell is part of a merged range.
     */
    public function isMergedCell(string $cell): bool
    {
        $this->assertSheetSelected();

        [$targetCol, $targetRow] = Coordinate::coordinateFromString($cell);
        $targetColIndex = Coordinate::columnIndexFromString($targetCol);
        $targetRow = (int) $targetRow;

        foreach ($this->mergedCells as $mergedRange) {
            if (str_contains($mergedRange, ':')) {
                [$start, $end] = explode(':', $mergedRange);
            } else {
                if ($mergedRange === $cell) {
                    return true;
                }
                continue;
            }

            [$startCol, $startRow] = Coordinate::coordinateFromString($start);
            [$endCol, $endRow] = Coordinate::coordinateFromString($end);

            $startColIndex = Coordinate::columnIndexFromString($startCol);
            $endColIndex = Coordinate::columnIndexFromString($endCol);
            $startRow = (int) $startRow;
            $endRow = (int) $endRow;

            if ($targetColIndex >= $startColIndex
                && $targetColIndex <= $endColIndex
                && $targetRow >= $startRow
                && $targetRow <= $endRow) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if a cell is protected (formula, merged, or subtotal row).
     *
     * KNOWN SUBTOTAL ROWS (protected in ALL columns):
     *  16, 20, 21, 34, 38, 39, 42, 44, 48
     */
    public function isProtectedCell(string $cell): bool
    {
        $this->assertSheetSelected();

        $column = Coordinate::coordinateFromString($cell)[0];
        $row = (int) Coordinate::coordinateFromString($cell)[1];

        // Column F (Total) always has formulas
        if (strtoupper($column) === 'F') {
            return true;
        }

        // Known subtotal rows are protected in ALL columns
        $subtotalRows = [16, 20, 21, 34, 38, 39, 42, 44, 48];
        if (in_array($row, $subtotalRows, true)) {
            return true;
        }

        // Check if it's a formula
        if ($this->isFormulaCell($cell)) {
            return true;
        }

        // Check if it's inside a merged range
        if ($this->isMergedCell($cell)) {
            return true;
        }

        return false;
    }

    /**
     * Set a cell value ONLY if it is safe to do so (not a formula, not merged).
     */
    public function setCellValue(string $cell, mixed $value): self
    {
        $this->assertSheetSelected();

        if ($this->isProtectedCell($cell)) {
            $protectedReason = '';
            if ($this->isFormulaCell($cell)) {
                $protectedReason = 'cell berisi formula';
            } elseif ($this->isMergedCell($cell)) {
                $protectedReason = 'cell berada dalam merged range';
            } else {
                $protectedReason = 'cell dilindungi (kolom F / subtotal)';
            }

            Log::warning('WorkbookEngine: Skipping protected cell', [
                'cell' => $cell,
                'reason' => $protectedReason,
                'value' => $value,
            ]);
            return $this;
        }

        $this->sheet->setCellValue($cell, $value);

        Log::info('WorkbookEngine: Cell written', [
            'cell' => $cell,
            'value' => $value,
        ]);
        return $this;
    }

    /**
     * Get all worksheet names in the workbook.
     */
    public function getSheetNames(): array
    {
        $this->assertLoaded();
        return $this->spreadsheet->getSheetNames();
    }

    /**
     * Save the workbook to a file path.
     */
    public function save(string $outputPath, string $writerType = 'Xlsx'): void
    {
        $this->assertLoaded();

        $writer = IOFactory::createWriter($this->spreadsheet, $writerType);

        $outputDir = dirname($outputPath);
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $writer->save($outputPath);

        Log::info('WorkbookEngine: Workbook saved', [
            'path' => $outputPath,
            'writer' => $writerType,
        ]);
    }

    /**
     * Get highest row number in the current worksheet.
     */
    public function getHighestRow(): int
    {
        $this->assertSheetSelected();
        return $this->sheet->getHighestRow();
    }

    /**
     * Get highest column letter in the current worksheet.
     */
    public function getHighestColumn(): string
    {
        $this->assertSheetSelected();
        return $this->sheet->getHighestColumn();
    }

    /**
     * Get the underlying Spreadsheet object.
     */
    public function getSpreadsheet(): Spreadsheet
    {
        $this->assertLoaded();
        return $this->spreadsheet;
    }

    /**
     * Get the current Worksheet object.
     */
    public function getSheet(): Worksheet
    {
        $this->assertSheetSelected();
        return $this->sheet;
    }

    /**
     * Get merged cells ranges for the current sheet.
     */
    public function getMergedCells(): array
    {
        $this->assertSheetSelected();
        return $this->mergedCells;
    }

    /**
     * Get the current sheet name.
     */
    public function getCurrentSheetName(): string
    {
        $this->assertSheetSelected();
        return $this->sheet->getTitle();
    }

    /**
     * Disconnect worksheets to free memory.
     */
    public function disconnect(): void
    {
        if ($this->spreadsheet !== null) {
            $this->spreadsheet->disconnectWorksheets();
        }
        $this->spreadsheet = null;
        $this->sheet = null;
        $this->mergedCells = [];
    }

    // -----------------------------------------------------------------------
    //  Internal assertions
    // -----------------------------------------------------------------------

    private function assertLoaded(): void
    {
        if ($this->spreadsheet === null) {
            throw new \RuntimeException(
                'WorkbookEngine: Belum ada workbook yang di-load. Panggil load() terlebih dahulu.'
            );
        }
    }

    private function assertSheetSelected(): void
    {
        $this->assertLoaded();
        if ($this->sheet === null) {
            throw new \RuntimeException(
                'WorkbookEngine: Belum ada worksheet yang dipilih. Panggil selectSheet() terlebih dahulu.'
            );
        }
    }
}
