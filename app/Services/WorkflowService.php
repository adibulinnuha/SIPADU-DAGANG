<?php

namespace App\Services;

use App\Models\Retribution;
use App\Models\WorkflowLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WorkflowService
{
    /**
     * Get all retributions that are eligible for Bendel generation.
     * Includes verified, approved, and locked records.
     * Replaces: Verification::where('status', 'Terverifikasi')->get()
     */
    public function getVerifiedRetributions(): Collection
    {
        return Retribution::with(['market', 'items'])
            ->whereIn('status', ['verified', 'approved', 'locked'])
            ->get();
    }

    public function submit(Retribution $retribution): Retribution
    {
        return $this->changeStatus(
            $retribution,
            'submitted',
            'SUBMIT'
        );
    }

    public function verify(Retribution $retribution, ?string $catatan = null): Retribution
    {
        return $this->changeStatus(
            $retribution,
            'verified',
            'VERIFY',
            $catatan
        );
    }

    public function approve(Retribution $retribution): Retribution
    {
        return $this->changeStatus(
            $retribution,
            'approved',
            'APPROVE'
        );
    }

    public function lock(Retribution $retribution): Retribution
    {
        return $this->changeStatus(
            $retribution,
            'locked',
            'LOCK'
        );
    }

    /**
     * Verify a retribution and store the nomor_setor in a single transactional call.
     * Replaces the core logic previously only in VerificationController::store().
     *
     * @throws \Exception
     */
    public function verifyWithNomorSetor(
        Retribution $retribution,
        string $nomorSetor,
        ?string $catatan = null
    ): Retribution {
        return DB::transaction(function () use ($retribution, $nomorSetor, $catatan) {

            // Store the nomor_setor on the retribution record
            $retribution->update(['nomor_setor' => $nomorSetor]);

            // changeStatus wraps its own DB::transaction, but Laravel handles
            // nested transactions via savepoints — the outermost transaction
            // ensures atomic rollback if changeStatus throws.
            return $this->changeStatus($retribution, 'verified', 'VERIFY', $catatan);
        });
    }

    private function changeStatus(
        Retribution $retribution,
        string $status,
        string $action,
        ?string $description = null
    ): Retribution {

        return DB::transaction(function () use (
            $retribution,
            $status,
            $action,
            $description
        ) {

            $oldStatus = $retribution->status;

            if (! $this->canChangeStatus($oldStatus, $status)) {
                throw new \Exception(
                    "Perubahan status {$oldStatus} ke {$status} tidak diperbolehkan."
                );
            }

            $updateData = [
                'status' => $status,
            ];

            // Map status transitions to their corresponding audit fields.
            // Only populate the timestamp + user_id if the field is not already set.
            $auditMap = [
                'submitted' => ['submitted_at', 'submitted_by'],
                'verified' => ['verified_at', 'verified_by'],
                'approved' => ['approved_at', 'approved_by'],
                'locked' => ['locked_at', 'locked_by'],
            ];

            if (isset($auditMap[$status])) {
                [$timeField, $userField] = $auditMap[$status];

                if ($retribution->{$timeField} === null) {
                    $updateData[$timeField] = now();
                    $updateData[$userField] = Auth::id();
                }
            }

            $retribution->update($updateData);

            WorkflowLog::create([
                'user_id' => Auth::id(),
                'retribution_id' => $retribution->id,
                'action' => $action,
                'old_status' => $oldStatus,
                'new_status' => $status,
                'description' => $description ?? "Status berubah dari {$oldStatus} menjadi {$status}",
            ]);

            return $retribution;
        });
    }

    private function canChangeStatus(
        ?string $oldStatus,
        string $newStatus
    ): bool {

        $flows = [
            'draft' => [
                'submitted',
            ],

            'submitted' => [
                'verified',
            ],

            'verified' => [
                'approved',
            ],

            'approved' => [
                'locked',
            ],
        ];

        return in_array(
            $newStatus,
            $flows[$oldStatus] ?? []
        );
    }
}
