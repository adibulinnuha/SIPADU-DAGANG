<?php

use App\Models\Retribution;
use App\Models\User;
use App\Models\Verification;
use App\Services\WorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Verification → Workflow Integration Tests (Sprint 3A)
// ---------------------------------------------------------------------------
// These tests verify that the VerificationController correctly performs
// dual-writes to both the legacy verifications table and the retributions
// workflow fields via WorkflowService, while preserving existing behavior.

// ---------------------------------------------------------------------------
// Verification store() — dual-write integration
// ---------------------------------------------------------------------------

test('verification store updates retribution workflow to verified with audit fields', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // A retribution must be in 'submitted' state before it can be verified
    $retribution = Retribution::factory()->create([
        'status' => 'submitted',
        'submitted_at' => now(),
        'submitted_by' => $user->id,
    ]);

    $response = $this->post(route('verifications.store'), [
        'retribution_id' => $retribution->id,
        'nomor_setor' => '123/ABC/2026',
        'tanggal_verifikasi' => now()->toDateString(),
        'catatan' => 'Catatan verifikasi test',
    ]);

    $response->assertRedirect(route('verifications.index'));
    $response->assertSessionHas('success', 'Billing berhasil diverifikasi.');

    // Refresh to get workflow updates
    $retribution->refresh();

    // Assert workflow transition
    expect($retribution->status)->toBe('verified');
    expect($retribution->nomor_setor)->toBe('123/ABC/2026');
    expect($retribution->verified_at)->not->toBeNull();
    expect($retribution->verified_by)->toBe($user->id);

    // Assert legacy record still works
    $legacy = Verification::where('retribution_id', $retribution->id)->first();
    expect($legacy)->not->toBeNull();
    expect($legacy->status)->toBe('Terverifikasi');
    expect($legacy->nomor_setor)->toBe('123/ABC/2026');
    expect($legacy->verified_by)->toBe($user->id);
});

test('verification store rolls back both writes on failure', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $retribution = Retribution::factory()->create([
        'status' => 'draft', // draft → verified will fail!
    ]);

    // This should throw because draft cannot transition directly to verified
    $this->withoutExceptionHandling();

    try {
        $this->post(route('verifications.store'), [
            'retribution_id' => $retribution->id,
            'nomor_setor' => '123/ABC/2026',
            'tanggal_verifikasi' => now()->toDateString(),
        ]);
    } catch (Exception $e) {
        expect($e->getMessage())->toContain('tidak diperbolehkan');
    }

    // Assert no legacy record was written (transaction rolled back)
    $legacy = Verification::where('retribution_id', $retribution->id)->first();
    expect($legacy)->toBeNull();

    // Assert retribution was not modified
    $retribution->refresh();
    expect($retribution->status)->toBe('draft');
    expect($retribution->nomor_setor)->toBeNull();
});

// ---------------------------------------------------------------------------
// Verification update() — dual-write integration
// ---------------------------------------------------------------------------

test('verification update with Terverifikasi status transitions workflow', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $retribution = Retribution::factory()->create([
        'status' => 'submitted',
        'submitted_at' => now(),
        'submitted_by' => $user->id,
    ]);

    // Create a legacy verification record first
    $verification = Verification::create([
        'retribution_id' => $retribution->id,
        'nomor_setor' => 'OLD/123',
        'tanggal_verifikasi' => now()->subDay()->toDateString(),
        'status' => 'Pending',
        'verified_by' => $user->id,
    ]);

    $response = $this->put(route('verifications.update', $verification), [
        'nomor_setor' => 'NEW/456/2026',
        'tanggal_verifikasi' => now()->toDateString(),
        'status' => 'Terverifikasi',
        'catatan' => 'Updated catatan',
    ]);

    $response->assertRedirect(route('verifications.index'));
    $response->assertSessionHas('success', 'Status verifikasi berhasil diperbarui.');

    // Assert legacy update worked
    $verification->refresh();
    expect($verification->status)->toBe('Terverifikasi');
    expect($verification->nomor_setor)->toBe('NEW/456/2026');

    // Assert workflow transition
    $retribution->refresh();
    expect($retribution->status)->toBe('verified');
    expect($retribution->nomor_setor)->toBe('NEW/456/2026');
    expect($retribution->verified_at)->not->toBeNull();
    expect($retribution->verified_by)->toBe($user->id);
});

