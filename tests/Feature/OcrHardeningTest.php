<?php

use App\Models\Market;
use App\Models\Retribution;
use App\Models\User;
use App\Services\GeminiService;
use App\Services\OcrService;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

/**
 * OCR HARDENING TEST — Sprint 8, Prioritas 2.
 *
 * Verifies the OcrService pipeline is resilient:
 *  - no unhandled exceptions on common failure scenarios
 *  - market_id is never null on success
 *  - graceful fallbacks for blurry/empty image, OCR failure, incomplete data,
 *    negative values, wrong format
 *  - informative logging
 */

/**
 * Helper: make an admin user for acting-as.
 */
function ocrAdmin(): User
{
    return User::factory()->create(['role' => UserRole::Admin]);
}

/**
 * Act as a user and return a fresh OcrService with a mocked Gemini response.
 *
 * @param  array|callable $geminiReturn The Gemini response, or a closure
 *                                      returning it (to simulate failures).
 */
function ocrServiceWith($geminiReturn): OcrService
{
    $mock = Mockery::mock(GeminiService::class);

    if (is_callable($geminiReturn)) {
        $mock->shouldReceive('ocr')->andThrow($geminiReturn());
    } else {
        $mock->shouldReceive('ocr')->andReturn($geminiReturn);
    }

    return new OcrService($mock, app(\App\Services\EretNumberService::class));
}

/**
 * Build a valid Gemini-like OCR response.
 */
function validGeminiResponse(array $overrides = []): array
{
    $data = array_merge([
        'petugas' => 'PONIMAN',
        'nomor_setor' => 'OCR-001',
        'tanggal' => '2026-07-21',
        'pasar' => 'Karimata',
        'kios' => 100000,
        'los' => 50000,
        'total' => 150000,
    ], $overrides);

    return [
        'candidates' => [
            [
                'content' => [
                    'parts' => [
                        ['text' => json_encode($data)],
                    ],
                ],
            ],
        ],
    ];
}

test('OCR sukses: data tervalidasi dan market_id terisi', function () {
    Market::factory()->create(['name' => 'Karimata']);

    $service = ocrServiceWith(validGeminiResponse());
    $result = $service->process(base64_encode('x'), 'image/png', 'ocr-temp/x.png');

    expect($result['success'])->toBeTrue()
        ->and($result['errors'])->toBeEmpty()
        ->and($result['data']['market_id'])->not->toBeNull()
        ->and($result['data']['nominal'])->toBe(150000.0)
        ->and($result['data']['tanggal'])->toBe('2026-07-21');
});

test('OCR gagal: exception Gemini tidak bocor, fallback aman', function () {
    $service = ocrServiceWith(fn () => new \Exception('Gemini down'));

    $result = $service->process(base64_encode('x'), 'image/png');

    expect($result['success'])->toBeFalse()
        ->and($result['errors'])->toContain('OCR gagal.')
        ->and(count($result['warnings']))->toBeGreaterThan(0);
});

test('OCR gambar kosong: tidak ada data yang diekstrak', function () {
    $service = ocrServiceWith(['candidates' => [['content' => ['parts' => [['text' => '']]]]]]);

    $result = $service->process(base64_encode('x'), 'image/png');

    expect($result['success'])->toBeFalse()
        ->and($result['errors'])->toContain('Gambar tidak dapat dibaca (kosong / buram).');
});

test('OCR data tidak lengkap: pasar kosong ditolak dengan error', function () {
    Market::factory()->create(['name' => 'Karimata']);

    $service = ocrServiceWith(validGeminiResponse(['pasar' => '']));
    $result = $service->process(base64_encode('x'), 'image/png');

    expect($result['success'])->toBeFalse()
        ->and($result['errors'])->toContain('Pasar wajib diisi.');
});

test('OCR nilai negatif: ditolak dengan error', function () {
    Market::factory()->create(['name' => 'Karimata']);

    $service = ocrServiceWith(validGeminiResponse(['total' => -5000]));
    $result = $service->process(base64_encode('x'), 'image/png');

    expect($result['success'])->toBeFalse();
});

test('OCR nominal nol: ditolak sebagai tidak valid', function () {
    Market::factory()->create(['name' => 'Karimata']);

    $service = ocrServiceWith(validGeminiResponse(['total' => 0]));
    $result = $service->process(base64_encode('x'), 'image/png');

    expect($result['success'])->toBeFalse()
        ->and($result['errors'])->toContain('Nominal tidak valid atau kosong.');
});

