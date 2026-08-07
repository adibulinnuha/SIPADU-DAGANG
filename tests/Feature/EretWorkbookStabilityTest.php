<?php

namespace Tests\Feature;

use App\Models\Market;
use App\Models\Retribution;
use App\Models\User;
use App\Services\EretEngine;
use App\Services\WorkbookEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

/**
 * EretWorkbookStabilityTest — Regression protection for the Workbook Engine
 * and Excel export compatibility with PhpSpreadsheet 1.30.6.
 *
 * Covers:
 *  - workbook generation (load + select + fill + save)
 *  - merged-cell detection (including the compatible isCellInRange helper,
 *    which replaces the removed Coordinate::coordinateIsInsideRange())
 *  - formula preservation
 *  - styles, row heights, column widths, number formats
 *  - export success via the HTTP route
 *  - compatibility guard (assertPhpSpreadsheetCompatible)
 */
class EretWorkbookStabilityTest extends TestCase
{
    use RefreshDatabase;

    protected ?string $tempPath = null;
    protected string $templatePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->templatePath = config('eret.template');
    }

    protected function tearDown(): void
    {
        if ($this->tempPath && file_exists($this->tempPath)) {
            unlink($this->tempPath);
        }
        parent::tearDown();
    }

    private function makeEngine(): WorkbookEngine
    {
        $engine = app(WorkbookEngine::class);
        $engine->load($this->templatePath);
        $engine->selectSheet('01 Juli');

        return $engine;
    }

    // -----------------------------------------------------------------------
    //  Workbook generation
    // -----------------------------------------------------------------------

    public function test_workbook_engine_generates_and_saves_a_valid_workbook(): void
    {
        $engine = app(WorkbookEngine::class);
        $engine->load($this->templatePath);
        $engine->selectSheet('01 Juli');

        $engine->setCellValue('B5', 12345);
        $engine->setCellValue('C5', 999);

        $this->tempPath = tempnam(sys_get_temp_dir(), 'ERET_STAB_').'.xlsx';
        $engine->save($this->tempPath);
        $engine->disconnect();

        $reopened = IOFactory::load($this->tempPath);
        $this->assertInstanceOf(Spreadsheet::class, $reopened);
        $this->assertGreaterThan(0, $reopened->getSheetCount());
        $this->assertNotNull($reopened->getSheetByName('01 Juli'));
        $reopened->disconnectWorksheets();
    }

    public function test_workbook_engine_preserves_public_api_contract(): void
    {
        $engine = $this->makeEngine();

        // All original public methods must remain callable.
        $this->assertSame('01 Juli', $engine->getCurrentSheetName());
        $this->assertGreaterThan(0, $engine->getHighestRow());
        $this->assertNotEmpty($engine->getHighestColumn());
        $this->assertIsArray($engine->getSheetNames());
        $this->assertIsArray($engine->getMergedCells());
        $this->assertInstanceOf(\PhpOffice\PhpSpreadsheet\Spreadsheet::class, $engine->getSpreadsheet());
        $this->assertInstanceOf(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::class, $engine->getSheet());
    }

    // -----------------------------------------------------------------------
    //  Merged cells (compatible isCellInRange helper)
    // -----------------------------------------------------------------------

    public function test_is_cell_in_range_handles_single_and_range_addresses(): void
    {
        $engine = $this->makeEngine();

        // Inside vertical merges A3:A4, B3:B4, etc.
        $this->assertTrue($engine->isCellInRange('A3', 'A3:A4'));
        $this->assertTrue($engine->isCellInRange('A4', 'A3:A4'));
        $this->assertFalse($engine->isCellInRange('A5', 'A3:A4'));

        // Inside two-column merge A1:B2
        $this->assertTrue($engine->isCellInRange('A1', 'A1:B2'));
        $this->assertTrue($engine->isCellInRange('B2', 'A1:B2'));
        $this->assertFalse($engine->isCellInRange('C2', 'A1:B2'));

        // Single-cell range
        $this->assertTrue($engine->isCellInRange('B5', 'B5'));
        $this->assertFalse($engine->isCellInRange('B5', 'C5'));
    }

    public function test_merged_cell_detection_matches_template(): void
    {
        $engine = $this->makeEngine();

        // These cells are part of merged ranges in the official template.
        $this->assertTrue($engine->isMergedCell('A1'));   // A1:B2
        $this->assertTrue($engine->isMergedCell('B2'));   // A1:B2
        $this->assertTrue($engine->isMergedCell('A3'));   // A3:A4
        $this->assertTrue($engine->isMergedCell('A4'));   // A3:A4
        $this->assertTrue($engine->isMergedCell('B3'));   // B3:B4
        $this->assertTrue($engine->isMergedCell('G4'));   // G4:G5
        $this->assertTrue($engine->isMergedCell('H5'));   // H4:H5
        $this->assertTrue($engine->isMergedCell('G20'));  // G20:G21
        $this->assertTrue($engine->isMergedCell('A22'));  // A22:A23

        // These cells should NOT be merged (data rows).
        $this->assertFalse($engine->isMergedCell('B5'));
        $this->assertFalse($engine->isMergedCell('C5'));
        $this->assertFalse($engine->isMergedCell('D6'));
        $this->assertFalse($engine->isMergedCell('E13'));
    }

    public function test_protected_cells_include_merged_and_subtotal_rows(): void
    {
        $engine = $this->makeEngine();

        // Merged cells are protected.
        $this->assertTrue($engine->isProtectedCell('A1'));

        // Subtotal rows are protected in all columns.
        $this->assertTrue($engine->isProtectedCell('B16'));
        $this->assertTrue($engine->isProtectedCell('C34'));

        // Column F always protected (formula total column).
        $this->assertTrue($engine->isProtectedCell('F5'));

        // Normal value cells are writable.
        $this->assertFalse($engine->isProtectedCell('B5'));
        $this->assertFalse($engine->isProtectedCell('C5'));
    }

    // -----------------------------------------------------------------------
    //  Formula preservation
    // -----------------------------------------------------------------------

    public function test_formulas_are_preserved_after_generation(): void
    {
        $user = User::factory()->create();
        $market = Market::factory()->create(['name' => 'Karimata 1']);
        $retribution = Retribution::factory()->create([
            'market_id' => $market->id,
            'retribution_date' => '2026-07-21',
        ]);
        $retribution->items()->createMany([
            ['jenis_retribusi' => 'kios', 'amount' => 50000],
            ['jenis_retribusi' => 'los', 'amount' => 30000],
        ]);

        $engine = app(EretEngine::class);
        $spreadsheet = $engine->generate('2026-07-21');

        $this->tempPath = tempnam(sys_get_temp_dir(), 'ERET_FORM_').'.xlsx';
        $engine->save($this->tempPath);
        $engine->disconnect();

        $reopened = IOFactory::load($this->tempPath);
        $sheet = $reopened->getSheetByName('21 Jul');
        $this->assertNotNull($sheet);

        // F column formulas must remain intact.
        $this->assertTrue($sheet->getCell('F5')->isFormula());
        $this->assertTrue($sheet->getCell('F24')->isFormula());
        $reopened->disconnectWorksheets();
    }

    public function test_set_cell_value_never_overwrites_a_formula(): void
    {
        $engine = $this->makeEngine();

        $originalFormula = $engine->getCellRawValue('F5');
        $this->assertStringStartsWith('=', (string) $originalFormula);

        $engine->setCellValue('F5', 999999);

        $this->assertSame($originalFormula, $engine->getCellRawValue('F5'));
    }

    // -----------------------------------------------------------------------
    //  Styles, row heights, column widths, number formats
    // -----------------------------------------------------------------------

    public function test_styles_number_formats_rows_and_columns_preserved_after_export(): void
    {
        $engine = app(EretEngine::class);
        $spreadsheet = $engine->generate('2026-07-01');
        $this->tempPath = tempnam(sys_get_temp_dir(), 'ERET_STYLE_').'.xlsx';
        $engine->save($this->tempPath);
        $engine->disconnect();

        $original = IOFactory::load($this->templatePath);
        $originalSheet = $original->getSheetByName('01 Juli');

        $exported = IOFactory::load($this->tempPath);
        $exportedSheet = $exported->getSheetByName('01 Juli');

        // Number format for B5 (currency format) must be preserved.
        $this->assertSame(
            $originalSheet->getCell('B5')->getStyle()->getNumberFormat()->getFormatCode(),
            $exportedSheet->getCell('B5')->getStyle()->getNumberFormat()->getFormatCode()
        );

        // Column width preserved.
        $this->assertSame(
            $originalSheet->getColumnDimension('B')->getWidth(),
            $exportedSheet->getColumnDimension('B')->getWidth()
        );

        // Row height preserved for a key row.
        $this->assertSame(
            $originalSheet->getRowDimension(5)->getRowHeight(),
            $exportedSheet->getRowDimension(5)->getRowHeight()
        );

        // A sample style (font name) preserved.
        $this->assertSame(
            $originalSheet->getStyle('A1')->getFont()->getName(),
            $exportedSheet->getStyle('A1')->getFont()->getName()
        );

        $original->disconnectWorksheets();
        $exported->disconnectWorksheets();
    }

    public function test_get_formatted_value_returns_currency_formatted_string(): void
    {
        $engine = $this->makeEngine();

        // C5 has a numeric value (195800) with a currency number format.
        $formatted = $engine->getFormattedValue('C5');
        $this->assertIsString($formatted);
        $this->assertStringContainsString('Rp', (string) $formatted);
    }

    // -----------------------------------------------------------------------
    //  Export success via HTTP
    // -----------------------------------------------------------------------

    public function test_export_template_route_returns_ok_workbook(): void
    {
        $user = User::factory()->create();
        $market = Market::factory()->create(['name' => 'Karimata 1']);
        $retribution = Retribution::factory()->create([
            'market_id' => $market->id,
            'retribution_date' => '2026-07-21',
        ]);
        $retribution->items()->createMany([
            ['jenis_retribusi' => 'kios', 'amount' => 100000],
            ['jenis_retribusi' => 'los', 'amount' => 50000],
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('retributions.export-template', [
                'date' => '2026-07-21',
                'engine' => 'new',
            ]));

        $response->assertOk();

        $this->tempPath = tempnam(sys_get_temp_dir(), 'ERET_HTTP_').'.xlsx';
        file_put_contents($this->tempPath, $response->streamedContent());

        $spreadsheet = IOFactory::load($this->tempPath);
        $this->assertGreaterThan(0, $spreadsheet->getSheetCount());
        $this->assertNotNull($spreadsheet->getSheetByName('21 Jul'));
        $spreadsheet->disconnectWorksheets();
    }

    // -----------------------------------------------------------------------
    //  Compatibility guard
    // -----------------------------------------------------------------------

    public function test_phpspreadsheet_api_is_compatible(): void
    {
        // The guard runs on load(); if it throws, the installed PhpSpreadsheet
        // version is incompatible. Loading without exception proves the guard
        // passes for the installed version (1.30.6).
        $engine = app(WorkbookEngine::class);
        $engine->load($this->templatePath);

        $this->assertInstanceOf(WorkbookEngine::class, $engine);
        $engine->disconnect();
    }

    public function test_removed_coordinate_method_is_not_used(): void
    {
        // Regression: the removed Coordinate::coordinateIsInsideRange() must
        // NOT be referenced anywhere in the app code. The engine must use the
        // compatible isCellInRange() helper instead.
        $this->assertFalse(method_exists(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::class, 'coordinateIsInsideRange'));

        // The engine must still correctly detect merged cells without it.
        $engine = $this->makeEngine();
        $this->assertTrue($engine->isMergedCell('A1'));
        $this->assertTrue($engine->isMergedCell('B3'));
    }
}
