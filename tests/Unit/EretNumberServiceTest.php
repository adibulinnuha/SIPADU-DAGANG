<?php

use App\Services\EretNumberService;

/**
 * Unit tests for the single reusable ERET numeric parsing service.
 *
 * These cover the accepted/rejected inputs, normalization, edge cases, and
 * frontend/backend consistency (the behavior mirrored in eret-spreadsheet.js).
 */

function eretNumberService(): EretNumberService
{
    return app(EretNumberService::class);
}

// ── Accepted inputs ──────────────────────────────────────────────

test('accepts plain integer', function () {
    expect(eretNumberService()->isValid('1000'))->toBeTrue();
});

test('accepts Indonesian thousands separator', function () {
    expect(eretNumberService()->isValid('1.000'))->toBeTrue();
});

test('accepts decimal comma without grouping', function () {
    expect(eretNumberService()->isValid('1000,50'))->toBeTrue();
});

test('accepts grouped thousands with decimal comma', function () {
    expect(eretNumberService()->isValid('1.000,50'))->toBeTrue();
});

test('accepts Rp prefix with space', function () {
    expect(eretNumberService()->isValid('Rp 1.000'))->toBeTrue();
});

test('accepts lowercase rp prefix with space', function () {
    expect(eretNumberService()->isValid('rp 1.000'))->toBeTrue();
});

test('accepts uppercase RP prefix without space', function () {
    expect(eretNumberService()->isValid('RP1.000,50'))->toBeTrue();
});

test('accepts zero', function () {
    expect(eretNumberService()->isValid('0'))->toBeTrue();
});

test('accepts zero with decimal comma', function () {
    expect(eretNumberService()->isValid('0,00'))->toBeTrue();
});

test('accepts empty string', function () {
    expect(eretNumberService()->isValid(''))->toBeTrue();
});

// ── Rejected inputs ──────────────────────────────────────────────

test('rejects non-numeric text', function () {
    expect(eretNumberService()->isValid('abc'))->toBeFalse();
});

test('rejects trailing non-numeric text', function () {
    expect(eretNumberService()->isValid('12abc'))->toBeFalse();
});

test('rejects Rp prefix with non-numeric text', function () {
    expect(eretNumberService()->isValid('Rp abc'))->toBeFalse();
});

test('rejects double dot separator', function () {
    expect(eretNumberService()->isValid('1..000'))->toBeFalse();
});

test('rejects multiple comma separators', function () {
    expect(eretNumberService()->isValid('1,2,3'))->toBeFalse();
});

test('rejects malformed grouping', function () {
    expect(eretNumberService()->isValid('1.00'))->toBeFalse();
});

test('rejects more than 2 decimal places', function () {
    expect(eretNumberService()->isValid('1.000,123'))->toBeFalse();
});

test('rejects negative numbers', function () {
    expect(eretNumberService()->isValid('-1000'))->toBeFalse();
});

test('rejects mixed currency and invalid digits', function () {
    expect(eretNumberService()->isValid('Rp 1.00.0'))->toBeFalse();
});

// ── Normalization ────────────────────────────────────────────────

test('normalizes plain integer', function () {
    expect(eretNumberService()->normalize('1000'))->toBe(1000.0);
});

test('normalizes Indonesian thousands separator', function () {
    expect(eretNumberService()->normalize('1.000'))->toBe(1000.0);
});

test('normalizes decimal comma', function () {
    expect(eretNumberService()->normalize('1000,50'))->toBe(1000.5);
});

test('normalizes grouped thousands and decimal comma', function () {
    expect(eretNumberService()->normalize('1.000,50'))->toBe(1000.5);
});

test('normalizes Rp prefix with space', function () {
    expect(eretNumberService()->normalize('Rp 1.000'))->toBe(1000.0);
});

test('normalizes lowercase rp prefix', function () {
    expect(eretNumberService()->normalize('rp 1.000'))->toBe(1000.0);
});

test('normalizes uppercase RP prefix without space', function () {
    expect(eretNumberService()->normalize('RP1.000,50'))->toBe(1000.5);
});

test('normalizes zero', function () {
    expect(eretNumberService()->normalize('0'))->toBe(0.0);
});

test('normalizes zero with decimal comma', function () {
    expect(eretNumberService()->normalize('0,00'))->toBe(0.0);
});

test('normalizes empty string to null', function () {
    expect(eretNumberService()->normalize(''))->toBeNull();
});

test('normalizes null to null', function () {
    expect(eretNumberService()->normalize(null))->toBeNull();
});

test('normalizes numeric input to float', function () {
    expect(eretNumberService()->normalize(250000))->toBe(250000.0);
});

// ── Edge cases ──────────────────────────────────────────────────

test('accepts large grouped number', function () {
    expect(eretNumberService()->isValid('1.000.000.000'))->toBeTrue()
        ->and(eretNumberService()->normalize('1.000.000.000'))->toBe(1000000000.0);
});

test('accepts leading/trailing whitespace', function () {
    expect(eretNumberService()->isValid('  1000  '))->toBeTrue()
        ->and(eretNumberService()->normalize('  1000  '))->toBe(1000.0);
});

test('accepts Rp with surrounding whitespace', function () {
    expect(eretNumberService()->isValid('  Rp 1.000,50  '))->toBeTrue()
        ->and(eretNumberService()->normalize('  Rp 1.000,50  '))->toBe(1000.5);
});

test('rejects currency symbol placed after number', function () {
    expect(eretNumberService()->isValid('1000 Rp'))->toBeFalse();
});

test('rejects empty string with only whitespace', function () {
    expect(eretNumberService()->isValid('   '))->toBeFalse();
});

test('rejects string with only Rp', function () {
    expect(eretNumberService()->isValid('Rp'))->toBeFalse();
});

// ── Frontend/backend consistency ─────────────────────────────────

test('parseNumeric-equivalent behavior matches backend for accepted values', function () {
    // Mirrors the JS parseNumeric() in resources/js/eret-spreadsheet.js.
    $cases = [
        '1000' => 1000.0,
        '1.000' => 1000.0,
        '1000,50' => 1000.5,
        '1.000,50' => 1000.5,
        'Rp 1.000' => 1000.0,
        'rp 1.000' => 1000.0,
        'RP1.000,50' => 1000.5,
        '0' => 0.0,
        '0,00' => 0.0,
    ];

    foreach ($cases as $input => $expected) {
        expect(eretNumberService()->normalize($input))->toBe($expected);
    }
});
