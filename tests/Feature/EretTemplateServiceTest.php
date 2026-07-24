<?php

namespace Tests\Feature;

use App\Models\Market;
use App\Models\Retribution;
use App\Models\RetributionItem;
use App\Services\EretTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EretTemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_eret_template_is_filled_from_aggregate_service(): void
    {
        $market = Market::factory()->create([
            'name' => 'Karimata',
        ]);

        $retribution = Retribution::factory()->create([
            'market_id' => $market->id,
            'retribution_date' => '2026-07-24',
        ]);

        RetributionItem::factory()->create([
            'retribution_id' => $retribution->id,
            'jenis_retribusi' => 'kios',
            'amount' => 10000,
        ]);

        $spreadsheet = app(EretTemplateService::class)
            ->generate('JULI', '2026-07-24');

        $sheet = $spreadsheet->getSheetByName('JULI')
            ?? $spreadsheet->getActiveSheet();

        $this->assertEquals(10000, $sheet->getCell('B24')->getValue());
    }
}