<?php

namespace App\Services;

use App\Models\Market;
use App\Models\Retribution;
use App\Models\RetributionItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * EretDashboardService — Business logic for the ERET Dashboard spreadsheet input.
 *
 * The dashboard is ONLY an interface (spreadsheet). All rows are stored directly
 * into `retributions` + `retribution_items` (SINGLE SOURCE OF TRUTH).
 *
 * No duplicate tables, no sync layer, no double input. Every downstream module
 * (Retribusi, Verification, Bendel, Rekap, Export Excel/PDF, OCR) reads the same
 * data this service writes.
 */
class EretDashboardService
{
    /**
     * Map dashboard column keys to retribution_item.jenis_retribusi values.
     * Sources are resolved via config('eret.dashboard_columns').
     */
    protected array $columnSources = [];

    public function __construct(
        protected EretNumberService $numberService
    ) {
        $this->columnSources = [];
        foreach (config('eret.dashboard_columns', []) as $key => $config) {
            // The first source is the canonical jenis_retribusi used for storage.
            $this->columnSources[$key] = (string) ($config['sources'][0] ?? $key);
        }
    }

/**
     * Validate & persist a batch of spreadsheet rows for a given date.
     *
     * Each row may carry an `entry_type` ('manual' | 'eret') identifying which
     * worksheet table it belongs to. When omitted it defaults to 'manual'.
     * Rows are stored directly into `retributions` + `retribution_items`.
     *
     * Delete/update logic is ALWAYS scoped by `entry_type` so that saving the
     * Manual table never removes E-Retribusi rows and vice versa.
     *
     * @param  string        $tanggal   Y-m-d
     * @param  array<int, array<string, mixed>> $rows
     * @return array{created:int, updated:int, deleted:int, total:float, errors:array}
     */
    public function save(string $tanggal, array $rows): array
    {
        $errors = [];
        $created = 0;
        $updated = 0;
        $deleted = 0;
        $grandTotal = 0.0;

        // Keep track of submitted ids to detect removals.
        $rowIds = [];

        // Determine the entry type to scope the delete below. A batch may only
        // belong to one table type (Manual or E-Retribusi). We read it from the
        // first submitted row (falling back to 'manual').
        $entryType = $this->normalizeEntryType($rows[0]['entry_type'] ?? null);

        DB::transaction(function () use ($tanggal, $rows, $entryType, &$errors, &$created, &$updated, &$deleted, &$grandTotal, &$rowIds) {
            $seenNomorSetor = [];

            foreach ($rows as $index => $row) {
                $rowErrors = $this->validateRow($row, $index);

                if (! empty($rowErrors)) {
                    $errors = array_merge($errors, $rowErrors);
                    continue;
                }

                $nomorSetor = $this->normalizeNomorSetor($row['nomor_setor'] ?? null);

                // Duplicate nomor_setor within the same batch.
                if ($nomorSetor !== null) {
                    $key = strtoupper($nomorSetor);
                    if (isset($seenNomorSetor[$key])) {
                        $errors[] = [
                            'row' => $index,
                            'field' => 'nomor_setor',
                            'message' => "Nomor setor '{$nomorSetor}' duplikat pada baris ini.",
                        ];
                        continue;
                    }
                    $seenNomorSetor[$key] = true;
                }

                $marketId = (int) $row['market_id'];
                $petugasId = ! empty($row['petugas_id'])
                    ? (int) $row['petugas_id']
                    : auth()->id();

                // Build item amounts from dashboard columns.
                $items = $this->buildItems($row);

                // Total = sum of all item amounts.
                $total = array_sum(array_map(fn ($i) => (float) $i['amount'], $items));

                $retributionId = ! empty($row['id']) ? (int) $row['id'] : null;

                if ($retributionId) {
                    $retribution = Retribution::find($retributionId);

                    if ($retribution) {
                        $retribution->update([
                            'market_id' => $marketId,
                            'jenis_retribusi' => 'Retribusi Harian',
                            'recorded_by' => $petugasId,
                            'retribution_date' => $tanggal,
                            'amount' => $total,
                            'nomor_setor' => $nomorSetor,
                            'payment_method' => $row['payment_method'] ?? 'cash',
                            'entry_type' => $entryType,
                        ]);

                        // Replace items.
                        $retribution->items()->delete();
                        foreach ($items as $item) {
                            RetributionItem::create(array_merge([
                                'retribution_id' => $retribution->id,
                            ], $item));
                        }

                        $rowIds[] = $retribution->id;
                        $updated++;
                        $grandTotal += $total;
                        continue;
                    }
                }

                // Create new retribution.
                $retribution = Retribution::create([
                    'market_id' => $marketId,
                    'jenis_retribusi' => 'Retribusi Harian',
                    'recorded_by' => $petugasId,
                    'retribution_date' => $tanggal,
                    'amount' => $total,
                    'nomor_setor' => $nomorSetor,
                    'payment_method' => $row['payment_method'] ?? 'cash',
                    'notes' => $row['notes'] ?? null,
                    'status' => 'draft',
                    'entry_type' => $entryType,
                ]);

                foreach ($items as $item) {
                    RetributionItem::create(array_merge([
                        'retribution_id' => $retribution->id,
                    ], $item));
                }

                $rowIds[] = $retribution->id;
                $created++;
                $grandTotal += $total;
            }

            // Remove retributions for this date that were NOT in the submitted
            // batch, are still in draft (not yet submitted/verified), and belong
            // to the SAME table type. This scoping is critical: saving Manual
            // must never delete E-Retribusi rows and vice versa.
            $deleted = Retribution::query()
                ->whereDate('retribution_date', $tanggal)
                ->where('status', 'draft')
                ->where('entry_type', $entryType)
                ->whereNotIn('id', $rowIds)
                ->delete();
        });

        return [
            'created' => $created,
            'updated' => $updated,
            'deleted' => $deleted,
            'total' => (float) $grandTotal,
            'errors' => $errors,
        ];
    }

