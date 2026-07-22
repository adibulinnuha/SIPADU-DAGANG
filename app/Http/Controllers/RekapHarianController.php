<?php

namespace App\Http\Controllers;

use App\Exports\RekapHarianExport;
use App\Services\EretService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class RekapHarianController extends Controller
{
    public function __construct(
        protected EretService $eretService
    ) {}

    public function index(Request $request)
    {
        $tanggal = $request->input('tanggal', now()->toDateString());

        $rekap = $this->eretService->getDailyRecap($tanggal);

        $grandTotal = $rekap->sum('total');
        $grandTransaksi = $rekap->count();

        return view('rekap-harian.index', compact(
            'tanggal',
            'rekap',
            'grandTotal',
            'grandTransaksi'
        ));
    }

    public function export(Request $request)
    {
        $tanggal = $request->input(
            'tanggal',
            now()->toDateString()
        );

        return Excel::download(
            new RekapHarianExport($tanggal),
            'rekap-harian-'.$tanggal.'.xlsx'
        );
    }
}
