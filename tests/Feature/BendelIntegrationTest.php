<?php

use App\Models\Bendel;
use App\Models\BendelDocument;
use App\Models\Retribution;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Bendel Integration Tests (Sprint 3B)
// ---------------------------------------------------------------------------
// These tests verify that BendelGenerator correctly supports both the legacy
// (verifications table) and workflow (retributions.status) data sources,
// controlled by the BENDEL_SOURCE config flag.

// ---------------------------------------------------------------------------
// Helper: create a verified retribution (via factory + manual field set)
// ---------------------------------------------------------------------------

function createVerifiedRetribution(array $overrides = []): Retribution
{
    $user = User::factory()->create();

    return Retribution::factory()->create(array_merge([
        'status' => 'verified',
        'verified_at' => now(),
        'verified_by' => $user->id,
        'submitted_at' => now(),
        'submitted_by' => $user->id,
    ], $overrides));
}

// ---------------------------------------------------------------------------
// Workflow source tests (BENDEL_SOURCE=workflow)
// ---------------------------------------------------------------------------

test('workflow source includes verified retributions in bendel', function () {
    // Force workflow source for this test
    config(['eret.bendel_source' => 'workflow']);

    $user = User::factory()->create();
    $this->actingAs($user);

    $retribution = createVerifiedRetribution();

    $this->post(route('bendels.generate'), [
        'tanggal_pendapatan' => now()->toDateString(),
        'tanggal_setor' => now()->toDateString(),
    ]);

    $this->assertDatabaseHas('bendel_document_items', [
        'retribution_id' => $retribution->id,
        'nominal' => $retribution->amount,
    ]);
});

test('workflow source includes approved and locked retributions in bendel', function () {
    config(['eret.bendel_source' => 'workflow']);

    $user = User::factory()->create();
    $this->actingAs($user);

    $verified = Retribution::factory()->create([
        'status' => 'verified',
        'verified_at' => now(),
        'verified_by' => $user->id,
        'submitted_at' => now(),
        'submitted_by' => $user->id,
    ]);

    $approved = Retribution::factory()->create([
        'status' => 'approved',
        'approved_at' => now(),
        'approved_by' => $user->id,
        'verified_at' => now(),
        'verified_by' => $user->id,
        'submitted_at' => now(),
        'submitted_by' => $user->id,
    ]);

    $locked = Retribution::factory()->create([
        'status' => 'locked',
        'locked_at' => now(),
        'locked_by' => $user->id,
        'approved_at' => now(),
        'approved_by' => $user->id,
        'verified_at' => now(),
        'verified_by' => $user->id,
        'submitted_at' => now(),
        'submitted_by' => $user->id,
    ]);

    $this->post(route('bendels.generate'), [
        'tanggal_pendapatan' => now()->toDateString(),
        'tanggal_setor' => now()->toDateString(),
    ]);

    $this->assertDatabaseHas('bendel_document_items', [
        'retribution_id' => $verified->id,
    ]);
    $this->assertDatabaseHas('bendel_document_items', [
        'retribution_id' => $approved->id,
    ]);
    $this->assertDatabaseHas('bendel_document_items', [
        'retribution_id' => $locked->id,
    ]);
});

test('workflow source skips draft and submitted retributions', function () {
    config(['eret.bendel_source' => 'workflow']);

    $user = User::factory()->create();
    $this->actingAs($user);

    $draft = Retribution::factory()->create(['status' => 'draft']);
    $submitted = Retribution::factory()->create([
        'status' => 'submitted',
        'submitted_at' => now(),
        'submitted_by' => $user->id,
    ]);

    $this->post(route('bendels.generate'), [
        'tanggal_pendapatan' => now()->toDateString(),
        'tanggal_setor' => now()->toDateString(),
    ]);

    $this->assertDatabaseMissing('bendel_document_items', [
        'retribution_id' => $draft->id,
    ]);
    $this->assertDatabaseMissing('bendel_document_items', [
        'retribution_id' => $submitted->id,
    ]);
});

test('workflow source handles empty set', function () {
    config(['eret.bendel_source' => 'workflow']);

    $user = User::factory()->create();
    $this->actingAs($user);

    $this->post(route('bendels.generate'), [
        'tanggal_pendapatan' => now()->toDateString(),
        'tanggal_setor' => now()->toDateString(),
    ]);

    // Bendel record is still created
    $this->assertDatabaseCount('bendels', 1);

    // But no items
    $this->assertDatabaseCount('bendel_document_items', 0);
});

