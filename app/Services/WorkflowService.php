<?php

namespace App\Services;

use App\Models\Retribution;
use App\Models\WorkflowLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class WorkflowService
{
    public function submit(Retribution $retribution): Retribution
    {
        return $this->changeStatus(
            $retribution,
            'submitted',
            'SUBMIT'
        );
    }


    public function verify(Retribution $retribution): Retribution
    {
        return $this->changeStatus(
            $retribution,
            'verified',
            'VERIFY'
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


    private function changeStatus(
        Retribution $retribution,
        string $status,
        string $action
    ): Retribution {

        return DB::transaction(function () use (
            $retribution,
            $status,
            $action
        ) {

            $oldStatus = $retribution->status;


            if (!$this->canChangeStatus($oldStatus, $status)) {
                throw new \Exception(
                    "Perubahan status {$oldStatus} ke {$status} tidak diperbolehkan."
                );
            }


            $retribution->update([
                'status' => $status,
            ]);


            WorkflowLog::create([
                'user_id' => Auth::id(),
                'retribution_id' => $retribution->id,
                'action' => $action,
                'old_status' => $oldStatus,
                'new_status' => $status,
                'description' => "Status berubah dari {$oldStatus} menjadi {$status}",
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
                'submitted'
            ],

            'submitted' => [
                'verified'
            ],

            'verified' => [
                'approved'
            ],

            'approved' => [
                'locked'
            ],
        ];


        return in_array(
            $newStatus,
            $flows[$oldStatus] ?? []
        );
    }
}