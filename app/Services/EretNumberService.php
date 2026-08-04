<?php

namespace App\Services;

/**
 * EretNumberService — Single source of truth for ERET numeric parsing,
 * validation and normalization.
 *
 * The ERET spreadsheet must accept Indonesian-formatted numeric input typed by
 * users or pasted directly from Excel (e.g. "Rp 1.000,50", "1.000", "1000,50").
 *
 * Validation happens in two steps:
 *  1. isValid()  — regex match against an allowed currency/number format.
 *  2. normalize() — convert to a canonical decimal (strip "Rp", spaces,
 *                   thousand separators, convert comma to decimal point).
 *
 * All ERET numeric fields (Form Request, DashboardService, OCR, export, etc.)
 * MUST use this service so the parsing logic is never duplicated.
 */
class EretNumberService
{
    /**
     * Allowed canonical Indonesian currency/number format.
     *
     * Rules:
     *  - Optional "Rp" / "rp" / "RP" prefix (case-insensitive), space optional.
     *  - Integer part with optional dot-thousands grouping (1.000, 1.000.000).
     *    When a dot is present, digits after the dot MUST be a group of 3
     *    (rejects "1.00", "1.0", "1.0000").
     *  - Optional comma decimal with up to 2 digits (1000,50 / 1.000,50 / 0,00).
     *  - No other characters allowed.
     *  - Negative numbers are NOT accepted.
     */
    public const PATTERN = '/^\s*(?:rp\s?)?(?:0|[1-9]\d{0,2}(?:\.\d{3})*|[1-9]\d*)(?:,\d{1,2})?\s*$/i';

    /**
     * Determine whether a value is an allowed ERET numeric string.
     *
     * Empty/null values are considered valid (the caller decides whether a
     * field is nullable). This is the regex-gate — no Laravel `numeric` rule.
     *
     * Note: Does NOT use is_numeric() because we must reject negative
     * numbers and only accept Indonesian-formatted input.
     */
    public function isValid(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return preg_match(self::PATTERN, (string) $value) === 1;
    }

    /**
     * Normalize an ERET numeric value into a canonical decimal float.
     *
     * Steps:
     *  - null / ''            -> null (empty)
     *  - already numeric      -> float
     *  - otherwise strip "Rp", spaces, remove dot-thousands, comma -> decimal dot
     *
* @return float|null The normalized value, or null when empty.
     */
    public function normalize(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $s = (string) $value;

        // Remove currency symbol and spaces (case-insensitive).
        $s = preg_replace('/[Rp\s]/i', '', $s);

        // Indonesian convention: dot = thousands separator, comma = decimal.
        $s = str_replace('.', '', $s);
        $s = str_replace(',', '.', $s);

        return (float) $s;
    }
}
