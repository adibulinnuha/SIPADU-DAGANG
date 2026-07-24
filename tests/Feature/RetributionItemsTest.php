<?php

use App\Exports\RekapHarianExport;
use App\Models\Market;
use App\Models\Retribution;
use App\Models\RetributionItem;
use App\Models\User;
use App\Services\AggregateService;
use App\Services\EretService;
use App\Services\EretTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('retribution can store detailed eret items and compute total amount', function () {
    $user = User::factory()->create();

    $market = Market::create([
        'name' => 'Karimata',
        'code' => 'KR01',
    ]);

    $retribution = Retribution::create([
        'market_id' => $market->id,
        'recorded_by' => $user->id,
        'jenis_retribusi' => 'Kebersihan',
        'retribution_date' => '2026-07-29',
        'amount' => 5000,
        'payment_method' => 'Tunai',
        'status' => 'draft',
    ]);

    RetributionItem::create([
        'retribution_id' => $retribution->id,
        'jenis_retribusi' => 'kios',
        'quantity' => 1,
        'amount' => 250000,
    ]);

    RetributionItem::create([
        'retribution_id' => $retribution->id,
        'jenis_retribusi' => 'los',
        'quantity' => 1,
        'amount' => 150000,
    ]);

    $total = $retribution->items()->sum('amount');

    expect((float) $total)->toBe(400000.0);
});

test('eret service can summarize daily recap from retribution items', function () {
    $user = User::factory()->create();

    $market = Market::create([
        'name' => 'Karimata',
        'code' => 'KR01',
    ]);

    $retribution = Retribution::create([
        'market_id' => $market->id,
        'recorded_by' => $user->id,
        'jenis_retribusi' => 'Kebersihan',
        'retribution_date' => '2026-07-29',
        'amount' => 5000,
        'payment_method' => 'Tunai',
        'status' => 'draft',
    ]);

    RetributionItem::create([
        'retribution_id' => $retribution->id,
        'jenis_retribusi' => 'kios',
        'quantity' => 1,
        'amount' => 250000,
    ]);

    RetributionItem::create([
        'retribution_id' => $retribution->id,
        'jenis_retribusi' => 'los',
        'quantity' => 1,
        'amount' => 150000,
    ]);

    $recap = app(EretService::class)->getDailyRecap('2026-07-29');

    expect($recap)->toHaveCount(1)
        ->and($recap[0]['market'])->toBe('Karimata')
        ->and($recap[0]['kios'])->toBe(250000.0)
        ->and($recap[0]['los'])->toBe(150000.0)
        ->and($recap[0]['total'])->toBe(400000.0);
});

test('aggregate service summarizes item-aware totals per market and category', function () {
    $user = User::factory()->create();

    $market = Market::create([
        'name' => 'Karimata',
        'code' => 'KR01',
    ]);

    $retribution = Retribution::create([
        'market_id' => $market->id,
        'recorded_by' => $user->id,
        'jenis_retribusi' => 'Kebersihan',
        'retribution_date' => '2026-07-29',
        'amount' => 5000,
        'payment_method' => 'Tunai',
        'status' => 'draft',
    ]);

    RetributionItem::create([
        'retribution_id' => $retribution->id,
        'jenis_retribusi' => 'kios',
        'quantity' => 1,
        'amount' => 250000,
    ]);

    RetributionItem::create([
        'retribution_id' => $retribution->id,
        'jenis_retribusi' => 'los',
        'quantity' => 1,
        'amount' => 150000,
    ]);

    $aggregate = app(AggregateService::class);

    expect($aggregate->getDailyRecap('2026-07-29'))->toHaveCount(1)
        ->and($aggregate->getDailyRecap('2026-07-29')[0]['market'])->toBe('Karimata')
        ->and($aggregate->getDailyRecap('2026-07-29')[0]['kios'])->toBe(250000.0)
        ->and($aggregate->getDailyRecap('2026-07-29')[0]['los'])->toBe(150000.0)
        ->and($aggregate->getDailyRecap('2026-07-29')[0]['total'])->toBe(400000.0)
        ->and($aggregate->getGrandTotal('2026-07-29'))->toBe(400000.0)
        ->and($aggregate->getTransactionCount('2026-07-29'))->toBe(1);
});

