<?php

namespace App\Http\Controllers;

use App\Models\Retribution;
use App\Models\Verification;
use App\Services\WorkflowService;
use Illuminate\Http\RedirectResponse;
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'retribution_id' => 'required|exists:retributions,id',
            'nomor_setor' => 'required|string|max:100',
            'tanggal_verifikasi' => 'required|date',
            'catatan' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($validated) {

                Verification::updateOrCreate(
                    ['retribution_id' => $validated['retribution_id']],
                    [
                        'nomor_setor' => $validated['nomor_setor'],
                        'tanggal_verifikasi' => $validated['tanggal_verifikasi'],
                        'status' => 'Terverifikasi',
                        'catatan' => $validated['catatan'] ?? null,
                        'verified_by' => auth()->id(),
                    ]
                );

                $retribution = Retribution::findOrFail($validated['retribution_id']);

                $workflow = app(WorkflowService::class);

                if ($retribution->status === 'draft') {
                    $workflow->submit($retribution);
                    $retribution->refresh();
                }

                $workflow->verifyWithNomorSetor(
                    $retribution,
                    $validated['nomor_setor'],
                    $validated['catatan'] ?? null
                );
            });
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

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

    public function update(Request $request, Verification $verification): RedirectResponse
    {
        $validated = $request->validate([
            'nomor_setor' => 'required|string|max:100',
            'tanggal_verifikasi' => 'required|date',
            'status' => 'required|in:Pending,Terverifikasi',
            'catatan' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($validated, $verification) {

                $verification->update([
                    'nomor_setor' => $validated['nomor_setor'],
                    'tanggal_verifikasi' => $validated['tanggal_verifikasi'],
                    'status' => $validated['status'],
                    'catatan' => $validated['catatan'] ?? null,
                    'verified_by' => auth()->id(),
                ]);

                if ($validated['status'] === 'Terverifikasi') {
                    $retribution = $verification->retribution;

                    $retribution->update([
                        'nomor_setor' => $validated['nomor_setor'],
                    ]);

                    $workflow = app(WorkflowService::class);

                    if ($retribution->status === 'draft') {
                        $workflow->submit($retribution);
                        $retribution->refresh();
                    }

                    if ($retribution->status === 'submitted') {
                        $workflow->verify($retribution);
                    }
                }
            });
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

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