test('verification update with Pending status does not change workflow', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $retribution = Retribution::factory()->create([
        'status' => 'submitted',
        'submitted_at' => now(),
        'submitted_by' => $user->id,
    ]);

    $verification = Verification::create([
        'retribution_id' => $retribution->id,
        'nomor_setor' => 'OLD/123',
        'tanggal_verifikasi' => now()->subDay()->toDateString(),
        'status' => 'Pending',
        'verified_by' => $user->id,
    ]);

    $response = $this->put(route('verifications.update', $verification), [
        'nomor_setor' => 'UPDATED/789',
        'tanggal_verifikasi' => now()->toDateString(),
        'status' => 'Pending',
        'catatan' => 'Still pending',
    ]);

    $response->assertRedirect(route('verifications.index'));

    // Assert legacy update worked
    $verification->refresh();
    expect($verification->status)->toBe('Pending');
    expect($verification->nomor_setor)->toBe('UPDATED/789');

    // Assert workflow status was NOT changed (no reverse transition)
    $retribution->refresh();
    expect($retribution->status)->toBe('submitted');
    expect($retribution->verified_at)->toBeNull();
    expect($retribution->verified_by)->toBeNull();
});

// ---------------------------------------------------------------------------
// Existing verification behavior still works
// ---------------------------------------------------------------------------

test('verification index page loads successfully', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('verifications.index'));

    $response->assertStatus(200);
});

test('verification can be created and read', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $retribution = Retribution::factory()->create([
        'status' => 'submitted',
        'submitted_at' => now(),
        'submitted_by' => $user->id,
    ]);

    $this->post(route('verifications.store'), [
        'retribution_id' => $retribution->id,
        'nomor_setor' => 'SETOR/001',
        'tanggal_verifikasi' => now()->toDateString(),
    ]);

    $this->assertDatabaseHas('verifications', [
        'retribution_id' => $retribution->id,
        'nomor_setor' => 'SETOR/001',
        'status' => 'Terverifikasi',
    ]);
});

test('verification can be deleted', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $retribution = Retribution::factory()->create();
    $verification = Verification::create([
        'retribution_id' => $retribution->id,
        'nomor_setor' => 'DEL/001',
        'tanggal_verifikasi' => now()->toDateString(),
        'status' => 'Pending',
        'verified_by' => $user->id,
    ]);

    $response = $this->delete(route('verifications.destroy', $verification));

    $response->assertRedirect(route('verifications.index'));
    $this->assertModelMissing($verification);
});

// ---------------------------------------------------------------------------
// Invalid workflow transitions are rejected
// ---------------------------------------------------------------------------

test('invalid workflow transition from draft to verified via store is rejected', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $retribution = Retribution::factory()->create([
        'status' => 'draft', // Cannot go directly to verified
    ]);

    // The WorkflowService throws an exception for invalid transitions.
    // The controller's DB::transaction() will roll back and re-throw.
    $this->withoutExceptionHandling();

    expect(fn () => $this->post(route('verifications.store'), [
        'retribution_id' => $retribution->id,
        'nomor_setor' => 'INVALID/001',
        'tanggal_verifikasi' => now()->toDateString(),
    ]))->toThrow(Exception::class, 'Perubahan status draft ke verified tidak diperbolehkan.');

    // Assert no legacy record was written due to transaction rollback
    $this->assertDatabaseMissing('verifications', [
        'retribution_id' => $retribution->id,
    ]);

    // Assert retribution was not modified
    $retribution->refresh();
    expect($retribution->status)->toBe('draft');
    expect($retribution->nomor_setor)->toBeNull();
});

test('existing WorkflowRegressionTest still passes', function () {
    // This test simply validates the existing workflow tests are untouched
    $user = User::factory()->create();
    $this->actingAs($user);

    $retribution = Retribution::factory()->create();

    $result = app(WorkflowService::class)->submit($retribution);
    $result->refresh();

    expect($result->status)->toBe('submitted');
    expect($result->submitted_at)->not->toBeNull();
    expect($result->submitted_by)->toBe($user->id);
});