test('daily export totals are identical to aggregate service summaries', function () {
    $user = User::factory()->create();

    $karimata = Market::create([
        'name' => 'Karimata',
        'code' => 'KR01',
    ]);

    $sungai = Market::create([
        'name' => 'Sungai',
        'code' => 'SG01',
    ]);

    $retribution = Retribution::create([
        'market_id' => $karimata->id,
        'recorded_by' => $user->id,
        'jenis_retribusi' => 'Kebersihan',
        'retribution_date' => '2026-07-29',
        'amount' => 5000,
        'payment_method' => 'Tunai',
        'status' => 'draft',
    ]);

    RetributionItem::create([
        'retribution_id' => $retribution->id,
        'jenis_retribusi' => 'kios',
        'quantity' => 1,
        'amount' => 250000,
    ]);

    RetributionItem::create([
        'retribution_id' => $retribution->id,
        'jenis_retribusi' => 'los',
        'quantity' => 1,
        'amount' => 150000,
    ]);

    Retribution::create([
        'market_id' => $sungai->id,
        'recorded_by' => $user->id,
        'jenis_retribusi' => 'Kebersihan',
        'retribution_date' => '2026-07-29',
        'amount' => 75000,
        'payment_method' => 'Tunai',
        'status' => 'draft',
    ]);

    $aggregate = app(AggregateService::class);
    $marketSummary = $aggregate->getMarketSummary('2026-07-29');
    $dailyRecap = $aggregate->getDailyRecap('2026-07-29');

    $export = new RekapHarianExport('2026-07-29');
    $rows = $export->collection();

    expect((float) $rows->sum('total_nominal'))->toBe($aggregate->getGrandTotal('2026-07-29'))
        ->and($rows->pluck('pasar')->all())->toBe($marketSummary->pluck('market')->all())
        ->and((float) $rows->firstWhere('pasar', 'Karimata')->total_nominal)->toBe($dailyRecap->firstWhere('market', 'Karimata')['total'])
        ->and((float) $rows->firstWhere('pasar', 'Sungai')->total_nominal)->toBe($dailyRecap->firstWhere('market', 'Sungai')['total'])
        ->and($rows->firstWhere('pasar', 'Karimata')->total_transaksi)->toBe(1)
        ->and($rows->firstWhere('pasar', 'Sungai')->total_transaksi)->toBe(1);
});

test('eret template service maps aggregated recap values to exported worksheet', function () {
    $user = User::factory()->create();

    $market = Market::create([
        'name' => 'Karimata',
        'code' => 'KR01',
    ]);

    $retribution = Retribution::create([
        'market_id' => $market->id,
        'recorded_by' => $user->id,
        'jenis_retribusi' => 'Kebersihan',
        'retribution_date' => '2026-07-29',
        'amount' => 5000,
        'payment_method' => 'Tunai',
        'status' => 'draft',
    ]);

    RetributionItem::create([
        'retribution_id' => $retribution->id,
        'jenis_retribusi' => 'kios',
        'quantity' => 1,
        'amount' => 250000,
    ]);

    RetributionItem::create([
        'retribution_id' => $retribution->id,
        'jenis_retribusi' => 'los',
        'quantity' => 1,
        'amount' => 150000,
    ]);

    $spreadsheet = app(EretTemplateService::class)->generate('ERET', '2026-07-29');
    $sheet = $spreadsheet->getActiveSheet();

    expect((float) $sheet->getCell('B24')->getValue())->toBe(250000.0)
        ->and((float) $sheet->getCell('C24')->getValue())->toBe(150000.0);
});
