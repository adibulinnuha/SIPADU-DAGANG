<?php

namespace App\Http\Controllers;

use App\Models\Retribution;
use App\Models\Verification;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VerificationController extends Controller
{
    public function index()
    {
        $verifications = Verification::with([
            'retribution.market',
            'verifier',
        ])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('verifications.index', compact('verifications'));
    }

    public function create()
    {
        $retributions = Retribution::with('market')
            ->orderByDesc('retribution_date')
            ->get();

        return view('verifications.create', compact('retributions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'retribution_id' => 'required|exists:retributions,id',
            'nomor_setor' => 'required|string|max:100',
            'tanggal_verifikasi' => 'required|date',
        ]);

        DB::transaction(function () use ($request) {

            // Legacy write — create/update the verifications record
            Verification::updateOrCreate(
                ['retribution_id' => $request->retribution_id],
                [
                    'nomor_setor' => $request->nomor_setor,
                    'tanggal_verifikasi' => $request->tanggal_verifikasi,
                    'status' => 'Terverifikasi',
                    'catatan' => $request->catatan,
                    'verified_by' => auth()->id(),
                ]
            );

            // Dual-write — also advance the retribution workflow
            $retribution = Retribution::findOrFail($request->retribution_id);

            app(WorkflowService::class)->verifyWithNomorSetor(
                $retribution,
                $request->nomor_setor,
                $request->catatan
            );
        });

        return redirect()
            ->route('verifications.index')
            ->with('success', 'Billing berhasil diverifikasi.');
    }

    public function show(Verification $verification)
    {
        return redirect()->route('verifications.edit', $verification);
    }

    public function edit(Verification $verification)
    {
        $retributions = Retribution::with('market')
            ->orderByDesc('retribution_date')
            ->get();

        return view('verifications.edit', compact(
            'verification',
            'retributions'
        ));
    }

    public function update(Request $request, Verification $verification)
    {
        $request->validate([
            'nomor_setor' => 'required|string|max:100',
            'tanggal_verifikasi' => 'required|date',
            'status' => 'required|in:Pending,Terverifikasi',
        ]);

        DB::transaction(function () use ($request, $verification) {

            // Legacy write — update the verifications record
            $verification->update([
                'nomor_setor' => $request->nomor_setor,
                'tanggal_verifikasi' => $request->tanggal_verifikasi,
                'status' => $request->status,
                'catatan' => $request->catatan,
                'verified_by' => auth()->id(),
            ]);

            // Dual-write — only transition the retribution workflow when
            // the legacy status is set to "Terverifikasi".
            // The state machine does not support reverse transitions,
            // so "Pending" updates are intentionally skipped.
            if ($request->status === 'Terverifikasi') {
                $retribution = $verification->retribution;

                // Store the nomor_setor on the retribution record
                $retribution->update(['nomor_setor' => $request->nomor_setor]);

                // Perform the workflow verify transition
                app(WorkflowService::class)->verify($retribution);
            }
        });

        return redirect()
            ->route('verifications.index')
            ->with('success', 'Status verifikasi berhasil diperbarui.');
    }

    public function destroy(Verification $verification)
    {
        $verification->delete();

        return redirect()
            ->route('verifications.index')
            ->with('success', 'Data verifikasi berhasil dihapus.');
    }
}
