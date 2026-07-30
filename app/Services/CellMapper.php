<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * CellMapper — Dynamic cell mapping for ERET template.
 *
 * Reads the workbook structure dynamically to find:
 *  - Market names and their row positions (grouped by Manual vs E-Retribusi)
 *  - Column headers (Kios, Los, DT, Kebersihan, Total)
 *  - Special labels (Listrik, MCK)
 *
 * No row numbers are hardcoded — everything is discovered by scanning the sheet.
 */
class CellMapper
{
    /** Column letters that are input values (safe to write) */
    private const VALUE_COLUMNS = ['B', 'C', 'D', 'E'];

    /** Column letters that contain formulas (protected) */
    private const FORMULA_COLUMNS = ['F'];

    private ?WorkbookEngine $engine = null;

    /**
     * Cache for scan results per sheet to avoid re-scanning.
     * @var array<string, array>
     */
    private array $scanCache = [];

    public function __construct(?WorkbookEngine $engine = null)
    {
        $this->engine = $engine;
    }

    /**
     * Inject WorkbookEngine after construction.
     */
    public function setEngine(WorkbookEngine $engine): self
    {
        $this->engine = $engine;

        return $this;
    }

    // -----------------------------------------------------------------------
    //  Market Scanning
    // -----------------------------------------------------------------------

    /**
     * Scan the current worksheet to find all market names with their row
     * positions and group membership (manual vs. eret).
     *
     * The scanning logic works as follows:
     * 1. Scan all rows looking for text in column A
     * 2. Detect section headers: "Pasar" indicates a column header row
     * 3. Detect group boundaries:
     *    - Rows before "Jumlah" (row 16) → Retribusi Manual
     *    - Rows between second "Pasar" header (row 22) and "Jumlah" (row 34) → E-Retribusi
     * 4. Skip: headers, "Jumlah" rows, "Harian", "Kebersihan", empty rows
     *
     * @return array<int, array{name: string, row: int, group: string}>
     */
    public function scanMarkets(): array
    {
        $this->assertEngine();
        $sheet = $this->engine->getSheet();
        $sheetName = $this->engine->getCurrentSheetName();

        // Check cache
        if (isset($this->scanCache[$sheetName]['markets'])) {
            return $this->scanCache[$sheetName]['markets'];
        }

        $highestRow = $this->engine->getHighestRow();
        $markets = [];
        $currentGroup = 'manual'; // default: first section is manual
        $headerCount = 0;         // count "Pasar" headers found
        $lastJumlahRow = 0;       // track where "Jumlah" subtotal rows are

        // Skip words that are NOT market names
        $skipWords = [
            'TANGGAL', 'PASAR', 'KIOS', 'LOS', 'DT', 'TOTAL', 'JUMLAH',
            'HARIAN', 'KEBERSIHAN', 'LISTRIK', 'MCK',
            'JUMLAH TOTAL SETORAN', 'JUMLAH TOTAL SETORAN KIOS',
            'JUMLAH TOTAL SETORAN LOS', 'JUMLAH TOTAL SETORAN DT',
            'JUMLAH TOTAL KEBERSIHAN', 'JUMLAH TOTAL E- HARIAN',
            'JUMLAH TOTAL  E- KEBERSIHAN',
            'REKAP HARIAN', 'REKAP',
        ];

        for ($row = 1; $row <= $highestRow; $row++) {
            $cellValue = $this->getCellValueTrimmed($sheet, "A{$row}");

            if ($cellValue === '' || $cellValue === null) {
                continue;
            }

            $upperValue = strtoupper($cellValue);

            // Detect "Pasar" headers — indicates start of a section
            if ($upperValue === 'PASAR') {
                $headerCount++;
                // First header (row 3) = Manual section
                // Second header (row 22) = E-Retribusi section
                $currentGroup = ($headerCount <= 1) ? 'manual' : 'eret';

                continue;
            }

            // Detect "Jumlah" rows — subtotal rows, skip
            if (str_starts_with($upperValue, 'JUMLAH')) {
                $lastJumlahRow = $row;

                continue;
            }

            // Skip other known non-market rows
            if (in_array($upperValue, $skipWords)) {
                continue;
            }

            // Skip rows that start with known keywords
            $skipPrefixes = ['HARIAN', 'KEBERSIHAN', 'LISTRIK', 'MCK'];
            $isSkip = false;
            foreach ($skipPrefixes as $prefix) {
                if (str_starts_with($upperValue, $prefix)) {
                    $isSkip = true;
                    break;
                }
            }
            if ($isSkip) {
                continue;
            }

            // Check if this row represents a group summary (like "Karimata", "Waru Indah")
            // These are after the second "Jumlah" row and contain formulas in columns B-E
            // We can detect them by checking if they have formula cells
            // For now, we include all rows that look like market names
            // The caller can filter based on whether the row has formula columns

            // This is a market row
            $markets[] = [
                'name' => trim($cellValue),
                'row' => $row,
                'group' => $currentGroup,
            ];
        }

        Log::info('CellMapper: Markets scanned', [
            'sheet' => $sheetName,
            'count' => count($markets),
            'markets' => $markets,
        ]);

        // Cache the result
        $this->scanCache[$sheetName]['markets'] = $markets;

        return $markets;
    }

