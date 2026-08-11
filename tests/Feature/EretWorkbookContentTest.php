<?php

namespace Tests\Feature;

use App\Models\Market;
use App\Models\Retribution;
use App\Models\User;
use App\Services\AggregateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class EretWorkbookContentTest extends TestCase
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

    public function test_workbook_content_matches_aggregate_service(): void
    {
        if (! file_exists(config('eret.template'))) {
            $this->markTestSkipped('Template ERET JULI.xltx tidak ditemukan.');
        }

        $testDate = '2026-07-21';

        $user = User::factory()->create();

        $market = Market::factory()->create([
            'name' => 'Karimata 1',
        ]);

        $retribution = Retribution::factory()->create([
            'market_id' => $market->id,
            'retribution_date' => $testDate,
        ]);

        $retribution->items()->createMany([
            ['jenis_retribusi' => 'kios', 'amount' => 50000],
            ['jenis_retribusi' => 'los', 'amount' => 30000],
            ['jenis_retribusi' => 'dasaran_terbuka', 'amount' => 20000],
            ['jenis_retribusi' => 'kebersihan', 'amount' => 5000],
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('retributions.export-template', ['date' => $testDate]));

        $response->assertOk();

        $this->tempPath = tempnam(sys_get_temp_dir(), 'ERET_WB_').'.xlsx';
        file_put_contents($this->tempPath, $response->streamedContent());

        $spreadsheet = IOFactory::load($this->tempPath);

        $this->assertGreaterThan(
            0,
            $spreadsheet->getSheetCount(),
            'Workbook should contain at least one worksheet.'
        );

        $worksheet = $spreadsheet->getSheetByName('21 Jul');

        $this->assertNotNull(
            $worksheet,
            "Worksheet '21 Jul' should exist in the workbook."
        );

        $aggregateService = app(AggregateService::class);
        $dailyRecap = $aggregateService->getDailyRecap($testDate);
        $row = $dailyRecap->firstWhere('market', 'Karimata 1');

        $this->assertNotNull($row, 'Karimata 1 should appear in daily recap.');

        $valueFields = [
            'kios' => 'B24',
            'los' => 'C24',
            'dasaran_terbuka' => 'D24',
            'kebersihan' => 'E24',
        ];

        foreach ($valueFields as $field => $cell) {
            $this->assertEquals(
                (int) ($row[$field] ?? 0),
                (int) $worksheet->getCell($cell)->getValue(),
                "Cell {$cell} (Karimata 1.{$field}) should match AggregateService."
            );
        }

        $this->assertStringStartsWith(
            '=',
            (string) $worksheet->getCell('F24')->getValue(),
            'F24 should remain a formula.'
        );

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    }
}
