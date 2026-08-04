<?php

namespace App\Rules;

use App\Services\EretNumberService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * EretNumberRule — Reusable Laravel validation rule for ERET numeric fields.
 *
 * Delegates all parsing/validation logic to EretNumberService so the ERET
 * acceptance rules (Indonesian formatted numbers, Rp prefix, thousands dots,
 * decimal comma, up to 2 decimals) are defined in exactly ONE place.
 *
 * Usage in a FormRequest:
 *   'rows.*.kios' => ['nullable', new EretNumberRule],
 */
class EretNumberRule implements ValidationRule
{
    protected EretNumberService $numberService;

    /**
     * Laravel instantiates FormRequest rules with `new` (not the container),
     * so the EretNumberService dependency is resolved from the container here
     * when it is not explicitly injected. EretNumberService remains the single
     * source of truth for all ERET numeric parsing/validation.
     */
    public function __construct(?EretNumberService $numberService = null)
    {
        $this->numberService = $numberService ?? app(EretNumberService::class);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->numberService->isValid($value)) {
            $fail('Nilai harus berupa angka yang valid (contoh: 1000, 1.000, 1.000,50).');
        }
    }
}
