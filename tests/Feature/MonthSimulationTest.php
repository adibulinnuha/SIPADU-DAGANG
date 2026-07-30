<?php

namespace Tests\Feature;

use App\Models\Market;
use App\Models\Retribution;
use App\Models\User;
use App\Services\EretEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * MonthSimulationTest — Generate workbook for all available July sheets (Task 5)
 *
 * Template ERET JULI.xltx memiliki 15 worksheet untuk 21 hari pertama Juli.
 * Beberapa hari digabung dalam satu sheet, dan ada sheet dengan nama berbeda.
 *
 * Template adalah source of truth — test mengikuti struktur template.
 */
class MonthSimulationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Mapping tanggal → sheet name yang diharapkan dari template.
     *
     * Template memiliki 15 sheet. Beberapa sheet mencakup beberapa hari.
     * Beberapa sheet memiliki nama tidak konsisten (header JULI, sheet Juni).
     */
    private function getExpectedSheetForDate(string $date): ?string
    {
        $day = (int) substr($date, 8, 2);

        $mapping = [
            1  => '01 Juli',
            2  => '02 Juli',
            3  => '03,04,05 Juli',
            4  => '03,04,05 Juli',
            5  => '03,04,05 Juli',
            6  => '06 Juni',      // Sheet name says "Juni", header says "JULI"
            7  => '07 Juni',      // Sheet name says "Juni", header says "JULI"
            8  => '08 Juni',      // Sheet name says "Juni", header says "JULI"
            9  => '09 Juli',
            10 => '10,11,12 Juli',
            11 => '10,11,12 Juli',
            12 => '10,11,12 Juli',
            13 => '13 Juli',
            14 => '14 Juli',
            15 => '15 Juli',
            16 => '16 Juli',
            17 => '17,18,19 jULI',
            18 => '17,18,19 jULI',
            19 => '17,18,19 jULI',
            20 => '20 Juli',
            21 => '21 Jul',       // Abbreviated name
        ];

        return $mapping[$day] ?? null;
    }

    /**
     * Hari yang TIDAK memiliki sheet di template.
     */
    private function hasSheetForDate(string $date): bool
    {
        return $this->getExpectedSheetForDate($date) !== null;
    }

    /**
     * Generate workbook for ALL available sheets (21 hari pertama Juli).
     */
    public function test_generate_all_available_days_of_july()
    {
        $user = User::factory()->create();

        // Create test markets
        $markets = [
            Market::factory()->create(['name' => 'Rejomulyo']),
            Market::factory()->create(['name' => 'Karimata 1']),
            Market::factory()->create(['name' => 'Dargo']),
        ];

        // Create retribution data for all days 1-21
        for ($day = 1; $day <= 21; $day++) {
            $date = '2026-07-'.str_pad((string) $day, 2, '0', STR_PAD_LEFT);

            foreach ($markets as $market) {
                $retribution = Retribution::factory()->create([
                    'market_id' => $market->id,
                    'retribution_date' => $date,
                ]);
                $retribution->items()->createMany([
                    ['jenis_retribusi' => 'kios', 'amount' => 100000],
                    ['jenis_retribusi' => 'los', 'amount' => 50000],
                    ['jenis_retribusi' => 'dasaran_terbuka', 'amount' => 25000],
                    ['jenis_retribusi' => 'kebersihan', 'amount' => 15000],
                ]);
            }
        }

        $successCount = 0;
        $failures = [];
        $tempFiles = [];

        // Generate workbook for each day that HAS a sheet
        for ($day = 1; $day <= 31; $day++) {
            $date = '2026-07-'.str_pad((string) $day, 2, '0', STR_PAD_LEFT);

            if (! $this->hasSheetForDate($date)) {
                // Days 22-31 don't have sheets in template — skip
                continue;
            }

            try {
                $engine = app(EretEngine::class);
                $spreadsheet = $engine->generate($date);
                $this->assertNotNull($spreadsheet, "Spreadsheet for {$date} should not be null");

                // Save to temp file for verification
                $tempFile = tempnam(sys_get_temp_dir(), "ERET_MONTH_{$day}_").'.xlsx';
                $engine->save($tempFile);
                $tempFiles[] = $tempFile;

                // Reopen and verify sheet exists
                $spreadsheet2 = IOFactory::load($tempFile);
                $expectedSheetName = $this->getExpectedSheetForDate($date);
                $sheet = $spreadsheet2->getSheetByName($expectedSheetName);

                $this->assertNotNull(
                    $sheet,
                    "Sheet '{$expectedSheetName}' harus ada untuk tanggal {$date}"
                );
                $this->assertNotNull(
                    $sheet->getCell('A1')->getCalculatedValue(),
                    "Header A1 harus ada untuk sheet {$expectedSheetName}"
                );

                $engine->disconnect();
                $spreadsheet2->disconnectWorksheets();
                $successCount++;

            } catch (\Exception $e) {
                $failures[] = [
                    'date' => $date,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ];
            }
        }

        // Clean up temp files
        foreach ($tempFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        // Report results — should succeed for all 21 days that have sheets
        $this->assertEquals(21, $successCount,
            "Should successfully generate for all 21 days that have sheets. Failures: ".json_encode($failures));
        $this->assertEmpty($failures, "No failures should occur. Got: ".json_encode($failures));
    }

    /**
     * Stress test: generate several July dates in sequence
     * (tests that each generate creates a fresh engine).
     */
    public function test_sequential_generation_completes_successfully()
    {
        $user = User::factory()->create();
        $market = Market::factory()->create(['name' => 'Rejomulyo']);

        // Create data for sample days that have sheets
        $sampleDays = ['2026-07-01', '2026-07-07', '2026-07-14', '2026-07-21'];
        foreach ($sampleDays as $date) {
            $retribution = Retribution::factory()->create([
                'market_id' => $market->id,
                'retribution_date' => $date,
            ]);
            $retribution->items()->createMany([
                ['jenis_retribusi' => 'kios', 'amount' => 100000],
            ]);
        }

        foreach ($sampleDays as $date) {
            $engine = app(EretEngine::class);
            $spreadsheet = $engine->generate($date);
            $this->assertNotNull($spreadsheet);

            // Verify the correct sheet was selected
            $expectedSheetName = $this->getExpectedSheetForDate($date);
            $wbEngine = app(\App\Services\WorkbookEngine::class);
            // Just check that the spreadsheet has the expected sheet
            $foundSheet = $spreadsheet->getSheetByName($expectedSheetName);
            $this->assertNotNull($foundSheet, "Sheet '{$expectedSheetName}' should exist for {$date}");

            $engine->disconnect();
        }

        // If we got here without errors, test passes
        $this->assertTrue(true, 'Sequential generation completed without issues');
    }

    /**
     * Verify sheet names match the dates (Business Rule #8).
     * Template adalah source of truth — test mengikuti struktur template.
     */
    public function test_sheet_names_follow_date_headers()
    {
        $engine = app(\App\Services\WorkbookEngine::class);
        $templatePath = config('eret.template');
        $engine->load($templatePath);

        // Test all sheet name mappings
        $testCases = [
            '01 Juli'         => '2026-07-01',
            '02 Juli'         => '2026-07-02',
            '03,04,05 Juli'   => '2026-07-03',
            '06 Juni'         => '2026-07-06',   // Sheet "Juni" → header says "06 JULI 2026"
            '07 Juni'         => '2026-07-07',
            '08 Juni'         => '2026-07-08',
            '09 Juli'         => '2026-07-09',
            '10,11,12 Juli'   => '2026-07-10',
            '13 Juli'         => '2026-07-13',
            '14 Juli'         => '2026-07-14',
            '15 Juli'         => '2026-07-15',
            '16 Juli'         => '2026-07-16',
            '17,18,19 jULI'   => '2026-07-17',
            '20 Juli'         => '2026-07-20',
            '21 Jul'          => '2026-07-21',
        ];

        foreach ($testCases as $sheetName => $expectedDate) {
            $foundSheet = $engine->findSheetByDateHeader($expectedDate);
            $this->assertEquals(
                $sheetName,
                $foundSheet,
                "Sheet '{$sheetName}' harus ditemukan untuk tanggal {$expectedDate}"
            );
        }

        $engine->disconnect();
    }
}

