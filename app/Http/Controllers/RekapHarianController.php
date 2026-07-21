<?php

namespace App\Http\Controllers;

use App\Models\Retribution;
use App\Exports\RekapHarianExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class RekapHarianController extends Controller
{
    public function index(Request $request)
    {
        $tanggal = $request->input('tanggal', now()->toDateString());

        $rekap = Retribution::query()
            ->join('markets', 'markets.id', '=', 'retributions.market_id')
            ->select(
                'markets.name as market_name',
                DB::raw('COUNT(retributions.id) as total_transaksi'),
                DB::raw('SUM(retributions.amount) as total_nominal')
            )
            ->whereDate('retributions.retribution_date', $tanggal)
            ->groupBy('markets.id', 'markets.name')
            ->orderBy('markets.name')
            ->get();

        $grandTotal = $rekap->sum('total_nominal');
        $grandTransaksi = $rekap->sum('total_transaksi');

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