    // -----------------------------------------------------------------------
    //  Column Header Scanning
    // -----------------------------------------------------------------------

    /**
     * Scan column headers to map field names to column letters.
     * Looks for rows containing "Pasar", "Kios", "Los", "DT", "Kebersihan", "Total".
     *
     * @return array<string, string>  e.g., ['Kios' => 'B', 'Los' => 'C', ...]
     */
    public function scanColumnHeaders(): array
    {
        $this->assertEngine();
        $sheet = $this->engine->getSheet();
        $sheetName = $this->engine->getCurrentSheetName();

        if (isset($this->scanCache[$sheetName]['column_headers'])) {
            return $this->scanCache[$sheetName]['column_headers'];
        }

        $highestRow = min(25, $this->engine->getHighestRow()); // headers are in first 25 rows
        $highestCol = $this->engine->getHighestColumn();
        $highestColIndex = Coordinate::columnIndexFromString($highestCol);

        $headers = [];
        $foundHeaderRow = false;

        for ($row = 1; $row <= $highestRow; $row++) {
            // Check if this is a header row (contains "Pasar" in column A)
            $cellA = $this->getCellValueTrimmed($sheet, "A{$row}");
            if (strtoupper($cellA) !== 'PASAR') {
                continue;
            }

            // This is a header row — scan columns B through highest
            for ($col = 2; $col <= $highestColIndex; $col++) {
                $colLetter = Coordinate::stringFromColumnIndex($col);
                $headerText = $this->getCellValueTrimmed($sheet, "{$colLetter}{$row}");

                if ($headerText !== '') {
                    $headers[trim($headerText)] = $colLetter;
                }
            }

            $foundHeaderRow = true;
            Log::info('CellMapper: Column headers found', [
                'row' => $row,
                'headers' => $headers,
            ]);

            // Only scan for first header row pattern
            break;
        }

        if (! $foundHeaderRow) {
            // Fallback to default mapping if no header found
            Log::warning('CellMapper: No header row found, using fallback mapping');

            $headers = [
                'Kios' => 'B',
                'Los' => 'C',
                'DT' => 'D',
                'Kebersihan' => 'E',
                'Total' => 'F',
            ];
        }

        $this->scanCache[$sheetName]['column_headers'] = $headers;

        return $headers;
    }

    // -----------------------------------------------------------------------
    //  Label Scanning (Listrik, MCK)
    // -----------------------------------------------------------------------

    /**
     * Find a cell address by searching for a text label in column A.
     * Used to find "Listrik" and "MCK" rows dynamically.
     *
     * @param  string      $label Label text to search for (case-insensitive)
     * @return string|null        Cell address like "B45", or null if not found
     */
    public function findLabelCell(string $label): ?string
    {
        $this->assertEngine();
        $sheet = $this->engine->getSheet();
        $highestRow = $this->engine->getHighestRow();

        $labelUpper = strtoupper(trim($label));

        for ($row = 1; $row <= $highestRow; $row++) {
            $cellValue = $this->getCellValueTrimmed($sheet, "A{$row}");

            if (strtoupper($cellValue) === $labelUpper) {
                Log::info('CellMapper: Label found', [
                    'label' => $label,
                    'row' => $row,
                    'cell_address' => "B{$row}",
                ]);

                // Value is in column B (same structure as other data)
                return "B{$row}";
            }
        }

        Log::warning('CellMapper: Label not found', [
            'label' => $label,
        ]);

        return null;
    }

