<?php

use App\Models\Market;
use App\Models\Retribution;
use App\Models\User;
use App\Services\EretDashboardService;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

/**
 * UAT SPRINT CHECKPOINT — Skenario Pengujian Manual (User Acceptance Testing).
 *
 * Sprint ini dianggap selesai jika seluruh skenario manual berikut berhasil:
 *   1. Buka Dashboard → halaman tampil dengan tabel ERET Harian.
 *   2. Pilih beberapa pasar berbeda (Karimata 1, Waru Indah, Dargo, dll.)
 *      → dropdown Juru Pungut berubah sesuai pasar terpilih.
 *   3. Simpan data ERET menggunakan beberapa kombinasi (Tabel A manual &
 *      Tabel B eret).
 *   4. Data tersimpan dengan benar dan bisa dibuka kembali (di-reload dari
 *      sumber data yang sama — retributions + retribution_items).
 *   5. Tidak ada error baru di log (storage/logs/laravel.log) selama operasi.
 *
 * Test ini meniru langkah manual tersebut secara otomatis agar sprint dapat
 * ditandai sebagai checkpoint yang siap menjadi dasar pengembangan modul
 * selanjutnya.
 */

/**
 * Helper: membuat admin untuk login.
 */
function uatAdmin(): User
{
    return User::factory()->create(['role' => UserRole::Admin]);
}

/**
 * Helper: membuat petugas (Juru Pungut) yang terikat ke satu pasar.
 */
function uatPetugas(Market $market, array $attrs = []): User
{
    return User::factory()->create(array_merge([
        'role' => UserRole::Petugas,
        'market_id' => $market->id,
        'is_active' => true,
        'is_juru_pungut' => true,
    ], $attrs));
}

/**
 * UAT 1 — Buka Dashboard dan pastikan tabel ERET Harian tampil.
 */
test('UAT 1: buka dashboard dan tabel ERET harian tampil', function () {
    $admin = uatAdmin();
    $this->actingAs($admin);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dashboard Monitoring Dinas Perdagangan')
        ->assertSee('ERET — Pekerjaan Harian')
        ->assertSee('Tabel A — Retribusi Manual')
        ->assertSee('Tabel B — E-Retribusi')
        ->assertSee('TOTAL SELURUH PASAR');
});

/**
 * UAT 2 — Pilih beberapa pasar berbeda dan pastikan dropdown Juru Pungut
 * berubah sesuai pasar terpilih.
 */
test('UAT 2: dropdown juru pungut berubah sesuai pasar terpilih', function () {
    $admin = uatAdmin();
    $this->actingAs($admin);

    // Seed beberapa pasar berbeda (mirip MarketSeeder).
    $karimata = Market::create(['name' => 'KARIMATA 1', 'code' => 'KRM01', 'is_active' => true]);
    $waruIndah = Market::create(['name' => 'WARU INDAH', 'code' => 'WI01', 'is_active' => true]);
    $dargo = Market::create(['name' => 'DARGO', 'code' => 'DRG01', 'is_active' => true]);

    // Juru Pungut per pasar.
    $jpKarimata = [
        uatPetugas($karimata, ['name' => 'PONIMAN']),
        uatPetugas($karimata, ['name' => 'IKA WULANDARI']),
        uatPetugas($karimata, ['name' => 'FAHRUL RIZA WIDIYANTO']),
    ];
    $jpWaru = [
        uatPetugas($waruIndah, ['name' => 'INDRA SETIAWAN']),
        uatPetugas($waruIndah, ['name' => 'HENI LESTARI']),
        uatPetugas($waruIndah, ['name' => 'MUHAMAD BAYU MARJOKO']),
    ];
    $jpDargo = [
        uatPetugas($dargo, ['name' => 'MOCH WINARNO']),
        uatPetugas($dargo, ['name' => 'CHAVIDZ ANDI SAPUTRA']),
    ];

    // Kasus 1 — KARIMATA 1: hanya 3 JP milik karimata yang muncul.
    $this->getJson(route('markets.active-petugas', $karimata))
        ->assertOk()
        ->assertJsonCount(3)
        ->assertJsonFragment(['id' => $jpKarimata[0]->id, 'nama' => 'PONIMAN'])
        ->assertJsonFragment(['id' => $jpKarimata[1]->id, 'nama' => 'IKA WULANDARI'])
        ->assertJsonFragment(['id' => $jpKarimata[2]->id, 'nama' => 'FAHRUL RIZA WIDIYANTO'])
        ->assertJsonMissing(['id' => $jpWaru[0]->id])
        ->assertJsonMissing(['id' => $jpDargo[0]->id]);

    // Kasus 2 — WARU INDAH: dropdown berubah → hanya JP waru indah.
    $this->getJson(route('markets.active-petugas', $waruIndah))
        ->assertOk()
        ->assertJsonCount(3)
        ->assertJsonFragment(['id' => $jpWaru[0]->id, 'nama' => 'INDRA SETIAWAN'])
        ->assertJsonFragment(['id' => $jpWaru[1]->id, 'nama' => 'HENI LESTARI'])
        ->assertJsonMissing(['id' => $jpKarimata[0]->id])
        ->assertJsonMissing(['id' => $jpDargo[0]->id]);

    // Kasus 3 — DARGO: dropdown berubah → hanya JP dargo.
    $this->getJson(route('markets.active-petugas', $dargo))
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonFragment(['id' => $jpDargo[0]->id, 'nama' => 'MOCH WINARNO'])
        ->assertJsonFragment(['id' => $jpDargo[1]->id, 'nama' => 'CHAVIDZ ANDI SAPUTRA'])
        ->assertJsonMissing(['id' => $jpKarimata[0]->id])
        ->assertJsonMissing(['id' => $jpWaru[0]->id]);
});

