<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * ValidationReport — Validation Report Service for ERET Engine V1.2
 *
 * Generates structured validation reports after workbook export.
 * Tracks per-market status, warnings, errors, and summary statistics.
 *
 * Report format:
 *   ✔ REJOMULYO → OK
 *   ✔ TAMBAK LOROK → OK
 *   ✔ DARGO (Manual) → OK
 *   ✔ DARGO (E-Retribusi) → OK
 *   ⚠ Pasar Tidak Ada → NOT FOUND
 *
 *   Summary:
 *     Workbook: Valid
 *     Total Warnings: 1
 *     Total Errors: 0
 */
class ValidationReport
{
    /** @var array<int, array{market: string, group: string, row: int|null, status: string, message: string}> */
    protected array $entries = [];

    protected int $warnings = 0;
    protected int $errors = 0;
    protected string $workbookStatus = 'Valid';
    protected ?string $sheetName = null;
    protected ?string $date = null;

    // -----------------------------------------------------------------------
    //  Builders
    // -----------------------------------------------------------------------

    /**
     * Add a successful market mapping entry.
     */
    public function addSuccess(string $market, string $group, ?int $row = null): self
    {
        $this->entries[] = [
            'market' => $market,
            'group' => $group,
            'row' => $row,
            'status' => 'OK',
            'message' => "✔ {$market} ({$group}) → OK",
        ];

        return $this;
    }

    /**
     * Add a warning entry (e.g., market not found).
     */
    public function addWarning(string $market, string $message): self
    {
        $this->warnings++;
        $this->workbookStatus = 'Valid (with warnings)';

        $this->entries[] = [
            'market' => $market,
            'group' => '-',
            'row' => null,
            'status' => 'WARNING',
            'message' => "⚠ {$market} → {$message}",
        ];

        return $this;
    }

    /**
     * Add an error entry.
     */
    public function addError(string $market, string $message): self
    {
        $this->errors++;
        $this->workbookStatus = 'Invalid';

        $this->entries[] = [
            'market' => $market,
            'group' => '-',
            'row' => null,
            'status' => 'ERROR',
            'message' => "✘ {$market} → {$message}",
        ];

        return $this;
    }

    /**
     * Set the sheet name for context.
     */
    public function setSheet(string $sheetName): self
    {
        $this->sheetName = $sheetName;

        return $this;
    }

    /**
     * Set the date for context.
     */
    public function setDate(string $date): self
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Merge another report into this one.
     */
    public function merge(ValidationReport $other): self
    {
        $this->entries = array_merge($this->entries, $other->entries);
        $this->warnings += $other->warnings;
        $this->errors += $other->errors;

        if ($other->errors > 0) {
            $this->workbookStatus = 'Invalid';
        } elseif ($other->warnings > 0 && $this->workbookStatus === 'Valid') {
            $this->workbookStatus = 'Valid (with warnings)';
        }

        return $this;
    }

    // -----------------------------------------------------------------------
    //  Accessors
    // -----------------------------------------------------------------------

    /**
     * Get all report entries.
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    /**
     * Get entries filtered by status.
     */
    public function getEntriesByStatus(string $status): array
    {
        return array_values(
            array_filter($this->entries, fn ($e) => $e['status'] === strtoupper($status))
        );
    }

    /**
     * Get warning count.
     */
    public function getWarningCount(): int
    {
        return $this->warnings;
    }

    /**
     * Get error count.
     */
    public function getErrorCount(): int
    {
        return $this->errors;
    }

    /**
     * Get success count.
     */
    public function getSuccessCount(): int
    {
        return count($this->entries) - $this->warnings - $this->errors;
    }

    /**
     * Get total entry count.
     */
    public function getTotalCount(): int
    {
        return count($this->entries);
    }

    /**
     * Get workbook status string.
     */
    public function getWorkbookStatus(): string
    {
        return $this->workbookStatus;
    }

    /**
     * Check if workbook is valid (no errors).
     */
    public function isValid(): bool
    {
        return $this->errors === 0;
    }

    /**
     * Get warnings list.
     */
    public function getWarnings(): array
    {
        return $this->getEntriesByStatus('WARNING');
    }

    /**
     * Get errors list.
     */
    public function getErrors(): array
    {
        return $this->getEntriesByStatus('ERROR');
    }

    // -----------------------------------------------------------------------
    //  Output
    // -----------------------------------------------------------------------

    /**
     * Generate a human-readable report string.
     */
    public function toText(): string
    {
        $lines = [];
        $lines[] = str_repeat('=', 60);
        $lines[] = 'VALIDATION REPORT';
        $lines[] = str_repeat('=', 60);

        if ($this->date) {
            $lines[] = "Date: {$this->date}";
        }
        if ($this->sheetName) {
            $lines[] = "Sheet: {$this->sheetName}";
        }
        $lines[] = '';

        // Per-market entries
        foreach ($this->entries as $entry) {
            $lines[] = $entry['message'];
        }

        $lines[] = '';
        $lines[] = str_repeat('-', 60);
        $lines[] = 'SUMMARY';
        $lines[] = str_repeat('-', 60);
        $lines[] = "Workbook: {$this->workbookStatus}";
        $lines[] = "Total Markets: {$this->getTotalCount()}";
        $lines[] = "  ✔ Success: {$this->getSuccessCount()}";
        $lines[] = "  ⚠ Warnings: {$this->warnings}";
        $lines[] = "  ✘ Errors: {$this->errors}";
        $lines[] = str_repeat('=', 60);

        return implode(PHP_EOL, $lines);
    }

    /**
     * Generate a structured array report (for JSON export).
     */
    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'sheet' => $this->sheetName,
            'summary' => [
                'workbook_status' => $this->workbookStatus,
                'total_markets' => $this->getTotalCount(),
                'success' => $this->getSuccessCount(),
                'warnings' => $this->warnings,
                'errors' => $this->errors,
                'is_valid' => $this->isValid(),
            ],
            'entries' => $this->entries,
            'warnings_list' => $this->getWarnings(),
            'errors_list' => $this->getErrors(),
        ];
    }

    /**
     * Log the report using Laravel's logging system.
     */
    public function log(): void
    {
        Log::info('ValidationReport: ' . $this->toText());

        if ($this->warnings > 0) {
            foreach ($this->getWarnings() as $warning) {
                Log::warning($warning['message']);
            }
        }

        if ($this->errors > 0) {
            foreach ($this->getErrors() as $error) {
                Log::error($error['message']);
            }
        }
    }
}