    /**
     * Find Listrik cell address.
     */
    public function findListrikCell(): ?string
    {
        return $this->findLabelCell('Listrik');
    }

    /**
     * Find MCK cell address.
     */
    public function findMckCell(): ?string
    {
        return $this->findLabelCell('MCK');
    }

    // -----------------------------------------------------------------------
    //  Group-based Market Matching
    // -----------------------------------------------------------------------

    /**
     * Find all occurrences of a market name in the sheet, grouped by section.
     * A market can appear in both Manual and E-Retribusi sections.
     *
     * @param  string $marketName The market name to find
     * @return array<int, array{name: string, row: int, group: string}>
     */
    public function findMarketOccurrences(string $marketName): array
    {
        $markets = $this->scanMarkets();
        $marketUpper = strtoupper(trim($marketName));

        $occurrences = [];
        foreach ($markets as $market) {
            if (strtoupper(trim($market['name'])) === $marketUpper) {
                $occurrences[] = $market;
            }
        }

        if (empty($occurrences)) {
            Log::warning('CellMapper: Market not found in sheet', [
                'market' => $marketName,
                'available_markets' => collect($markets)->pluck('name')->unique()->values()->toArray(),
            ]);
        }

        return $occurrences;
    }

    // -----------------------------------------------------------------------
    //  Column Helpers
    // -----------------------------------------------------------------------

    /**
     * Get column letters that are safe for writing input values.
     *
     * @return string[]
     */
    public function getValueColumns(): array
    {
        return self::VALUE_COLUMNS;
    }

    /**
     * Get column letters that contain formulas and must not be written to.
     *
     * @return string[]
     */
    public function getFormulaColumns(): array
    {
        return self::FORMULA_COLUMNS;
    }

    /**
     * Map field name (kios, los, dasaran_terbuka, kebersihan, mck, listrik)
     * to the corresponding Excel column letter.
     *
     * @return array<string, string>  e.g., ['kios' => 'B', 'los' => 'C', ...]
     */
    public function getFieldToColumnMap(): array
    {
        $columnHeaders = $this->scanColumnHeaders();

        // Map known field names from AggregateService to column headers
        $fieldMap = [
            'kios' => $columnHeaders['Kios'] ?? 'B',
            'los' => $columnHeaders['Los'] ?? 'C',
            'dasaran_terbuka' => $columnHeaders['DT'] ?? 'D',
            'kebersihan' => $columnHeaders['Kebersihan'] ?? 'E',
            'dt' => $columnHeaders['DT'] ?? 'D',
        ];

        return $fieldMap;
    }

    // -----------------------------------------------------------------------
    //  Cache Management
    // -----------------------------------------------------------------------

    /**
     * Clear scan cache for a specific sheet or all sheets.
     */
    public function clearCache(?string $sheetName = null): void
    {
        if ($sheetName !== null) {
            unset($this->scanCache[$sheetName]);
        } else {
            $this->scanCache = [];
        }
    }

    // -----------------------------------------------------------------------
    //  Internal Helpers
    // -----------------------------------------------------------------------

    private function assertEngine(): void
    {
        if ($this->engine === null) {
            throw new \RuntimeException(
                'CellMapper: WorkbookEngine belum di-set. Panggil setEngine() terlebih dahulu.'
            );
        }
        if ($this->engine->getSheet() === null) {
            throw new \RuntimeException(
                'CellMapper: Belum ada worksheet yang dipilih. Panggil engine->selectSheet() terlebih dahulu.'
            );
        }
    }

    private function getCellValueTrimmed(Worksheet $sheet, string $cell): string
    {
        $val = $sheet->getCell($cell)->getCalculatedValue();

        if ($val === null) {
            return '';
        }

        return trim((string) $val);
    }
}