test('OCR tanggal tidak valid: fallback ke tanggal hari ini', function () {
    Market::factory()->create(['name' => 'Karimata']);

    $service = ocrServiceWith(validGeminiResponse(['tanggal' => 'not-a-date']));
    $result = $service->process(base64_encode('x'), 'image/png');

    expect($result['success'])->toBeTrue()
        ->and($result['data']['tanggal'])->toBe(now()->format('Y-m-d'))
        ->and(count($result['warnings']))->toBeGreaterThan(0);
});

test('OCR format salah: nominal format Indonesia dinormalisasi', function () {
    Market::factory()->create(['name' => 'Karimata']);

    $service = ocrServiceWith(validGeminiResponse(['total' => 'Rp 1.500,00']));
    $result = $service->process(base64_encode('x'), 'image/png');

    expect($result['success'])->toBeTrue()
        ->and($result['data']['nominal'])->toBe(1500.0);
});

test('OCR pasar tidak ditemukan di master data: error informatif', function () {
    // No market created.
    $service = ocrServiceWith(validGeminiResponse(['pasar' => 'Tidak Ada']));
    $result = $service->process(base64_encode('x'), 'image/png');

    expect($result['success'])->toBeFalse()
        ->and(count($result['errors']))->toBeGreaterThan(0)
        ->and(str_contains($result['errors'][0], 'tidak ditemukan'))->toBeTrue();
});

test('store: pasar tidak ditemukan menghasilkan error, bukan exception', function () {
    $admin = ocrAdmin();
    $this->actingAs($admin);

    $service = app(OcrService::class);
    $result = $service->store([
        'tanggal' => '2026-07-21',
        'pasar' => 'Tidak Ada',
        'jenis_retribusi' => 'Retribusi Harian',
        'nominal' => 1000,
    ]);

    expect($result['success'])->toBeFalse()
        ->and($result['retribution'])->toBeNull()
        ->and(count($result['errors']))->toBeGreaterThan(0);
});

test('store: transaksi valid disimpan dengan market_id terisi', function () {
    $admin = ocrAdmin();
    $this->actingAs($admin);

    $market = Market::factory()->create(['name' => 'Karimata']);

    $service = app(OcrService::class);
    $result = $service->store([
        'tanggal' => '2026-07-21',
        'pasar' => 'Karimata',
        'jenis_retribusi' => 'Retribusi Harian',
        'nominal' => 150000,
        'nomor_setor' => 'OCR-999',
    ]);

    expect($result['success'])->toBeTrue()
        ->and($result['retribution'])->not->toBeNull()
        ->and((int) $result['retribution']->market_id)->toBe((int) $market->id)
        ->and((float) $result['retribution']->amount)->toBe(150000.0)
        ->and(Retribution::where('nomor_setor', 'OCR-999')->exists())->toBeTrue();
});

test('store: nominal negatif ditolak validasi tanpa exception', function () {
    $admin = ocrAdmin();
    $this->actingAs($admin);

    Market::factory()->create(['name' => 'Karimata']);

    $service = app(OcrService::class);
    $result = $service->store([
        'tanggal' => '2026-07-21',
        'pasar' => 'Karimata',
        'jenis_retribusi' => 'Retribusi Harian',
        'nominal' => -100,
    ]);

    expect($result['success'])->toBeFalse()
        ->and($result['retribution'])->toBeNull();
});

test('HTTP OCR: proses berhasil dan redirect dengan hasil', function () {
    $admin = ocrAdmin();
    $this->actingAs($admin);

    Market::factory()->create(['name' => 'Karimata']);

    // Mock GeminiService at the container level.
    $mock = Mockery::mock(GeminiService::class);
    $mock->shouldReceive('ocr')->andReturn(validGeminiResponse());
    $this->app->instance(GeminiService::class, $mock);

    $response = $this->post(route('ocr.process'), [
        'image' => \Illuminate\Http\UploadedFile::fake()->image('tiket.jpg', 100, 100),
    ]);

    $response->assertSessionHas('ocr_result');
});

test('HTTP OCR: gambar wajib diisi', function () {
    $admin = ocrAdmin();
    $this->actingAs($admin);

    $this->post(route('ocr.process'), [])
        ->assertSessionHasErrors('image');
});

test('HTTP OCR: store berhasil redirect ke dashboard', function () {
    $admin = ocrAdmin();
    $this->actingAs($admin);

    Market::factory()->create(['name' => 'Karimata']);

    $this->post(route('ocr.store'), [
        'tanggal' => '2026-07-21',
        'pasar' => 'Karimata',
        'jenis_retribusi' => 'Retribusi Harian',
        'nominal' => 150000,
        'nomor_setor' => 'OCR-HTTP',
    ])->assertRedirect(route('dashboard'))
        ->assertSessionHas('success');

    expect(Retribution::where('nomor_setor', 'OCR-HTTP')->exists())->toBeTrue();
});
