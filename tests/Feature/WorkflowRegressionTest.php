<?php

use App\Models\Retribution;
use App\Models\User;
use App\Services\WorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Workflow Regression Tests
// ---------------------------------------------------------------------------
// These tests verify that the workflow service correctly transitions
// Retribution records through the defined status flow and populates
// the corresponding audit fields (timestamp + user ID) on each transition.
// They reuse the existing RetributionFactory and WorkflowService without
// modifying any production code.

test('draft can be submitted with audit fields populated', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $retribution = Retribution::factory()->create();

    $result = app(WorkflowService::class)->submit($retribution);
    $result->refresh();

    expect($result->status)->toBe('submitted');
    expect($result->submitted_at)->not->toBeNull();
    expect($result->submitted_by)->toBe($user->id);
});

test('submitted can be verified with audit fields populated', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $retribution = Retribution::factory()->create([
        'status' => 'submitted',
        'submitted_at' => now(),
        'submitted_by' => $user->id,
    ]);

    $result = app(WorkflowService::class)->verify($retribution);
    $result->refresh();

    expect($result->status)->toBe('verified');
    expect($result->verified_at)->not->toBeNull();
    expect($result->verified_by)->toBe($user->id);
});

test('verified can be approved with audit fields populated', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $retribution = Retribution::factory()->create([
        'status' => 'verified',
        'submitted_at' => now(),
        'submitted_by' => $user->id,
        'verified_at' => now(),
        'verified_by' => $user->id,
    ]);

    $result = app(WorkflowService::class)->approve($retribution);
    $result->refresh();

    expect($result->status)->toBe('approved');
    expect($result->approved_at)->not->toBeNull();
    expect($result->approved_by)->toBe($user->id);
});

test('approved can be locked with audit fields populated', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $retribution = Retribution::factory()->create([
        'status' => 'approved',
        'submitted_at' => now(),
        'submitted_by' => $user->id,
        'verified_at' => now(),
        'verified_by' => $user->id,
        'approved_at' => now(),
        'approved_by' => $user->id,
    ]);

    $result = app(WorkflowService::class)->lock($retribution);
    $result->refresh();

    expect($result->status)->toBe('locked');
    expect($result->locked_at)->not->toBeNull();
    expect($result->locked_by)->toBe($user->id);
});

test('calling submit twice does not overwrite original audit timestamp', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $retribution = Retribution::factory()->create();

    $service = app(WorkflowService::class);

    // First transition: draft → submitted
    $result = $service->submit($retribution);
    $result->refresh();

    $originalSubmittedAt = $result->submitted_at;
    $originalSubmittedBy = $result->submitted_by;

    // Second call must be rejected because submitted → submitted is not a valid transition.
    // This is the guard that prevents overwriting original audit timestamps.
    expect(fn () => $service->submit($result))
        ->toThrow(Exception::class, 'Perubahan status submitted ke submitted tidak diperbolehkan.');

    // Verify original audit fields are preserved
    $result->refresh();
    expect($result->submitted_at->format('Y-m-d H:i:s'))->toBe($originalSubmittedAt->format('Y-m-d H:i:s'));
    expect($result->submitted_by)->toBe($originalSubmittedBy);
});

test('invalid transition throws exception', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $retribution = Retribution::factory()->create();

    expect(fn () => app(WorkflowService::class)->verify($retribution))
        ->toThrow(Exception::class, 'Perubahan status draft ke verified tidak diperbolehkan.');
});