/**
 * UAT 3 — Simpan data ERET menggunakan beberapa kombinasi
 * (Tabel A manual + Tabel B eret) lewat endpoint HTTP.
 */
test('UAT 3: simpan data ERET beberapa kombinasi (manual & eret) via HTTP', function () {
    $admin = uatAdmin();
    $this->actingAs($admin);

    $karimata = Market::create(['name' => 'KARIMATA 1', 'code' => 'KRM01', 'is_active' => true]);
    $waruIndah = Market::create(['name' => 'WARU INDAH', 'code' => 'WI01', 'is_active' => true]);
    $dargo = Market::create(['name' => 'DARGO', 'code' => 'DRG01', 'is_active' => true]);

    $jpKarimata = uatPetugas($karimata, ['name' => 'PONIMAN']);
    $jpWaru = uatPetugas($waruIndah, ['name' => 'INDRA SETIAWAN']);
    $jpDargo = uatPetugas($dargo, ['name' => 'MOCH WINARNO']);

    $tanggal = '2026-07-30';

    // Kombinasi 1 — Tabel A (manual): Karimata, kios + sampah.
    $this->postJson(route('dashboard.eret.save'), [
        'tanggal' => $tanggal,
        'rows' => [
            [
                'market_id' => $karimata->id,
                'petugas_id' => $jpKarimata->id,
                'nomor_setor' => 'UAT-M1',
                'entry_type' => 'manual',
                'kios' => 250000,
                'los' => 0,
                'dasaran' => 0,
                'mck' => 0,
                'sampah' => 50000,
                'listrik' => 0,
            ],
        ],
    ])->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('created', 1)
        ->assertJsonPath('total', 300000);

// Kombinasi 2 & 3 — Tabel B (eret): Waru Indah (kios+los+listrik) dan
    // Dargo (mck+sampah) disimpan dalam satu batch (satu sheet Tabel B).
    $this->postJson(route('dashboard.eret.save'), [
        'tanggal' => $tanggal,
        'rows' => [
            [
                'market_id' => $waruIndah->id,
                'petugas_id' => $jpWaru->id,
                'nomor_setor' => 'UAT-E2',
                'entry_type' => 'eret',
                'kios' => 150000,
                'los' => 75000,
                'dasaran' => 0,
                'mck' => 0,
                'sampah' => 0,
                'listrik' => 25000,
            ],
            [
                'market_id' => $dargo->id,
                'petugas_id' => $jpDargo->id,
                'nomor_setor' => 'UAT-E3',
                'entry_type' => 'eret',
                'kios' => 0,
                'los' => 0,
                'dasaran' => 0,
                'mck' => 30000,
                'sampah' => 20000,
                'listrik' => 0,
            ],
        ],
    ])->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('created', 2)
        ->assertJsonPath('total', 300000);

    // Semua data tersimpan di tabel sumber kebenaran tunggal.
    expect(Retribution::count())->toBe(3);

    $manual = Retribution::where('nomor_setor', 'UAT-M1')->first();
    expect($manual)->not->toBeNull()
        ->and((int) $manual->market_id)->toBe((int) $karimata->id)
        ->and((int) $manual->recorded_by)->toBe((int) $jpKarimata->id)
        ->and((float) $manual->amount)->toBe(300000.0)
        ->and($manual->entry_type)->toBe('manual');

    $eret2 = Retribution::where('nomor_setor', 'UAT-E2')->first();
    expect($eret2)->not->toBeNull()
        ->and((int) $eret2->market_id)->toBe((int) $waruIndah->id)
        ->and((float) $eret2->amount)->toBe(250000.0)
        ->and($eret2->entry_type)->toBe('eret');

    $eret3 = Retribution::where('nomor_setor', 'UAT-E3')->first();
    expect($eret3)->not->toBeNull()
        ->and((int) $eret3->market_id)->toBe((int) $dargo->id)
        ->and((float) $eret3->amount)->toBe(50000.0)
        ->and($eret3->entry_type)->toBe('eret');
});

