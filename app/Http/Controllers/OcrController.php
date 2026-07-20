<?php

namespace App\Http\Controllers;

use App\Models\Market;
use App\Models\Retribution;
use Illuminate\Http\Request;

class OcrController extends Controller
{
    public function review()
    {
        // Simulasi hasil OCR sementara
        // Nanti diganti hasil Gemini/OCR

        $ocrData = [
            'tanggal' => now()->format('Y-m-d'),
            'nomor_tiket' => 'ETK-000001',
            'pasar' => 'KARIMATA 1',
            'jenis_retribusi' => 'Kios',
            'nominal' => 5000,
        ];

        return view('ocr.review', compact('ocrData'));
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required',
            'nomor_tiket' => 'required',
            'pasar' => 'required',
            'jenis_retribusi' => 'required',
            'nominal' => 'required|numeric',
        ]);


        $market = Market::where('name', $validated['pasar'])
            ->first();


        if (!$market) {

            return back()
                ->withErrors([
                    'pasar' => 'Pasar tidak ditemukan di database'
                ]);

        }


        Retribution::create([

            'market_id' => $market->id,

            'recorded_by' => auth()->id(),

            'retribution_date' => $validated['tanggal'],

            'jenis_retribusi' => $validated['jenis_retribusi'],

            'amount' => $validated['nominal'],

            'notes' =>
                'OCR e-Ticketing | ' .
                'Tiket: ' . $validated['nomor_tiket'],

        ]);


        return redirect()
            ->route('dashboard')
            ->with('success', 'Transaksi OCR berhasil disimpan');
    }
}