    /**
     * Build retribution_items array from a dashboard row.
     *
     * @return array<int, array{jenis_retribusi:string, quantity:int, amount:float}>
     */
    protected function buildItems(array $row): array
    {
        $items = [];

        foreach ($this->columnSources as $columnKey => $jenis) {
            $amount = (float) $this->numberService->normalize($row[$columnKey] ?? 0);

            if ($amount > 0) {
                $items[] = [
                    'jenis_retribusi' => $jenis,
                    'quantity' => 1,
                    'amount' => $amount,
                ];
            }
        }

        return $items;
    }

    /**
     * Validate a single row. Returns a list of error messages.
     *
     * @return array<int, array{row:int, field:string, message:string}>
     */
    protected function validateRow(array $row, int $index): array
    {
        $errors = [];

        if (empty($row['market_id'])) {
            $errors[] = ['row' => $index, 'field' => 'market_id', 'message' => 'Pasar wajib diisi.'];
        } else {
            if (! Market::whereKey($row['market_id'])->exists()) {
                $errors[] = ['row' => $index, 'field' => 'market_id', 'message' => 'Pasar tidak valid.'];
            }
        }

        if (empty($row['petugas_id'])) {
            $errors[] = ['row' => $index, 'field' => 'petugas_id', 'message' => 'Petugas wajib diisi.'];
        } else {
            $petugas = User::whereKey($row['petugas_id'])->first();

            if (! $petugas) {
                $errors[] = ['row' => $index, 'field' => 'petugas_id', 'message' => 'Petugas tidak valid.'];
            } elseif (! $petugas->is_active) {
                $errors[] = ['row' => $index, 'field' => 'petugas_id', 'message' => 'Petugas tidak aktif.'];
            } elseif ($petugas->role === \App\UserRole::Petugas
                && (int) $petugas->market_id !== (int) $row['market_id']) {
                // Non-admin (role=petugas) recorders must belong to the selected
                // market. Admin users are allowed to create records for any
                // market (backward compatible with existing flows).
                $errors[] = ['row' => $index, 'field' => 'petugas_id', 'message' => 'Petugas bukan milik pasar terpilih.'];
            }
        }

        // Validate numeric columns.
        foreach ($this->columnSources as $columnKey => $jenis) {
            $value = $row[$columnKey] ?? 0;

            if ($value !== '' && $value !== null && ! $this->numberService->isValid($value)) {
                $errors[] = ['row' => $index, 'field' => $columnKey, 'message' => "Kolom '{$columnKey}' harus berupa angka."];
            }

            $normalized = $this->numberService->normalize($value);
            if ($normalized !== null && $normalized < 0) {
                $errors[] = ['row' => $index, 'field' => $columnKey, 'message' => "Kolom '{$columnKey}' tidak boleh negatif."];
            }
        }

        return $errors;
    }

    protected function normalizeNomorSetor(?string $nomorSetor): ?string
    {
        $nomorSetor = trim((string) $nomorSetor);

        return $nomorSetor === '' ? null : $nomorSetor;
    }

    /**
     * Normalize the entry_type value to one of the allowed worksheet groups.
     * Unknown/empty values fall back to 'manual' for backward compatibility.
     */
    protected function normalizeEntryType(?string $entryType): string
    {
        $entryType = strtolower(trim((string) $entryType));

        return in_array($entryType, ['manual', 'eret'], true)
            ? $entryType
            : 'manual';
    }
}
