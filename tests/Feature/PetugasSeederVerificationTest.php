<?php

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Verification of the seeded Korwil Karimata personnel data.
 *
 * This replaces `php artisan tinker --execute` (which has a PowerShell/PsySH
 * parsing issue) with a non-interactive feature test that exercises the real
 * seeder against an in-memory SQLite database.
 */

// Seed the real DatabaseSeeder (MarketSeeder + PetugasSeeder) once for this file.
beforeEach(function () {
    $this->seed();
});

test('1. total number of users with role = petugas is 20', function () {
    $total = User::where('role', UserRole::Petugas)->count();

    expect($total)->toBe(20);
});

test('2. total active Juru Pungut (is_active + is_juru_pungut) is 11', function () {
    $activeJuruPungut = User::query()
        ->where('role', UserRole::Petugas)
        ->where('is_active', true)
        ->where('is_juru_pungut', true)
        ->count();

    expect($activeJuruPungut)->toBe(11);
});

test('3. number of Juru Pungut assigned to each market', function () {
    $byMarket = User::query()
        ->where('role', UserRole::Petugas)
        ->where('is_active', true)
        ->where('is_juru_pungut', true)
        ->get()
        ->groupBy(fn (User $u) => $u->market?->name)
        ->map->count()
        ->sortKeys()
        ->toArray();

    expect($byMarket)->toBe([
        'DARGO' => 2,
        'KARIMATA 1' => 3,
        'LANGGAR' => 1,
        'REJOMULYO' => 1,
        'TAMBAK LOROK' => 1,
        'WARU INDAH' => 3,
    ]);
});

test('4. official Korwil Karimata personnel list is present in the database', function () {
    $expectedNames = [
        'WIYANTO',
        'TOMMY HARSONO',
        'ANJAR SUSETYO',
        'NOOR AZIS',
        'MOCH WINARNO',
        'SUTRIMAH',
        'SUPARNO',
        'PONIMAN',
        'CHAVIDZ ANDI SAPUTRA',
        'INDRA SETIAWAN',
        'IKA WULANDARI',
        'CITRA YONIT DIAN RACHMAWATI, S.E.',
        'LINTANG SATRIA PUTRA RAMADHAN, S.H.',
        'MUHAMAD BAYU MARJOKO',
        'FAHRUL RIZA WIDIYANTO',
        'HENI LESTARI',
        'KASMURI',
        'HENGKY',
        'PURNOMO WIDODO',
        'PRINGGO SUTJAHYO TR',
    ];

    $present = User::whereIn('name', $expectedNames)->count();

    expect($present)->toBe(20);
});

test('5. selecting a market returns only active Juru Pungut for that market', function () {
    $market = App\Models\Market::where('name', 'KARIMATA 1')->firstOrFail();

    $active = User::activeJuruPungut($market->id)->get();

    // KARIMATA 1 has exactly 3 active Juru Pungut.
    expect($active)->toHaveCount(3);

    // Every returned petugas must belong to that market and be active + JP.
    foreach ($active as $jp) {
        expect((int) $jp->market_id)->toBe((int) $market->id)
            ->and($jp->is_active)->toBeTrue()
            ->and($jp->is_juru_pungut)->toBeTrue()
            ->and($jp->role)->toBe(UserRole::Petugas);
    }

    // No active Juru Pungut leaks in from any other market.
    $otherMarkets = App\Models\Market::where('id', '!=', $market->id)
        ->whereIn('name', $active->pluck('market.name'))
        ->count();
    expect($otherMarkets)->toBe(0);
});
