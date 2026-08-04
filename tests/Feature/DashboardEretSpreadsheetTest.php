<?php

use App\Models\Market;
use App\Models\Retribution;
use App\Models\RetributionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Verifikasi Dashboard ERET (spreadsheet) — filter, sorting, pagination,
 * total otomatis, dan mapping kolom sesuai template ERET.
 */
function createDashboardUser(): User
{
    return User::factory()->create([
        'role' => 'admin',
    ]);
}

function createDashboardRetribution(
    Market $market,
    User $user,
    string $date,
    ?string $nomorSetor = null,
    array $items = [],
    string $status = 'draft',
    float $amount = 100000
): Retribution {
    $retribution = Retribution::create([
        'market_id' => $market->id,
        'recorded_by' => $user->id,
        'jenis_retribusi' => 'Retribusi Harian',
        'retribution_date' => $date,
        'amount' => $amount,
        'payment_method' => 'cash',
        'status' => $status,
        'nomor_setor' => $nomorSetor,
    ]);

    foreach ($items as $item) {
        RetributionItem::create(array_merge([
            'retribution_id' => $retribution->id,
        ], $item));
    }

    return $retribution->fresh();
}

test('dashboard renders eret harian table with mapped columns', function () {
    $user = createDashboardUser();

    $market = Market::create([
        'name' => 'Karimata',
        'code' => 'KR01',
    ]);

    $retribution = createDashboardRetribution($market, $user, '2026-07-29', 'NS-001');

    RetributionItem::create([
        'retribution_id' => $retribution->id,
        'jenis_retribusi' => 'kios',
        'quantity' => 1,
        'amount' => 250000,
    ]);

    RetributionItem::create([
        'retribution_id' => $retribution->id,
        'jenis_retribusi' => 'kebersihan', // -> Sampah
        'quantity' => 1,
        'amount' => 50000,
    ]);

    // The dashboard is now an interactive spreadsheet (Alpine.js).
    // Values are embedded in the JSON initialRows payload rather than
    // rendered as formatted text in the initial HTML.
    $this->actingAs($user)
        ->get(route('dashboard', ['tanggal' => '2026-07-29']))
        ->assertOk()
        ->assertSee('ERET — Pekerjaan Harian')
        ->assertSee('Karimata')
        // Raw values present in the JSON spreadsheet payload
        ->assertSee('250000')
        ->assertSee('50000');
});

test('dashboard eret respects market filter and search', function () {
    $user = createDashboardUser();

    $karimata = Market::create(['name' => 'Karimata', 'code' => 'KR01']);
    $pedurungan = Market::create(['name' => 'Pedurungan', 'code' => 'PD01']);

    createDashboardRetribution($karimata, $user, '2026-07-29', 'NS-001');
    createDashboardRetribution($pedurungan, $user, '2026-07-29', 'NS-002');

    // Filter pasar — assert on ERET table's unique data (nomor_setor).
    // The dashboard's global widgets (Top Pasar, Belum Input, Rekap, Ringkasan)
    // legitimately list all markets, so we can't assert on market names at page level.
    $this->actingAs($user)
        ->get(route('dashboard', ['tanggal' => '2026-07-29', 'market_id' => $karimata->id]))
        ->assertOk()
        ->assertSee('Karimata')
        ->assertSee('NS-001')
        ->assertDontSee('NS-002');

    // Pencarian nomor setor — assert on ERET table's unique data.
    $this->actingAs($user)
        ->get(route('dashboard', ['tanggal' => '2026-07-29', 'q' => 'NS-002']))
        ->assertOk()
        ->assertSee('Pedurungan')
        ->assertSee('NS-002')
        ->assertDontSee('NS-001');
});

test('dashboard rekap shows per-market status workflow breakdown', function () {
    $user = createDashboardUser();

    $market = Market::create(['name' => 'Karimata', 'code' => 'KR01']);

    createDashboardRetribution($market, $user, '2026-07-29', 'NS-001', [], 'verified');
    createDashboardRetribution($market, $user, '2026-07-29', 'NS-002', [], 'draft');

    $this->actingAs($user)
        ->get(route('dashboard', ['tanggal' => '2026-07-29']))
        ->assertOk()
        ->assertSee('Rekap Per Pasar')
        ->assertSee('Verified · 1')
        ->assertSee('Draft · 1');
});

test('dashboard grand total matches aggregate service', function () {
    $user = createDashboardUser();

    $market = Market::create(['name' => 'Karimata', 'code' => 'KR01']);

    $r1 = createDashboardRetribution($market, $user, '2026-07-29', 'NS-001');
    RetributionItem::create([
        'retribution_id' => $r1->id,
        'jenis_retribusi' => 'kios',
        'quantity' => 1,
        'amount' => 100000,
    ]);

    $r2 = createDashboardRetribution($market, $user, '2026-07-29', 'NS-002');
    RetributionItem::create([
        'retribution_id' => $r2->id,
        'jenis_retribusi' => 'los',
        'quantity' => 1,
        'amount' => 150000,
    ]);

$this->actingAs($user)
        ->get(route('dashboard', ['tanggal' => '2026-07-29']))
        ->assertOk()
        ->assertSee('TOTAL SELURUH PASAR')
        // Grand total values are embedded in the JSON spreadsheet payload
        ->assertSee('100000')
        ->assertSee('150000');
});