/**
 * UAT 4 — Data yang tersimpan bisa dibuka kembali di Dashboard
 * (re-load dari sumber data yang sama).
 */
test('UAT 4: data tersimpan terbuka kembali di dashboard', function () {
    $admin = uatAdmin();
    $this->actingAs($admin);

    $karimata = Market::create(['name' => 'KARIMATA 1', 'code' => 'KRM01', 'is_active' => true]);
    $jpKarimata = uatPetugas($karimata, ['name' => 'PONIMAN']);

    $tanggal = '2026-07-30';

    // Simpan via service (seperti manual simpan).
    $result = app(EretDashboardService::class)->save($tanggal, [
        [
            'market_id' => $karimata->id,
            'petugas_id' => $jpKarimata->id,
            'nomor_setor' => 'UAT-REOPEN',
            'entry_type' => 'manual',
            'kios' => 100000,
            'los' => 50000,
            'dasaran' => 25000,
            'mck' => 0,
            'sampah' => 15000,
            'listrik' => 0,
        ],
    ]);

    expect($result['created'])->toBe(1)
        ->and($result['errors'])->toBeEmpty();

    // Buka kembali dashboard pada tanggal yang sama → data tampil.
    $this->get(route('dashboard', ['tanggal' => $tanggal]))
        ->assertOk()
        ->assertSee('KARIMATA 1')
        ->assertSee('UAT-REOPEN')
        ->assertSee('100000')
        ->assertSee('50000')
        ->assertSee('25000')
        ->assertSee('15000');

    // Pastikan data disimpan dengan jumlah item yang benar.
    $retribution = Retribution::where('nomor_setor', 'UAT-REOPEN')->first();
    expect($retribution)->not->toBeNull()
        ->and($retribution->items()->count())->toBeGreaterThanOrEqual(4);
});

/**
 * UAT 5 — Tidak ada error baru di log selama operasi skenario inti.
 *
 * CATATAN: kami hanya memastikan skenario inti (dashboard + save ERET)
 * tidak memicu error. Operasi ekspor ERET (WorkbookEngine) membutuhkan
 * template .xltx asli dan di luar lingkup checkpoint ini.
 */
test('UAT 5: operasi dashboard & save ERET tidak memicu error log', function () {
    // Siapkan log listener untuk menangkap level ERROR.
    $errors = [];
    Log::listen(function ($message) use (&$errors) {
        if (strtolower($message->level->name ?? '') === 'error') {
            $errors[] = (string) $message->message;
        }
    });

    $admin = uatAdmin();
    $this->actingAs($admin);

    $karimata = Market::create(['name' => 'KARIMATA 1', 'code' => 'KRM01', 'is_active' => true]);
    $jpKarimata = uatPetugas($karimata, ['name' => 'PONIMAN']);

    // Buka dashboard.
    $this->get(route('dashboard'))->assertOk();

    // Simpan data.
    $this->postJson(route('dashboard.eret.save'), [
        'tanggal' => '2026-07-30',
        'rows' => [
            [
                'market_id' => $karimata->id,
                'petugas_id' => $jpKarimata->id,
                'nomor_setor' => 'UAT-NOERR',
                'entry_type' => 'manual',
                'kios' => 100000,
                'los' => 0,
                'dasaran' => 0,
                'mck' => 0,
                'sampah' => 0,
                'listrik' => 0,
            ],
        ],
    ])->assertOk()->assertJsonPath('success', true);

    // Log listener menangkap error secara sinkron; pastikan tidak ada
    // error yang dilempar selama operasi inti.
    expect(count($errors))->toBe(0)
        ->and(Retribution::where('nomor_setor', 'UAT-NOERR')->exists())->toBeTrue();
});
