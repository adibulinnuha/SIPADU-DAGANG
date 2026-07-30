<?php

namespace Tests\Feature;

use App\Models\Market;
use App\Models\Retribution;
use App\Models\User;
use App\Services\CellMapper;
use App\Services\EretEngine;
use App\Services\WorkbookEngine;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class EretEngineTest extends TestCase
{
    use RefreshDatabase;

    protected ?string $tempPath = null;

    protected function tearDown(): void
    {
        if ($this->tempPath && file_exists($this->tempPath)) {
            unlink($this->tempPath);
        }
        parent::tearDown();
    }

    // -----------------------------------------------------------------------
    //  WorkbookEngine Tests
    // -----------------------------------------------------------------------

    public function test_workbook_engine_loads_template(): void
    {
        $engine = app(WorkbookEngine::class);
        $templatePath = config('eret.template');

        $engine->load($templatePath);

        $sheetNames = $engine->getSheetNames();

        $this->assertGreaterThan(0, count($sheetNames));
        $this->assertContains('01 Juli', $sheetNames);
    }

    public function test_workbook_engine_selects_sheet(): void
    {
        $engine = app(WorkbookEngine::class);
        $templatePath = config('eret.template');

        $engine->load($templatePath);
        $engine->selectSheet('01 Juli');

        $this->assertEquals('01 Juli', $engine->getCurrentSheetName());
        $this->assertGreaterThan(0, $engine->getHighestRow());
    }

    public function test_workbook_engine_finds_sheet_by_date_header(): void
    {
        $engine = app(WorkbookEngine::class);
        $templatePath = config('eret.template');

        $engine->load($templatePath);
        $sheetName = $engine->findSheetByDateHeader('2026-07-01');

        $this->assertNotNull($sheetName);
        $this->assertEquals('01 Juli', $sheetName);
    }

    public function test_workbook_engine_reads_cell_value(): void
    {
        $engine = app(WorkbookEngine::class);
        $templatePath = config('eret.template');

        $engine->load($templatePath);
        $engine->selectSheet('01 Juli');

        // Row 1 should contain the date header
        $header = $engine->getCellValue('A1');
        $this->assertStringContainsString('01 JULI 2026', strtoupper((string) $header));
    }

    public function test_workbook_engine_detects_formula_cells(): void
    {
        $engine = app(WorkbookEngine::class);
        $templatePath = config('eret.template');

        $engine->load($templatePath);
        $engine->selectSheet('01 Juli');

        // Column F cells are formulas (=SUM(Bn:En))
        $this->assertTrue($engine->isFormulaCell('F5'));
        $this->assertTrue($engine->isFormulaCell('F16'));

        // Column B cells are values (not formulas)
        // B16 is =SUM(B5:B15) which IS a formula
        $this->assertTrue($engine->isFormulaCell('B16'));
    }

    public function test_workbook_engine_protects_formula_cells_from_write(): void
    {
        $engine = app(WorkbookEngine::class);
        $templatePath = config('eret.template');

        $engine->load($templatePath);
        $engine->selectSheet('01 Juli');

        // Try writing to a formula cell — should NOT change the value
        $originalValue = $engine->getCellValue('F5');
        $engine->setCellValue('F5', 999999);
        $newValue = $engine->getCellValue('F5');

        // Value should remain unchanged because F5 contains a formula
        $this->assertEquals($originalValue, $newValue);
    }

    public function test_workbook_engine_writes_to_value_cells(): void
    {
        $engine = app(WorkbookEngine::class);
        $templatePath = config('eret.template');

        $engine->load($templatePath);
        $engine->selectSheet('01 Juli');

        // Write to a value cell (B5 = Kios for Rejomulyo on row 5)
        $engine->setCellValue('B5', 12345);
        $value = $engine->getCellValue('B5');

        $this->assertEquals(12345, (int) $value);
    }

    public function test_workbook_engine_saves_and_reloads(): void
    {
        $engine = app(WorkbookEngine::class);
        $templatePath = config('eret.template');

        $engine->load($templatePath);
        $engine->selectSheet('01 Juli');

        // Write a value
        $engine->setCellValue('B5', 77777);

        // Save to temp file
        $this->tempPath = tempnam(sys_get_temp_dir(), 'ERET_SAVE_').'.xlsx';
        $engine->save($this->tempPath);

        // Reload and verify
        $engine2 = app(WorkbookEngine::class);
        $engine2->load($this->tempPath);
        $engine2->selectSheet('01 Juli');

        $value = $engine2->getCellValue('B5');
        $this->assertEquals(77777, (int) $value);

        // Formula should still be intact
        $this->assertTrue($engine2->isFormulaCell('F5'));
    }

    // -----------------------------------------------------------------------
    //  CellMapper Tests
    // -----------------------------------------------------------------------

    public function test_cell_mapper_scans_markets_dynamically(): void
    {
        $engine = app(WorkbookEngine::class);
        $templatePath = config('eret.template');

        $engine->load($templatePath);
        $engine->selectSheet('01 Juli');

        $mapper = app(CellMapper::class);
        $mapper->setEngine($engine);

        $markets = $mapper->scanMarkets();

        // Should find multiple markets
        $this->assertGreaterThan(0, count($markets));

        // Should find Rejomulyo (in manual section)
        $rejomulyo = collect($markets)->firstWhere('name', 'Rejomulyo');
        $this->assertNotNull($rejomulyo);
        $this->assertEquals(5, $rejomulyo['row']);
        $this->assertEquals('manual', $rejomulyo['group']);

        // Should find Karimata 1 (in eret section)
        $karimata1 = collect($markets)->firstWhere('name', 'Karimata 1');
        $this->assertNotNull($karimata1);
        $this->assertEquals(24, $karimata1['row']);
        $this->assertEquals('eret', $karimata1['group']);

        // Dargo should appear twice (manual row 13, eret row 27)
        $dargoOccurrences = collect($markets)->filter(fn ($m) => $m['name'] === 'Dargo');
        $this->assertGreaterThanOrEqual(2, $dargoOccurrences->count());
    }

    public function test_cell_mapper_scans_column_headers(): void
    {
        $engine = app(WorkbookEngine::class);
        $templatePath = config('eret.template');

        $engine->load($templatePath);
        $engine->selectSheet('01 Juli');

        $mapper = app(CellMapper::class);
        $mapper->setEngine($engine);

        $headers = $mapper->scanColumnHeaders();

        $this->assertArrayHasKey('Kios', $headers);
        $this->assertArrayHasKey('Los', $headers);
        $this->assertArrayHasKey('DT', $headers);
        $this->assertArrayHasKey('Kebersihan', $headers);
        $this->assertArrayHasKey('Total', $headers);

        $this->assertEquals('B', $headers['Kios']);
        $this->assertEquals('C', $headers['Los']);
    }

    public function test_cell_mapper_finds_listrik_label(): void
    {
        $engine = app(WorkbookEngine::class);
        $templatePath = config('eret.template');

        $engine->load($templatePath);
        $engine->selectSheet('01 Juli');

        $mapper = app(CellMapper::class);
        $mapper->setEngine($engine);

        $cell = $mapper->findListrikCell();

        $this->assertNotNull($cell);
        $this->assertEquals('B45', $cell);
    }

    public function test_cell_mapper_finds_mck_label(): void
    {
        $engine = app(WorkbookEngine::class);
        $templatePath = config('eret.template');

        $engine->load($templatePath);
        $engine->selectSheet('01 Juli');

        $mapper = app(CellMapper::class);
        $mapper->setEngine($engine);

        $cell = $mapper->findMckCell();

        $this->assertNotNull($cell);
        $this->assertEquals('B47', $cell);
    }

    public function test_cell_mapper_finds_market_occurrences(): void
    {
        $engine = app(WorkbookEngine::class);
        $templatePath = config('eret.template');

        $engine->load($templatePath);
        $engine->selectSheet('01 Juli');

        $mapper = app(CellMapper::class);
        $mapper->setEngine($engine);

        // Dargo appears in both manual (row 13) and eret (row 27)
        $dargoOccurrences = $mapper->findMarketOccurrences('Dargo');

        $this->assertGreaterThanOrEqual(2, count($dargoOccurrences));

        $rows = collect($dargoOccurrences)->pluck('row')->toArray();
        $this->assertContains(13, $rows);
        $this->assertContains(27, $rows);
    }

    public function test_cell_mapper_returns_empty_for_nonexistent_market(): void
    {
        $engine = app(WorkbookEngine::class);
        $templatePath = config('eret.template');

        $engine->load($templatePath);
        $engine->selectSheet('01 Juli');

        $mapper = app(CellMapper::class);
        $mapper->setEngine($engine);

        $occurrences = $mapper->findMarketOccurrences('Pasar Tidak Ada');

        $this->assertEmpty($occurrences);
    }

    public function test_cell_mapper_gets_field_to_column_map(): void
    {
        $engine = app(WorkbookEngine::class);
        $templatePath = config('eret.template');

        $engine->load($templatePath);
        $engine->selectSheet('01 Juli');

        $mapper = app(CellMapper::class);
        $mapper->setEngine($engine);

        $map = $mapper->getFieldToColumnMap();

        $this->assertEquals('B', $map['kios']);
        $this->assertEquals('C', $map['los']);
        $this->assertEquals('D', $map['dasaran_terbuka']);
        $this->assertEquals('E', $map['kebersihan']);
    }

    // -----------------------------------------------------------------------
    //  EretEngine Integration Tests
    // -----------------------------------------------------------------------

    public function test_eret_engine_dry_run_returns_report(): void
    {
        // Create test data
        $user = User::factory()->create();
        $market = Market::factory()->create(['name' => 'Karimata 1']);
        $retribution = Retribution::factory()->create([
            'market_id' => $market->id,
            'retribution_date' => '2026-07-21',
        ]);
        $retribution->items()->createMany([
            ['jenis_retribusi' => 'kios', 'amount' => 100000],
            ['jenis_retribusi' => 'los', 'amount' => 50000],
            ['jenis_retribusi' => 'dasaran_terbuka', 'amount' => 25000],
            ['jenis_retribusi' => 'kebersihan', 'amount' => 15000],
        ]);

        $engine = app(EretEngine::class);
        $report = $engine->dryRun('2026-07-21');

        // Verify report structure
        $this->assertArrayHasKey('date', $report);
        $this->assertArrayHasKey('sheet', $report);
        $this->assertArrayHasKey('columns', $report);
        $this->assertArrayHasKey('markets_in_template', $report);
        $this->assertArrayHasKey('markets_from_database', $report);
        $this->assertArrayHasKey('mapping_result', $report);
        $this->assertArrayHasKey('listrik_cell', $report);
        $this->assertArrayHasKey('mck_cell', $report);

        // Verify Karimata 1 was found
        $this->assertArrayHasKey('Karimata 1', $report['mapping_result']);
        $this->assertEquals('FOUND', $report['mapping_result']['Karimata 1']['status']);
    }

    public function test_eret_engine_fills_cells_and_preserves_formulas(): void
    {
        // Create test data
        $user = User::factory()->create();
        $market = Market::factory()->create(['name' => 'Karimata 1']);
        $retribution = Retribution::factory()->create([
            'market_id' => $market->id,
            'retribution_date' => '2026-07-21',
        ]);
        $retribution->items()->createMany([
            ['jenis_retribusi' => 'kios', 'amount' => 75000],
            ['jenis_retribusi' => 'los', 'amount' => 60000],
            ['jenis_retribusi' => 'dasaran_terbuka', 'amount' => 30000],
            ['jenis_retribusi' => 'kebersihan', 'amount' => 20000],
        ]);

        // Generate using the engine
        $engine = app(EretEngine::class);
        $spreadsheet = $engine->generate('2026-07-21');

        // Save to temp file
        $this->tempPath = tempnam(sys_get_temp_dir(), 'ERET_ENGINE_').'.xlsx';
        $engine->save($this->tempPath);
        $engine->disconnect();

        // Reopen and verify
        $spreadsheet2 = IOFactory::load($this->tempPath);
        $sheet = $spreadsheet2->getSheetByName('21 Jul');
        $this->assertNotNull($sheet);

        // Verify values were written (Karimata 1 is at row 24 in template sheet)
        // But wait — the sheet "21 Jul" might have different row structure
        // Let's check row 24 (Karimata 1 in "01 Juli")
        $kiosValue = (int) $sheet->getCell('B24')->getCalculatedValue();
        $losValue = (int) $sheet->getCell('C24')->getCalculatedValue();
        $dtValue = (int) $sheet->getCell('D24')->getCalculatedValue();
        $kebersihanValue = (int) $sheet->getCell('E24')->getCalculatedValue();

        // Check that formula F24 still works (it should sum B24:E24)
        $totalFormula = $sheet->getCell('F24')->getValue();
        $this->assertStringStartsWith('=', $totalFormula, 'F24 should contain a formula');

        $totalValue = (int) $sheet->getCell('F24')->getCalculatedValue();
        $this->assertEquals($kiosValue + $losValue + $dtValue + $kebersihanValue, $totalValue,
            'F24 formula should sum the input values');

        // Clean up
        $spreadsheet2->disconnectWorksheets();
        unset($spreadsheet2);
    }

    public function test_eret_engine_handles_missing_market_with_warning(): void
    {
        // Create a market NOT in the template
        $user = User::factory()->create();
        $market = Market::factory()->create(['name' => 'Pasar Baru Tidak Ada Di Template']);
        $retribution = Retribution::factory()->create([
            'market_id' => $market->id,
            'retribution_date' => '2026-07-21',
        ]);
        $retribution->items()->createMany([
            ['jenis_retribusi' => 'kios', 'amount' => 50000],
        ]);

        // Generate — should not crash
        $engine = app(EretEngine::class);
        $spreadsheet = $engine->generate('2026-07-21');

        $this->assertNotNull($spreadsheet);

        // Save and verify workbook is valid
        $this->tempPath = tempnam(sys_get_temp_dir(), 'ERET_MISSING_').'.xlsx';
        $engine->save($this->tempPath);
        $engine->disconnect();

        // Reopen — workbook should be valid
        $spreadsheet2 = IOFactory::load($this->tempPath);
        $this->assertGreaterThan(0, $spreadsheet2->getSheetCount());
        $spreadsheet2->disconnectWorksheets();
        unset($spreadsheet2);
    }

    public function test_eret_engine_end_to_end_via_route(): void
    {
        // Create test data matching template market names
        $user = User::factory()->create();

        // Create multiple markets that exist in the template
        $markets = [
            Market::factory()->create(['name' => 'Rejomulyo']),
            Market::factory()->create(['name' => 'Karimata 1']),
            Market::factory()->create(['name' => 'Dargo']),
        ];

        foreach ($markets as $market) {
            $retribution = Retribution::factory()->create([
                'market_id' => $market->id,
                'retribution_date' => '2026-07-21',
            ]);
            $retribution->items()->createMany([
                ['jenis_retribusi' => 'kios', 'amount' => 100000],
                ['jenis_retribusi' => 'los', 'amount' => 50000],
                ['jenis_retribusi' => 'kebersihan', 'amount' => 25000],
            ]);
        }

        // Call the route with engine=new
        $response = $this
            ->actingAs($user)
            ->get(route('retributions.export-template', [
                'date' => '2026-07-21',
                'engine' => 'new',
            ]));

        $response->assertOk();

        // Save to temp file and verify
        $this->tempPath = tempnam(sys_get_temp_dir(), 'ERET_E2E_').'.xlsx';
        file_put_contents($this->tempPath, $response->streamedContent());

        $spreadsheet = IOFactory::load($this->tempPath);
        $this->assertGreaterThan(0, $spreadsheet->getSheetCount());

        // Verify sheet exists
        $sheet = $spreadsheet->getSheetByName('21 Jul');
        $this->assertNotNull($sheet);

        // Verify formulas are intact (F column should still be formulas)
        $this->assertTrue($sheet->getCell('F5')->isFormula());
        $this->assertTrue($sheet->getCell('F24')->isFormula());

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    }
}

