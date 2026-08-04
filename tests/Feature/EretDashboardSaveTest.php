<?php

use App\Models\Market;
use App\Models\Retribution;
use App\Models\RetributionItem;
use App\Models\User;
use App\Services\EretDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createEretDashboardUser(): User
{
    return User::factory()->create(['role' => 'admin']);
}

/**
 * The dashboard spreadsheet should persist rows directly into the
 * retributions + retribution_items tables (single source of truth).
 * Downstream modules (Retribusi, Verification, Bendel, Rekap) read
 * these same tables — no duplicate input, no sync layer.
 */
test('dashboard save creates retribution and items', function () {
    $user = createEretDashboardUser();
    $this->actingAs($user);

    $market = Market::create(['name' => 'Karimata', 'code' => 'KR01']);

    $result = app(EretDashboardService::class)->save('2026-07-29', [
        [
            'market_id' => $market->id,
            'petugas_id' => $user->id,
            'nomor_setor' => 'NS-100',
            'kios' => 250000,
            'los' => 0,
            'dasaran' => 0,
            'mck' => 0,
            'sampah' => 50000,
            'listrik' => 0,
        ],
    ]);

    expect($result['created'])->toBe(1)
        ->and($result['errors'])->toBeEmpty()
        ->and($result['total'])->toBe(300000.0);

$retribution = Retribution::where('nomor_setor', 'NS-100')->first();
    expect($retribution)->not->toBeNull()
        ->and($retribution->market_id)->toBe($market->id)
        ->and((float) $retribution->amount)->toBe(300000.0)
        ->and($retribution->status)->toBe('draft');

// Items stored with correct jenis_retribusi mapping
    $items = RetributionItem::where('retribution_id', $retribution->id)->get();
    expect($items)->toHaveCount(2)
        ->and((float) $items->firstWhere('jenis_retribusi', 'kios')->amount)->toBe(250000.0)
        ->and((float) $items->firstWhere('jenis_retribusi', 'kebersihan')->amount)->toBe(50000.0);
});

test('dashboard save updates existing retribution and replaces items', function () {
    $user = createEretDashboardUser();
    $this->actingAs($user);

    $market = Market::create(['name' => 'Karimata', 'code' => 'KR01']);

    // Existing retribution
    $retribution = Retribution::create([
        'market_id' => $market->id,
        'recorded_by' => $user->id,
        'jenis_retribusi' => 'Retribusi Harian',
        'retribution_date' => '2026-07-29',
        'amount' => 100000,
        'payment_method' => 'cash',
        'status' => 'draft',
        'nomor_setor' => 'NS-001',
    ]);
    RetributionItem::create([
        'retribution_id' => $retribution->id,
        'jenis_retribusi' => 'kios',
        'quantity' => 1,
        'amount' => 100000,
    ]);

    $result = app(EretDashboardService::class)->save('2026-07-29', [
        [
            'id' => $retribution->id,
            'market_id' => $market->id,
            'petugas_id' => $user->id,
            'nomor_setor' => 'NS-001',
            'kios' => 150000,
            'los' => 50000,
            'dasaran' => 0,
            'mck' => 0,
            'sampah' => 0,
            'listrik' => 0,
        ],
    ]);

    expect($result['updated'])->toBe(1)
        ->and($result['errors'])->toBeEmpty();

$retribution->refresh();
    expect((float) $retribution->amount)->toBe(200000.0);

    // Items replaced (only 2 now)
    $items = RetributionItem::where('retribution_id', $retribution->id)->get();
    expect($items)->toHaveCount(2)
        ->and((float) $items->firstWhere('jenis_retribusi', 'kios')->amount)->toBe(150000.0)
        ->and((float) $items->firstWhere('jenis_retribusi', 'los')->amount)->toBe(50000.0);
});

test('dashboard save rejects invalid market', function () {
    $user = createEretDashboardUser();
    $this->actingAs($user);

    $result = app(EretDashboardService::class)->save('2026-07-29', [
        [
            'market_id' => 999,
            'petugas_id' => $user->id,
            'nomor_setor' => 'NS-200',
            'kios' => 100000,
        ],
    ]);

    expect($result['created'])->toBe(0)
        ->and($result['errors'])->not->toBeEmpty()
        ->and($result['errors'][0]['field'])->toBe('market_id');
});

test('dashboard save rejects duplicate nomor setor within batch', function () {
    $user = createEretDashboardUser();
    $this->actingAs($user);

    $market = Market::create(['name' => 'Karimata', 'code' => 'KR01']);

    $result = app(EretDashboardService::class)->save('2026-07-29', [
        [
            'market_id' => $market->id,
            'petugas_id' => $user->id,
            'nomor_setor' => 'NS-300',
            'kios' => 100000,
        ],
        [
            'market_id' => $market->id,
            'petugas_id' => $user->id,
            'nomor_setor' => 'NS-300',
            'los' => 50000,
        ],
    ]);

    expect($result['created'])->toBe(1)
        ->and($result['errors'])->not->toBeEmpty()
        ->and($result['errors'][0]['field'])->toBe('nomor_setor');
});

test('dashboard save endpoint persists data via HTTP', function () {
    $user = createEretDashboardUser();
    $this->actingAs($user);

    $market = Market::create(['name' => 'Karimata', 'code' => 'KR01']);

    $this->postJson(route('dashboard.eret.save'), [
        'tanggal' => '2026-07-29',
        'rows' => [
            [
                'market_id' => $market->id,
                'petugas_id' => $user->id,
                'nomor_setor' => 'NS-400',
                'kios' => 100000,
                'los' => 0,
                'dasaran' => 0,
                'mck' => 0,
                'sampah' => 0,
                'listrik' => 0,
            ],
        ],
    ])->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('created', 1)
        ->assertJsonPath('total', 100000)
        ->assertJsonStructure([
            'success', 'message', 'created', 'updated', 'deleted', 'total', 'errors',
        ]);

    $this->assertDatabaseHas('retributions', [
        'nomor_setor' => 'NS-400',
        'amount' => 100000,
    ]);
});
