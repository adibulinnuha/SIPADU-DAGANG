<?php

namespace Tests\Feature;

use App\Models\Market;
use App\Models\Retribution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EretExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_eret_template_can_be_downloaded(): void
    {
        $user = User::factory()->create();

        $market = Market::factory()->create([
            'name' => 'Karimata',
        ]);

        Retribution::factory()->create([
            'market_id' => $market->id,
            'retribution_date' => now()->toDateString(),
            'amount' => 100000,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('retributions.export-template'));

        $response->assertOk();

        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }
}