<?php

use App\Models\Market;
use App\Models\Retribution;
use App\Models\RetributionItem;
use App\Models\User;
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
