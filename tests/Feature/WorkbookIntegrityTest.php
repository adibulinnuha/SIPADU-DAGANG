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
 * WorkbookIntegrityTest — Final workbook integrity validation for Sprint 8.
 *
 * Verifies that the exported workbook:
 *  - has a reasonable file size (not empty, not absurdly large)
 *  - can be reopened without errors/warnings by PhpSpreadsheet
 *  - preserves merged cells
 *  - preserves formulas
 *  - preserves number formats
 *  - contains the expected data
 */
class WorkbookIntegrityTest extends TestCase
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

    public function test_exported_workbook_has_reasonable_file_size(): void
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
            ->get(route('retributions.export-template', ['date' => '2026-07-21']));

        $response->assertOk();

        $this->tempPath = tempnam(sys_get_temp_dir(), 'ERET_INT_').'.xlsx';
        file_put_contents($this->tempPath, $response->streamedContent());

        $size = filesize($this->tempPath);

        // Reasonable: template-derived workbook should be > 1KB and < 50MB.
        $this->assertGreaterThan(1024, $size, 'Workbook file size should be non-trivial.');
        $this->assertLessThan(50 * 1024 * 1024, $size, 'Workbook file size should be reasonable.');

        // Compare against the official template size (within a sane factor).
        if (file_exists($this->templatePath)) {
            $templateSize = filesize($this->templatePath);
            $this->assertGreaterThan(0, $templateSize);
            // Exported workbook should not be dramatically larger than the template.
            $this->assertLessThan($templateSize * 5, $size);
        }
    }

    public function test_exported_workbook_opens_without_error(): void
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
            ->get(route('retributions.export-template', ['date' => '2026-07-21']));

        $response->assertOk();

        $this->tempPath = tempnam(sys_get_temp_dir(), 'ERET_OPEN_').'.xlsx';
        file_put_contents($this->tempPath, $response->streamedContent());

        // Loading without exception proves the workbook is well-formed.
        $spreadsheet = IOFactory::load($this->tempPath);

        $this->assertGreaterThan(0, $spreadsheet->getSheetCount());
        $this->assertNotNull($spreadsheet->getSheetByName('21 Jul'));

        $spreadsheet->disconnectWorksheets();
    }

    public function test_exported_workbook_preserves_merges_formulas_and_number_formats(): void
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
            ->get(route('retributions.export-template', ['date' => '2026-07-21']));

        $response->assertOk();

        $this->tempPath = tempnam(sys_get_temp_dir(), 'ERET_MPF_').'.xlsx';
        file_put_contents($this->tempPath, $response->streamedContent());

        $exported = IOFactory::load($this->tempPath);
        $sheet = $exported->getSheetByName('21 Jul');

        $this->assertNotNull($sheet);

        // Merged cells preserved.
        $mergeRanges = $sheet->getMergeCells();
        $this->assertNotEmpty($mergeRanges);

        // Formulas preserved (F column total).
        $this->assertTrue($sheet->getCell('F24')->isFormula());

        // Number format preserved on a value cell.
        $this->assertStringContainsString(
            '0',
            $sheet->getCell('B5')->getStyle()->getNumberFormat()->getFormatCode()
        );

        $exported->disconnectWorksheets();
    }

public function test_exported_workbook_contains_expected_data(): void
    {
        $user = User::factory()->create();
        $market = Market::factory()->create(['name' => 'Karimata']);
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
            ->get(route('retributions.export-template', ['date' => '2026-07-21']));

        $response->assertOk();

        $this->tempPath = tempnam(sys_get_temp_dir(), 'ERET_DATA_').'.xlsx';
        file_put_contents($this->tempPath, $response->streamedContent());

        $exported = IOFactory::load($this->tempPath);
        $sheet = $exported->getSheetByName('21 Jul');

        $excelRow = config('eret.market_rows')['Karimata'] ?? null;
        $this->assertNotNull($excelRow);

        // kios -> column B, los -> column C
        $this->assertSame(100000, (int) $sheet->getCell('B'.$excelRow)->getValue());
        $this->assertSame(50000, (int) $sheet->getCell('C'.$excelRow)->getValue());

        $exported->disconnectWorksheets();
    }
}
