<?php

namespace Tests\Feature;

use App\Models\Market;
use App\Models\Retribution;
use App\Models\User;
use App\Services\EretEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Tests\TestCase;

/**
 * PixelPerfectValidationTest — Template vs Output comparison (Task 4)
 *
 * Ensures the generated workbook is pixel-identical to the template:
 *  - Same merged cells
 *  - Same borders
 *  - Same fonts
 *  - Same colors
 *  - Same alignment
 *  - Same row heights
 *  - Same column widths
 *  - Same print area
 *  - Same formulas (unchanged)
 *
 * IMPORTANT: Always compare the SAME sheet name between template and output.
 * Template sheet "01 Juli" ↔ Output sheet "01 Juli".
 */
class PixelPerfectValidationTest extends TestCase
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

    /**
     * Helper: load template sheet and generate output sheet for the SAME date.
     * This ensures we compare apples-to-apples (same sheet name in both).
     */
    private function getTemplateAndOutputSheet(string $date): array
    {
        // Load template and find the sheet name for this date
        $templateSpreadsheet = IOFactory::load($this->templatePath);
        $wbEngine = app(\App\Services\WorkbookEngine::class);
        $wbEngine->load($this->templatePath);
        $sheetName = $wbEngine->findSheetByDateHeader($date);
        $wbEngine->disconnect();

        $this->assertNotNull($sheetName, "Template sheet for date {$date} should exist");
        $templateSheet = $templateSpreadsheet->getSheetByName($sheetName);
        $this->assertNotNull($templateSheet, "Template sheet '{$sheetName}' should exist");

        // Generate output using engine (same date)
        $user = User::factory()->create();
        $market = Market::factory()->create(['name' => 'Karimata 1']);
        $retribution = Retribution::factory()->create([
            'market_id' => $market->id,
            'retribution_date' => $date,
        ]);
        $retribution->items()->createMany([
            ['jenis_retribusi' => 'kios', 'amount' => 50000],
            ['jenis_retribusi' => 'los', 'amount' => 30000],
        ]);

        $engine = app(EretEngine::class);
        $spreadsheet = $engine->generate($date);

        $this->tempPath = tempnam(sys_get_temp_dir(), 'ERET_PIXEL_').'.xlsx';
        $engine->save($this->tempPath);
        $engine->disconnect();

        // Load generated output
        $outputSpreadsheet = IOFactory::load($this->tempPath);

        // Find output sheet by the same name
        $outputSheet = $outputSpreadsheet->getSheetByName($sheetName);
        $this->assertNotNull($outputSheet, "Output sheet '{$sheetName}' should exist");

        return [$templateSpreadsheet, $templateSheet, $outputSpreadsheet, $outputSheet, $sheetName];
    }

    public function test_merged_cells_are_identical_to_template()
    {
        // Compare using sheet "01 Juli" — same sheet in both template and output
        [$templateSpreadsheet, $templateSheet, $outputSpreadsheet, $outputSheet] = $this->getTemplateAndOutputSheet('2026-07-01');

        $templateMergedCells = $templateSheet->getMergeCells();
        $outputMergedCells = $outputSheet->getMergeCells();

        $this->assertEquals(
            count($templateMergedCells),
            count($outputMergedCells),
            'Jumlah merged cells harus identik dengan template'
        );

        sort($templateMergedCells);
        sort($outputMergedCells);
        $this->assertEquals($templateMergedCells, $outputMergedCells,
            'Merged cells harus identik dengan template');

        $templateSpreadsheet->disconnectWorksheets();
        $outputSpreadsheet->disconnectWorksheets();
    }

    public function test_formulas_are_unchanged_from_original()
    {
        // Get template formulas from "01 Juli"
        [$templateSpreadsheet, $templateSheet, $outputSpreadsheet, $outputSheet, $sheetName] = $this->getTemplateAndOutputSheet('2026-07-01');

        // Collect all formula cells from template
        $templateFormulas = [];
        $highestRow = $templateSheet->getHighestRow();
        $highestCol = $templateSheet->getHighestColumn();

        for ($row = 1; $row <= $highestRow; $row++) {
            for ($col = 'A'; $col !== 'H'; $col++) { // A through G
                $cell = $col.$row;
                if ($templateSheet->getCell($cell)->isFormula()) {
                    $templateFormulas[$cell] = $templateSheet->getCell($cell)->getValue();
                }
            }
        }

        // Compare every formula cell
        $differences = [];
        foreach ($templateFormulas as $cell => $originalFormula) {
            if ($outputSheet->getCell($cell)->isFormula()) {
                $outputFormula = $outputSheet->getCell($cell)->getValue();
                if ($originalFormula !== $outputFormula) {
                    $differences[] = [
                        'cell' => $cell,
                        'original' => $originalFormula,
                        'output' => $outputFormula,
                    ];
                }
            } else {
                $differences[] = [
                    'cell' => $cell,
                    'original' => $originalFormula,
                    'output' => '(not a formula anymore)',
                ];
            }
        }

        $this->assertEmpty($differences,
            'Semua formula harus identik dengan template. Perbedaan: '.json_encode($differences));

        $templateSpreadsheet->disconnectWorksheets();
        $outputSpreadsheet->disconnectWorksheets();
    }

    public function test_cell_styles_are_preserved()
    {
        // Compare using sheet "01 Juli" — same sheet in both
        [$templateSpreadsheet, $templateSheet, $outputSpreadsheet, $outputSheet] = $this->getTemplateAndOutputSheet('2026-07-01');

        // Check a representative sample of cells for style preservation
        $sampleCells = ['A1', 'B5', 'C13', 'D24', 'E34', 'F44', 'B45', 'B47'];

        $differences = [];
        foreach ($sampleCells as $cell) {
            $templateStyle = $templateSheet->getStyle($cell);
            $outputStyle = $outputSheet->getStyle($cell);

            // Check font
            $templateFont = $templateStyle->getFont();
            $outputFont = $outputStyle->getFont();

            if ($templateFont->getName() !== $outputFont->getName()) {
                $differences[] = "{$cell} font name: {$templateFont->getName()} vs {$outputFont->getName()}";
            }
            if ($templateFont->getSize() !== $outputFont->getSize()) {
                $differences[] = "{$cell} font size: {$templateFont->getSize()} vs {$outputFont->getSize()}";
            }
            if ($templateFont->getBold() !== $outputFont->getBold()) {
                $differences[] = "{$cell} font bold: {$templateFont->getBold()} vs {$outputFont->getBold()}";
            }

            // Check alignment
            $templateAlign = $templateStyle->getAlignment();
            $outputAlign = $outputStyle->getAlignment();

            if ($templateAlign->getHorizontal() !== $outputAlign->getHorizontal()) {
                $differences[] = "{$cell} horizontal alignment: ".
                    ($templateAlign->getHorizontal() ?? 'null').' vs '.
                    ($outputAlign->getHorizontal() ?? 'null');
            }

            if ($templateAlign->getVertical() !== $outputAlign->getVertical()) {
                $differences[] = "{$cell} vertical alignment: ".
                    ($templateAlign->getVertical() ?? 'null').' vs '.
                    ($outputAlign->getVertical() ?? 'null');
            }

            // Check borders
            $templateBorders = $templateStyle->getBorders();
            $outputBorders = $outputStyle->getBorders();

            $borderSides = ['getLeft', 'getRight', 'getTop', 'getBottom'];
            foreach ($borderSides as $sideMethod) {
                $templateBorderStyle = $templateBorders->$sideMethod()->getBorderStyle();
                $outputBorderStyle = $outputBorders->$sideMethod()->getBorderStyle();

                if ($templateBorderStyle !== $outputBorderStyle) {
                    $differences[] = "{$cell} border {$sideMethod}: ".
                        ($templateBorderStyle ?? 'none').' vs '.
                        ($outputBorderStyle ?? 'none');
                }
            }

            // Check fill color
            $templateFill = $templateStyle->getFill()->getStartColor()->getRGB();
            $outputFill = $outputStyle->getFill()->getStartColor()->getRGB();

            if ($templateFill !== $outputFill && !(empty($templateFill) && empty($outputFill))) {
                $differences[] = "{$cell} fill color: {$templateFill} vs {$outputFill}";
            }
        }

        $this->assertEmpty($differences,
            'Cell styles harus identik. Perbedaan:' . PHP_EOL . implode(PHP_EOL, $differences));

        $templateSpreadsheet->disconnectWorksheets();
        $outputSpreadsheet->disconnectWorksheets();
    }

    public function test_row_heights_and_column_widths_match_template()
    {
        // Compare using sheet "01 Juli"
        [$templateSpreadsheet, $templateSheet, $outputSpreadsheet, $outputSheet] = $this->getTemplateAndOutputSheet('2026-07-01');

        $differences = [];

        // Check column widths (A through G)
        $columns = range('A', 'G');
        foreach ($columns as $col) {
            $templateWidth = $templateSheet->getColumnDimension($col)->getWidth();
            $outputWidth = $outputSheet->getColumnDimension($col)->getWidth();

            if (abs($templateWidth - $outputWidth) > 0.01) {
                $differences[] = "Column {$col} width: {$templateWidth} vs {$outputWidth}";
            }
        }

        // Check row heights for key rows
        $keyRows = [1, 3, 5, 13, 16, 24, 27, 34, 44, 45, 47, 48];
        foreach ($keyRows as $row) {
            $templateHeight = $templateSheet->getRowDimension($row)->getRowHeight();
            $outputHeight = $outputSheet->getRowDimension($row)->getRowHeight();

            if (abs($templateHeight - $outputHeight) > 0.01) {
                $differences[] = "Row {$row} height: {$templateHeight} vs {$outputHeight}";
            }
        }

        $this->assertEmpty($differences,
            'Row heights and column widths must match template. Differences: '.PHP_EOL.
            implode(PHP_EOL, $differences));

        $templateSpreadsheet->disconnectWorksheets();
        $outputSpreadsheet->disconnectWorksheets();
    }

    public function test_print_setup_is_preserved()
    {
        // Compare using sheet "01 Juli"
        [$templateSpreadsheet, $templateSheet, $outputSpreadsheet, $outputSheet] = $this->getTemplateAndOutputSheet('2026-07-01');

        // Compare print area
        $templatePrintArea = $templateSheet->getPageSetup()->getPrintArea();
        $outputPrintArea = $outputSheet->getPageSetup()->getPrintArea();
        $this->assertEquals($templatePrintArea, $outputPrintArea,
            'Print area harus identik dengan template');

        // Compare page setup
        $templatePageSetup = $templateSheet->getPageSetup();
        $outputPageSetup = $outputSheet->getPageSetup();

        $this->assertEquals(
            $templatePageSetup->getOrientation(),
            $outputPageSetup->getOrientation(),
            'Page orientation harus identik'
        );

        $this->assertEquals(
            $templatePageSetup->getPaperSize(),
            $outputPageSetup->getPaperSize(),
            'Paper size harus identik'
        );

        $this->assertEquals(
            $templatePageSetup->getScale(),
            $outputPageSetup->getScale(),
            'Print scale harus identik'
        );

        // Compare page margins
        $templateMargins = $templateSheet->getPageMargins();
        $outputMargins = $outputSheet->getPageMargins();

        $marginProps = ['getTop', 'getBottom', 'getLeft', 'getRight', 'getHeader', 'getFooter'];
        foreach ($marginProps as $prop) {
            $this->assertEquals(
                $templateMargins->$prop(),
                $outputMargins->$prop(),
                "Page margin {$prop} harus identik"
            );
        }

        $templateSpreadsheet->disconnectWorksheets();
        $outputSpreadsheet->disconnectWorksheets();
    }
}
