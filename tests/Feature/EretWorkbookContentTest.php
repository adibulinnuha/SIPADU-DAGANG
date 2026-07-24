<?php

namespace Tests\Feature;

use App\Models\Market;
use App\Models\Retribution;
use App\Models\User;
use App\Services\AggregateService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class EretWorkbookContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_workbook_content_matches_aggregate_service(): void
    {
        // Use a date whose translatedFormat('d M') matches a worksheet in the template.
        // The template has a sheet named "21 Jul" which matches en locale formatting.
        $testDate = '2026-07-21';

        // 1. Create a User
        $user = User::factory()->create();

        // 2. Create a Market named "Karimata"
        $market = Market::factory()->create([
            'name' => 'Karimata',
        ]);

        // 3. Create Retribution records for the test date
        $retribution = Retribution::factory()->create([
            'market_id' => $market->id,
            'retribution_date' => $testDate,
        ]);

        // Attach RetributionItems to exercise the items() path in AggregateService
        $retribution->items()->createMany([
            ['jenis_retribusi' => 'kios',            'amount' => 50000],
            ['jenis_retribusi' => 'los',             'amount' => 30000],
            ['jenis_retribusi' => 'dasaran_terbuka', 'amount' => 20000],
            ['jenis_retribusi' => 'mck',             'amount' => 10000],
            ['jenis_retribusi' => 'kebersihan',      'amount' => 5000],
            ['jenis_retribusi' => 'listrik',         'amount' => 15000],
        ]);

        // 4. Login and call the export route
        $response = $this
            ->actingAs($user)
            ->get(route('retributions.export-template', ['date' => $testDate]));

        $response->assertOk();

        // 5. Save response to a temporary xlsx file
        $tempPath = tempnam(sys_get_temp_dir(), 'ERET_WB_') . '.xlsx';
        file_put_contents($tempPath, $response->streamedContent());

        // 6. Open the workbook using PhpSpreadsheet
        $spreadsheet = IOFactory::load($tempPath);

        // 7. Verify expected worksheet exists
        $sheetName = Carbon::parse($testDate)->translatedFormat('d M');
        $worksheet = $spreadsheet->getSheetByName($sheetName);

        $this->assertNotNull(
            $worksheet,
            "Worksheet '{$sheetName}' should exist in the workbook."
        );

        // 8. Get expected values from AggregateService
        $aggregateService = app(AggregateService::class);
        $dailyRecap = $aggregateService->getDailyRecap($testDate);

        // 9. Compare workbook cell values with AggregateService output
        $marketRows = config('eret.market_rows');
        $columns = config('eret.columns');

        foreach ($dailyRecap as $row) {
            $marketName = $row['market'];
            $excelRow = $marketRows[$marketName] ?? null;

            $this->assertNotNull(
                $excelRow,
                "Market '{$marketName}' should have a row mapping in config('eret.market_rows')."
            );

            foreach ($columns as $field => $column) {
                $expectedValue = (int) ($row[$field] ?? 0);
                $cellValue = (int) $worksheet->getCell($column . $excelRow)->getValue();

                $this->assertEquals(
                    $expectedValue,
                    $cellValue,
                    "Cell {$column}{$excelRow} ({$marketName}.{$field}) should match AggregateService."
                );
            }
        }
    }
}

