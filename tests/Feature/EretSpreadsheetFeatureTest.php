<?php

use App\Models\Market;
use App\Models\Retribution;
use App\Models\RetributionItem;
use App\Models\User;
use App\Services\EretDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * ERET v2 Spreadsheet — Feature tests.
 *
 * Covers the Excel-like spreadsheet behaviors at the backend/service layer:
 *  - Row creation (add)
 *  - Row deletion (remove untracked draft rows)
 *  - Row duplication (save creates equivalent new rows)
 *  - Copy/paste (Excel-style numeric parsing, numeric formatting preserved)
 *  - Auto totals (grand total computed from items)
 *  - Save (HTTP endpoint persists batch)
 *  - Validation (numeric-only, non-negative, inline errors)
 *  - Large dataset performance (1,000+ rows)
 *
 * Because the project uses Pest + Laravel Feature Tests (no Dusk), these
 * tests assert on the single source of truth (`retributions` +
 * `retribution_items`) and the service/HTTP contract that the spreadsheet
 * drives.
 */

function createEretSpreadsheetUser(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function createEretSpreadsheetMarket(string $name = 'Karimata', string $code = 'KR01'): Market
{
    return Market::create(['name' => $name, 'code' => $code]);
}

/**
 * ADD ROW — saving a new row creates a retribution + items.
 */
test('add row persists a new retribution with items', function () {
    $user = createEretSpreadsheetUser();
    $this->actingAs($user);

    $market = createEretSpreadsheetMarket();

    $result = app(EretDashboardService::class)->save('2026-07-29', [
        [
            'market_id' => $market->id,
            'petugas_id' => $user->id,
            'nomor_setor' => 'NS-ADD-1',
            'kios' => 100000,
            'los' => 0,
            'dasaran' => 0,
            'mck' => 0,
            'sampah' => 0,
            'listrik' => 0,
        ],
    ]);

    expect($result['created'])->toBe(1)
        ->and($result['errors'])->toBeEmpty();

    $this->assertDatabaseHas('retributions', [
        'nomor_setor' => 'NS-ADD-1',
        'amount' => 100000,
        'status' => 'draft',
    ]);

    $this->assertDatabaseHas('retribution_items', [
        'jenis_retribusi' => 'kios',
        'amount' => 100000,
    ]);
});

/**
 * DELETE ROW — rows absent from the submitted batch (draft) are removed.
 */
test('delete row removes draft retributions not present in the batch', function () {
    $user = createEretSpreadsheetUser();
    $this->actingAs($user);

    $market = createEretSpreadsheetMarket();

    // Existing row that will be deleted (not in the new batch).
    $keep = Retribution::create([
        'market_id' => $market->id,
        'recorded_by' => $user->id,
        'jenis_retribusi' => 'Retribusi Harian',
        'retribution_date' => '2026-07-29',
        'amount' => 100000,
        'payment_method' => 'cash',
        'status' => 'draft',
        'nomor_setor' => 'NS-KEEP',
    ]);

    $remove = Retribution::create([
        'market_id' => $market->id,
        'recorded_by' => $user->id,
        'jenis_retribusi' => 'Retribusi Harian',
        'retribution_date' => '2026-07-29',
        'amount' => 50000,
        'payment_method' => 'cash',
        'status' => 'draft',
        'nomor_setor' => 'NS-REMOVE',
    ]);

    $result = app(EretDashboardService::class)->save('2026-07-29', [
        [
            'id' => $keep->id,
            'market_id' => $market->id,
            'petugas_id' => $user->id,
            'nomor_setor' => 'NS-KEEP',
            'kios' => 100000,
            'los' => 0,
            'dasaran' => 0,
            'mck' => 0,
            'sampah' => 0,
            'listrik' => 0,
        ],
    ]);

    expect($result['deleted'])->toBe(1)
        ->and($result['updated'])->toBe(1);

    $this->assertDatabaseHas('retributions', ['id' => $keep->id]);
    $this->assertDatabaseMissing('retributions', ['id' => $remove->id]);
});

/**
 * DUPLICATE ROW — two rows with identical values persist as two records.
 */
test('duplicate row persists two independent retributions', function () {
    $user = createEretSpreadsheetUser();
    $this->actingAs($user);

    $market = createEretSpreadsheetMarket();

    $result = app(EretDashboardService::class)->save('2026-07-29', [
        [
            'market_id' => $market->id,
            'petugas_id' => $user->id,
            'nomor_setor' => 'NS-DUP-A',
            'kios' => 250000,
            'los' => 0,
            'dasaran' => 0,
            'mck' => 0,
            'sampah' => 0,
            'listrik' => 0,
        ],
        [
            'market_id' => $market->id,
            'petugas_id' => $user->id,
            'nomor_setor' => 'NS-DUP-B',
            'kios' => 250000,
            'los' => 0,
            'dasaran' => 0,
            'mck' => 0,
            'sampah' => 0,
            'listrik' => 0,
        ],
    ]);

    expect($result['created'])->toBe(2);

    $this->assertDatabaseHas('retributions', ['nomor_setor' => 'NS-DUP-A', 'amount' => 250000]);
    $this->assertDatabaseHas('retributions', ['nomor_setor' => 'NS-DUP-B', 'amount' => 250000]);
});

/**
 * COPY/PASTE — Excel-style numeric formatting is preserved on save.
 * Pasted values like "Rp 1.000,50" or "1,000" must normalize to proper numbers.
 */
test('copy paste preserves numeric formatting via excel-style parsing', function () {
    $user = createEretSpreadsheetUser();
    $this->actingAs($user);

    $market = createEretSpreadsheetMarket();

    $result = app(EretDashboardService::class)->save('2026-07-29', [
        [
'market_id' => $market->id,
            'petugas_id' => $user->id,
            'nomor_setor' => 'NS-PASTE-1',
            // Indonesian/Excel formatted values pasted from the clipboard.
            'kios' => 'Rp 1.000,50',
            'los' => '1.000',
            'dasaran' => '500,25',
            'mck' => 0,
            'sampah' => 0,
            'listrik' => 0,
        ],
    ]);

    expect($result['errors'])->toBeEmpty()
        ->and($result['created'])->toBe(1);

    $retribution = Retribution::where('nomor_setor', 'NS-PASTE-1')->first();
    expect($retribution)->not->toBeNull();

    // 1000.50 + 1000 + 500.25 = 2500.75
    expect((float) $retribution->amount)->toBe(2500.75);

    $this->assertDatabaseHas('retribution_items', [
        'jenis_retribusi' => 'kios',
        'amount' => 1000.50,
    ]);
    $this->assertDatabaseHas('retribution_items', [
        'jenis_retribusi' => 'dasaran_terbuka',
        'amount' => 500.25,
    ]);
});

/**
 * AUTO TOTALS — the service computes grand total from item amounts.
 */
test('auto totals compute grand total from all items', function () {
    $user = createEretSpreadsheetUser();
    $this->actingAs($user);

    $market = createEretSpreadsheetMarket();

    $result = app(EretDashboardService::class)->save('2026-07-29', [
        [
            'market_id' => $market->id,
            'petugas_id' => $user->id,
            'nomor_setor' => 'NS-TOT-1',
            'kios' => 100000,
            'los' => 50000,
            'dasaran' => 0,
            'mck' => 25000,
            'sampah' => 0,
            'listrik' => 10000,
        ],
        [
            'market_id' => $market->id,
            'petugas_id' => $user->id,
            'nomor_setor' => 'NS-TOT-2',
            'kios' => 200000,
            'los' => 0,
            'dasaran' => 0,
            'mck' => 0,
            'sampah' => 0,
            'listrik' => 0,
        ],
    ]);

    // 185000 + 200000 = 385000
    expect((float) $result['total'])->toBe(385000.0);

    $this->assertDatabaseHas('retributions', ['nomor_setor' => 'NS-TOT-1', 'amount' => 185000]);
    $this->assertDatabaseHas('retributions', ['nomor_setor' => 'NS-TOT-2', 'amount' => 200000]);
});

/**
 * SAVE — HTTP endpoint persists the entire batch.
 */
test('save endpoint persists batch via HTTP with totals', function () {
    $user = createEretSpreadsheetUser();
    $this->actingAs($user);

    $market = createEretSpreadsheetMarket();

    $this->postJson(route('dashboard.eret.save'), [
        'tanggal' => '2026-07-29',
        'rows' => [
            [
                'market_id' => $market->id,
                'petugas_id' => $user->id,
                'nomor_setor' => 'NS-HTTP-1',
                'kios' => 300000,
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
        ->assertJsonPath('total', 300000)
        ->assertJsonStructure([
            'success', 'message', 'created', 'updated', 'deleted', 'total', 'errors',
        ]);

    $this->assertDatabaseHas('retributions', [
        'nomor_setor' => 'NS-HTTP-1',
        'amount' => 300000,
    ]);
});

/**
 * VALIDATION — rejects non-numeric and negative values.
 */
test('validation rejects non-numeric and negative values', function () {
    $user = createEretSpreadsheetUser();
    $this->actingAs($user);

    $market = createEretSpreadsheetMarket();

    $result = app(EretDashboardService::class)->save('2026-07-29', [
        [
            'market_id' => $market->id,
            'petugas_id' => $user->id,
            'nomor_setor' => 'NS-INV-1',
            'kios' => 'abc',
            'los' => -10,
            'dasaran' => 0,
            'mck' => 0,
            'sampah' => 0,
            'listrik' => 0,
        ],
    ]);

    expect($result['created'])->toBe(0)
        ->and($result['errors'])->not->toBeEmpty();

    $fields = array_column($result['errors'], 'field');
    expect($fields)->toContain('kios')
        ->and($fields)->toContain('los');
});

/**
 * VALIDATION — HTTP validation rejects non-numeric payload.
 */
test('http validation rejects invalid numeric payload', function () {
    $user = createEretSpreadsheetUser();
    $this->actingAs($user);

    $market = createEretSpreadsheetMarket();

    $this->postJson(route('dashboard.eret.save'), [
        'tanggal' => '2026-07-29',
        'rows' => [
            [
                'market_id' => $market->id,
                'petugas_id' => $user->id,
                'nomor_setor' => 'NS-HTTP-INV',
                'kios' => 'not-a-number',
                'los' => 0,
                'dasaran' => 0,
                'mck' => 0,
                'sampah' => 0,
                'listrik' => 0,
            ],
        ],
    ])->assertStatus(422);
});

/**
 * VALIDATION — inline validation requires market to be present.
 */
test('validation requires market to be selected', function () {
    $user = createEretSpreadsheetUser();
    $this->actingAs($user);

    $result = app(EretDashboardService::class)->save('2026-07-29', [
        [
            'market_id' => '',
            'petugas_id' => $user->id,
            'nomor_setor' => 'NS-NOMARKET',
            'kios' => 100000,
            'los' => 0,
            'dasaran' => 0,
            'mck' => 0,
            'sampah' => 0,
            'listrik' => 0,
        ],
    ]);

    expect($result['created'])->toBe(0)
        ->and($result['errors'][0]['field'])->toBe('market_id');
});

/**
 * LARGE DATASET PERFORMANCE — 1,000 rows save without errors and correct totals.
 */
test('large dataset of 1000 rows saves correctly with accurate totals', function () {
    $user = createEretSpreadsheetUser();
    $this->actingAs($user);

    $market = createEretSpreadsheetMarket();

    $rows = [];
    for ($i = 1; $i <= 1000; $i++) {
        $rows[] = [
            'market_id' => $market->id,
            'petugas_id' => $user->id,
            'nomor_setor' => 'NS-BULK-' . $i,
            'kios' => 1000,
            'los' => 2000,
            'dasaran' => 0,
            'mck' => 0,
            'sampah' => 0,
            'listrik' => 0,
        ];
    }

    $start = microtime(true);
    $result = app(EretDashboardService::class)->save('2026-07-29', $rows);
    $elapsed = microtime(true) - $start;

    expect($result['created'])->toBe(1000)
        ->and($result['errors'])->toBeEmpty()
        // 1000 * 3000 = 3,000,000
        ->and((float) $result['total'])->toBe(3000000.0);

    // Each row totals 3000.
    $this->assertDatabaseCount('retributions', 1000);
    $this->assertDatabaseCount('retribution_items', 2000);

    // Performance sanity: bulk save should complete in a reasonable time.
    expect($elapsed)->toBeLessThan(10.0);
});

/**
 * LARGE DATASET — HTTP endpoint handles 1,000 rows.
 */
test('large dataset endpoint saves 1000 rows via HTTP', function () {
    $user = createEretSpreadsheetUser();
    $this->actingAs($user);

    $market = createEretSpreadsheetMarket();

    $rows = [];
    for ($i = 1; $i <= 1000; $i++) {
        $rows[] = [
            'market_id' => $market->id,
            'petugas_id' => $user->id,
            'nomor_setor' => 'NS-HTTP-BULK-' . $i,
            'kios' => 500,
            'los' => 0,
            'dasaran' => 0,
            'mck' => 0,
            'sampah' => 0,
            'listrik' => 0,
        ];
    }

    $this->postJson(route('dashboard.eret.save'), [
        'tanggal' => '2026-07-29',
        'rows' => $rows,
    ])->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('created', 1000)
        ->assertJsonPath('total', 500000);

    $this->assertDatabaseCount('retributions', 1000);
});
