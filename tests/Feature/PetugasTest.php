<?php

use App\Models\Market;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createPetugasAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function createPetugasFor(Market $market, array $attrs = []): User
{
    return User::factory()->create(array_merge([
        'role' => UserRole::Petugas,
        'market_id' => $market->id,
        'is_active' => true,
        'is_juru_pungut' => true,
    ], $attrs));
}

test('petugas index lists petugas with search and badges', function () {
    $admin = createPetugasAdmin();
    $this->actingAs($admin);

    $market = Market::create(['name' => 'Karimata', 'code' => 'KR01']);
    createPetugasFor($market, ['name' => 'ANJAR SUSETYO', 'nip' => '196911202009011002', 'is_juru_pungut' => true, 'is_active' => true]);
    createPetugasFor($market, ['name' => 'SUTRIMAH', 'is_juru_pungut' => false, 'is_active' => false]);

    $this->get(route('petugas.index'))
        ->assertOk()
        ->assertSee('Master Petugas')
        ->assertSee('ANJAR SUSETYO')
        ->assertSee('196911202009011002')
        ->assertSee('Aktif')
        ->assertSee('Nonaktif');

    // Search by NIP
    $this->get(route('petugas.index', ['q' => '196911202009011002']))
        ->assertOk()
        ->assertSee('ANJAR SUSETYO')
        ->assertDontSee('SUTRIMAH');
});

test('petugas store creates a petugas with official fields', function () {
    $admin = createPetugasAdmin();
    $this->actingAs($admin);

    $market = Market::create(['name' => 'DARGO', 'code' => 'DRG01']);

    $this->post(route('petugas.store'), [
        'name' => 'MOCH WINARNO',
        'email' => 'moch.winarno@korwil.local',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'nip' => '197803282009011007',
        'rank' => 'Penata',
        'jabatan' => 'Juru Pungut Pasar Dargo',
        'market_id' => $market->id,
        'is_active' => '1',
        'is_juru_pungut' => '1',
    ])->assertRedirect(route('petugas.index'));

    $this->assertDatabaseHas('users', [
        'name' => 'MOCH WINARNO',
        'nip' => '197803282009011007',
        'market_id' => $market->id,
        'role' => UserRole::Petugas,
        'is_active' => true,
        'is_juru_pungut' => true,
    ]);
});

test('petugas update modifies petugas and preserves active/juru_pungut flags', function () {
    $admin = createPetugasAdmin();
    $this->actingAs($admin);

    $market = Market::create(['name' => 'LANGGAR', 'code' => 'LGR01']);
    $petugas = createPetugasFor($market, ['name' => 'KASMURI', 'is_juru_pungut' => false]);

    $this->put(route('petugas.update', $petugas), [
        'name' => 'KASMURI',
        'email' => $petugas->email,
        'market_id' => $market->id,
        'jabatan' => 'Tenaga Kebersihan Pasar Langgar',
        'is_active' => '1',
        'is_juru_pungut' => '1',
    ])->assertRedirect(route('petugas.index'));

    $petugas->refresh();
    expect($petugas->jabatan)->toBe('Tenaga Kebersihan Pasar Langgar')
        ->and($petugas->is_juru_pungut)->toBeTrue()
        ->and($petugas->is_active)->toBeTrue();
});

test('petugas destroy removes petugas', function () {
    $admin = createPetugasAdmin();
    $this->actingAs($admin);

    $market = Market::create(['name' => 'REJOMULYO', 'code' => 'RJM01']);
    $petugas = createPetugasFor($market);

    $this->delete(route('petugas.destroy', $petugas))
        ->assertRedirect(route('petugas.index'));

    $this->assertDatabaseMissing('users', ['id' => $petugas->id]);
});

test('active petugas endpoint returns only active juru pungut for the market', function () {
    $admin = createPetugasAdmin();
    $this->actingAs($admin);

    $market = Market::create(['name' => 'WARU INDAH', 'code' => 'WI01']);
    $otherMarket = Market::create(['name' => 'TAMBAK LOROK', 'code' => 'TBL01']);

    $jp = createPetugasFor($market, ['name' => 'INDRA SETIAWAN', 'nip' => '199307082025211010']);
    // Inactive juru pungut in the same market — must be excluded.
    createPetugasFor($market, ['name' => 'NONAKTIF', 'is_active' => false, 'is_juru_pungut' => true]);
    // Active but NOT juru pungut — must be excluded.
    createPetugasFor($market, ['name' => 'BUKAN JP', 'is_juru_pungut' => false]);
    // Active juru pungut in a different market — must be excluded.
    createPetugasFor($otherMarket, ['name' => 'PENYUSUP', 'is_juru_pungut' => true]);

    $response = $this->getJson(route('markets.active-petugas', $market))
        ->assertOk();

    $data = $response->json();
    expect($data)->toHaveCount(1)
        ->and($data[0]['id'])->toBe($jp->id)
        ->and($data[0]['nama'])->toBe('INDRA SETIAWAN')
        ->and($data[0]['nip'])->toBe('199307082025211010');
});

test('dashboard save rejects inactive petugas', function () {
    $admin = createPetugasAdmin();
    $this->actingAs($admin);

    $market = Market::create(['name' => 'DARGO', 'code' => 'DRG01']);
    $inactive = createPetugasFor($market, ['is_active' => false]);

    $this->postJson(route('dashboard.eret.save'), [
        'tanggal' => '2026-07-29',
        'rows' => [
            [
                'market_id' => $market->id,
                'petugas_id' => $inactive->id,
                'nomor_setor' => 'NS-JP',
                'kios' => 100000,
                'los' => 0,
                'dasaran' => 0,
                'mck' => 0,
                'sampah' => 0,
                'listrik' => 0,
            ],
        ],
    ])->assertOk()
        ->assertJsonPath('success', false)
        ->assertJsonPath('created', 0);
});

test('dashboard save rejects petugas not belonging to the selected market', function () {
    $admin = createPetugasAdmin();
    $this->actingAs($admin);

    $marketA = Market::create(['name' => 'REJOMULYO', 'code' => 'RJM01']);
    $marketB = Market::create(['name' => 'DARGO', 'code' => 'DRG01']);
    $petugasB = createPetugasFor($marketB);

    $this->postJson(route('dashboard.eret.save'), [
        'tanggal' => '2026-07-29',
        'rows' => [
            [
                'market_id' => $marketA->id,
                'petugas_id' => $petugasB->id,
                'nomor_setor' => 'NS-WRONG',
                'kios' => 100000,
                'los' => 0,
                'dasaran' => 0,
                'mck' => 0,
                'sampah' => 0,
                'listrik' => 0,
            ],
        ],
    ])->assertOk()
        ->assertJsonPath('success', false)
        ->assertJsonPath('created', 0);
});
