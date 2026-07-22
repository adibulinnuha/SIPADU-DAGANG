<?php

use App\Exports\RetributionsExport;
use App\Models\Market;
use App\Models\Retribution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

test('authenticated user can download retributions excel export', function () {
    Excel::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('retributions.export'));

    $response->assertStatus(200);

    Excel::assertDownloaded('Retribusi_'.now()->format('Y-m-d_H-i').'.xlsx', function (RetributionsExport $export) {
        return true;
    });
});

test('retributions export correctly instantiates filters from request', function () {
    Excel::fake();

    $user = User::factory()->create();

    $market = Market::create([
        'name' => 'Pasar Gede',
        'code' => 'PG01',
    ]);

    $response = $this->actingAs($user)
        ->get(route('retributions.export', [
            'market_id' => $market->id,
            'date_start' => '2026-07-01',
            'date_end' => '2026-07-15',
        ]));

    $response->assertStatus(200);

    Excel::assertDownloaded('Retribusi_'.now()->format('Y-m-d_H-i').'.xlsx', function (RetributionsExport $export) {
        $query = $export->query();

        $wheres = $query->getQuery()->wheres;

        $hasMarketFilter = collect($wheres)->contains(function ($where) {
            return ($where['column'] ?? null) === 'market_id';
        });

        return $hasMarketFilter;
    });
});

test('retributions export query returns correct data based on filters', function () {
    $user = User::factory()->create();

    $marketA = Market::create([
        'name' => 'Pasar A',
        'code' => 'PA01',
    ]);

    $marketB = Market::create([
        'name' => 'Pasar B',
        'code' => 'PB01',
    ]);

    Retribution::create([
        'market_id' => $marketA->id,
        'recorded_by' => $user->id,
        'jenis_retribusi' => 'Kebersihan',
        'retribution_date' => '2026-07-10',
        'amount' => 5000,
        'payment_method' => 'Tunai',
    ]);

    Retribution::create([
        'market_id' => $marketB->id,
        'recorded_by' => $user->id,
        'jenis_retribusi' => 'Parkir',
        'retribution_date' => '2026-07-12',
        'amount' => 10000,
        'payment_method' => 'QRIS',
    ]);

    $exportAll = new RetributionsExport;
    $resultsAll = $exportAll->query()->get();

    expect($resultsAll)->toHaveCount(2);
});
