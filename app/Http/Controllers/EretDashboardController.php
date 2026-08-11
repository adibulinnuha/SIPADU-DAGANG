<?php

namespace App\Http\Controllers;

use App\Http\Requests\EretDashboardSaveRequest;
use App\Models\Retribution;
use App\Services\EretDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EretDashboardController extends Controller
{
    public function __construct(
        protected EretDashboardService $eretDashboardService
    ) {}

    /**
     * Simpan seluruh data spreadsheet ERET lalu langsung buka halaman print.
     */
    public function save(EretDashboardSaveRequest $request): RedirectResponse
    {
        $tanggal = $request->input('tanggal');
        $rows = $request->input('rows', []);

        // Simpan data ke database
        $this->eretDashboardService->save($tanggal, $rows);

        // Redirect ke halaman print otomatis
        return redirect()->route('dashboard.eret.print', [
            'tanggal' => $tanggal,
        ]);
    }

    /**
     * Tampilkan halaman print otomatis berdasarkan data ERET yang baru disimpan.
     */
    public function print(string $tanggal): View
    {
        $retributions = Retribution::with(['market', 'items'])
            ->whereDate('retribution_date', $tanggal)
            ->where('entry_type', 'manual')
            ->orderBy('market_id')
            ->get();

        return view('dashboard-print', [
            'tanggal' => $tanggal,
            'retributions' => $retributions,
        ]);
    }
}