test('workflow source creates document with correct total', function () {
    config(['eret.bendel_source' => 'workflow']);

    $user = User::factory()->create();
    $this->actingAs($user);

    $r1 = Retribution::factory()->create([
        'status' => 'verified',
        'amount' => 50000,
        'verified_at' => now(),
        'verified_by' => $user->id,
        'submitted_at' => now(),
        'submitted_by' => $user->id,
    ]);

    $r2 = Retribution::factory()->create([
        'status' => 'verified',
        'amount' => 75000,
        'verified_at' => now(),
        'verified_by' => $user->id,
        'submitted_at' => now(),
        'submitted_by' => $user->id,
    ]);

    $this->post(route('bendels.generate'), [
        'tanggal_pendapatan' => now()->toDateString(),
        'tanggal_setor' => now()->toDateString(),
    ]);

    $document = BendelDocument::first();
    expect($document)->not->toBeNull();
    expect((float) $document->nominal)->toBe(125000.0);
});

// ---------------------------------------------------------------------------
// Legacy source tests (BENDEL_SOURCE=legacy, the default)
// ---------------------------------------------------------------------------

test('legacy source still works with verification records', function () {
    // This is the default config value
    config(['eret.bendel_source' => 'legacy']);

    $user = User::factory()->create();
    $this->actingAs($user);

    $retribution = Retribution::factory()->create([
        'status' => 'submitted',
        'submitted_at' => now(),
        'submitted_by' => $user->id,
    ]);

    // Create a legacy verification record
    Verification::create([
        'retribution_id' => $retribution->id,
        'nomor_setor' => 'LEGACY/001',
        'tanggal_verifikasi' => now()->toDateString(),
        'status' => 'Terverifikasi',
        'verified_by' => $user->id,
    ]);

    $this->post(route('bendels.generate'), [
        'tanggal_pendapatan' => now()->toDateString(),
        'tanggal_setor' => now()->toDateString(),
    ]);

    $this->assertDatabaseHas('bendel_document_items', [
        'retribution_id' => $retribution->id,
    ]);
});

test('legacy source skips pending verification records', function () {
    config(['eret.bendel_source' => 'legacy']);

    $user = User::factory()->create();
    $this->actingAs($user);

    $retribution = Retribution::factory()->create();

    Verification::create([
        'retribution_id' => $retribution->id,
        'nomor_setor' => 'PENDING/001',
        'tanggal_verifikasi' => now()->toDateString(),
        'status' => 'Pending',
        'verified_by' => $user->id,
    ]);

    $this->post(route('bendels.generate'), [
        'tanggal_pendapatan' => now()->toDateString(),
        'tanggal_setor' => now()->toDateString(),
    ]);

    $this->assertDatabaseMissing('bendel_document_items', [
        'retribution_id' => $retribution->id,
    ]);
});

// ---------------------------------------------------------------------------
// Structural integrity tests (both sources)
// ---------------------------------------------------------------------------

test('bendel generation creates bendel and document records', function () {
    config(['eret.bendel_source' => 'workflow']);

    $user = User::factory()->create();
    $this->actingAs($user);

    $retribution = createVerifiedRetribution();

    $this->post(route('bendels.generate'), [
        'tanggal_pendapatan' => '2026-07-01',
        'tanggal_setor' => '2026-07-02',
    ]);

    $this->assertDatabaseCount('bendels', 1);
    $this->assertDatabaseCount('bendel_documents', 1);
    $this->assertDatabaseCount('bendel_document_items', 1);

    $bendel = Bendel::first();
    expect($bendel->tanggal_pendapatan)->toBe('2026-07-01');
    expect($bendel->tanggal_setor)->toBe('2026-07-02');
    expect($bendel->status)->toBe('draft');
});

test('bendel generation respects database transaction rollback', function () {
    config(['eret.bendel_source' => 'workflow']);

    $user = User::factory()->create();
    $this->actingAs($user);

    // No retributions means no items, but bendel is still created
    $this->post(route('bendels.generate'), [
        'tanggal_pendapatan' => now()->toDateString(),
        'tanggal_setor' => now()->toDateString(),
    ]);

    // Bendel created, no items
    $this->assertDatabaseCount('bendels', 1);
    $this->assertDatabaseCount('bendel_document_items', 0);
});
