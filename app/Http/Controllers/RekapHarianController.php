<?php

namespace App\Http\Controllers;

use App\Exports\RekapHarianExport;
use App\Services\AggregateService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class RekapHarianController extends Controller
{
    public function __construct(
        protected AggregateService $aggregateService
    ) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'nullable|date',
        ]);

        $tanggal = $validated['tanggal'] ?? now()->toDateString();

        $rekap = $this->aggregateService->getDailyRecap($tanggal);

        $grandTotal = $this->aggregateService->getGrandTotal($tanggal);
        $grandTransaksi = $this->aggregateService->getTransactionCount($tanggal);

        return view('rekap-harian.index', compact(
            'tanggal',
            'rekap',
            'grandTotal',
            'grandTransaksi'
        ));
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'nullable|date',
        ]);

        $tanggal = $validated['tanggal'] ?? now()->toDateString();

        return Excel::download(
            new RekapHarianExport($tanggal),
            'rekap-harian-'.$tanggal.'.xlsx'
        );
    }
}
