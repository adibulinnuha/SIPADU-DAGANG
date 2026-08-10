<?php

namespace App\Services;

use App\Models\Market;
use App\Models\Retribution;
use App\Models\RetributionItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EretDashboardService
{
    protected array $columnSources = [];

    public function __construct(
        protected EretNumberService $numberService
    ) {
        foreach (config('eret.dashboard_columns', []) as $key => $config) {
            $this->columnSources[$key] = (string) ($config['sources'][0] ?? $key);
        }
    }

    public function save(string $tanggal, array $rows): array
    {
        $errors = [];
        $created = 0;
        $updated = 0;
        $deleted = 0;
        $grandTotal = 0.0;
        $rowIds = [];

        $entryType = $this->normalizeEntryType($rows[0]['entry_type'] ?? null);

        DB::transaction(function () use (
            $tanggal,
            $rows,
            $entryType,
            &$errors,
            &$created,
            &$updated,
            &$deleted,
            &$grandTotal,
            &$rowIds
        ) {
            $seenNomorSetor = [];

            foreach ($rows as $index => $row) {
                $rowErrors = $this->validateRow($row, $index + 1);

                if (! empty($rowErrors)) {
                    $errors = array_merge($errors, $rowErrors);
                    continue;
                }

                $nomorSetor = $this->normalizeNomorSetor($row['nomor_setor'] ?? null);

                if ($nomorSetor !== null) {
                    $key = strtoupper($nomorSetor);

                    if (isset($seenNomorSetor[$key])) {
                        $errors[] = [
                            'row' => $index + 1,
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

                $items = $this->buildItems($row);
                $total = array_sum(array_column($items, 'amount'));

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

                        $retribution->items()->delete();

                        foreach ($items as $item) {
                            RetributionItem::create([
                                'retribution_id' => $retribution->id,
                                'jenis_retribusi' => $item['jenis_retribusi'],
                                'quantity' => 1,
                                'amount' => $item['amount'],
                            ]);
                        }

                        $rowIds[] = $retribution->id;
                        $updated++;
                        $grandTotal += $total;

                        continue;
                    }
                }

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
                    RetributionItem::create([
                        'retribution_id' => $retribution->id,
                        'jenis_retribusi' => $item['jenis_retribusi'],
                        'quantity' => 1,
                        'amount' => $item['amount'],
                    ]);
                }

                $rowIds[] = $retribution->id;
                $created++;
                $grandTotal += $total;
            }

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

    protected function buildItems(array $row): array
    {
        $items = [];

        foreach ($this->columnSources as $columnKey => $jenis) {
            $amount = (float) $this->numberService->normalize($row[$columnKey] ?? 0);

            if ($amount > 0) {
                $items[] = [
                    'jenis_retribusi' => $jenis,
                    'amount' => $amount,
                ];
            }
        }

        return $items;
    }

    protected function validateRow(array $row, int $index): array
    {
        $errors = [];

        if (empty($row['market_id'])) {
            $errors[] = [
                'row' => $index,
                'field' => 'market_id',
                'message' => 'Pasar wajib diisi.',
            ];
        } elseif (! Market::whereKey($row['market_id'])->exists()) {
            $errors[] = [
                'row' => $index,
                'field' => 'market_id',
                'message' => 'Pasar tidak valid.',
            ];
        }

        if (empty($row['petugas_id'])) {
            $errors[] = [
                'row' => $index,
                'field' => 'petugas_id',
                'message' => 'Petugas wajib diisi.',
            ];
        } else {
            $petugas = User::whereKey($row['petugas_id'])->first();

            if (! $petugas) {
                $errors[] = [
                    'row' => $index,
                    'field' => 'petugas_id',
                    'message' => 'Petugas tidak valid.',
                ];
            } elseif (! $petugas->is_active) {
                $errors[] = [
                    'row' => $index,
                    'field' => 'petugas_id',
                    'message' => 'Petugas tidak aktif.',
                ];
            }
        }

        foreach ($this->columnSources as $columnKey => $jenis) {
            $value = $row[$columnKey] ?? 0;

            if ($value !== '' && $value !== null && ! $this->numberService->isValid($value)) {
                $errors[] = [
                    'row' => $index,
                    'field' => $columnKey,
                    'message' => "Kolom '{$columnKey}' harus berupa angka.",
                ];
            }

            $normalized = $this->numberService->normalize($value);

            if ($normalized !== null && $normalized < 0) {
                $errors[] = [
                    'row' => $index,
                    'field' => $columnKey,
                    'message' => "Kolom '{$columnKey}' tidak boleh negatif.",
                ];
            }
        }

        return $errors;
    }

    protected function normalizeNomorSetor(?string $nomorSetor): ?string
    {
        $nomorSetor = trim((string) $nomorSetor);

        return $nomorSetor === '' ? null : $nomorSetor;
    }

    protected function normalizeEntryType(?string $entryType): string
    {
        $entryType = strtolower(trim((string) $entryType));

        return in_array($entryType, ['manual', 'eret'], true)
            ? $entryType
            : 'manual';
    }
}