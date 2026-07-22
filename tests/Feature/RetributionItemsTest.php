<?php

use App\Models\Market;
use App\Models\Retribution;
use App\Models\RetributionItem;
use App\Models\User;
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

    expect($sheet->getCell('A1')->getValue())->toBe('Pasar')
        ->and($sheet->getCell('A2')->getValue())->toBe('Karimata')
        ->and((float) $sheet->getCell('B2')->getValue())->toBe(250000.0)
        ->and((float) $sheet->getCell('C2')->getValue())->toBe(150000.0)
        ->and((float) $sheet->getCell('H2')->getValue())->toBe(400000.0);
});
