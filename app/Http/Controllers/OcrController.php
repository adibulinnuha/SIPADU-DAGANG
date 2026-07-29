<?php

namespace App\Http\Controllers;

use App\Models\Market;
use App\Models\Retribution;
use Illuminate\Http\Request;

class OcrController extends Controller
{
    public function index()
    {
        return view('ocr.index');
    }

    public function process(Request $request)
    {

        $request->validate([

            'image' => [
                'required',
                'image',
                'max:4096',
            ],

        ]);

        $path = $request->file('image')
            ->store('ocr-temp');

        /*
        |--------------------------------------------------------------------------
        | SIMULASI HASIL OCR
        |--------------------------------------------------------------------------
        | Nanti diganti Gemini / Tesseract
        |--------------------------------------------------------------------------
        */

        $ocrData = [
            'nomor_setor' => 'ETK-000123',
            'tanggal' => now()->format('Y-m-d'),
            'pasar' => 'KARIMATA 1',
            'jenis_retribusi' => 'Kios',
            'nominal' => 5000,
            'image' => $path,
        ];

        session(['ocr_result' => $ocrData]);

        return redirect()
            ->route('ocr.index')
            ->with('ocr_result', $ocrData);

    }

    public function review()
    {

        return redirect()
            ->route('ocr.index');

    }

    public function store(Request $request)
    {

        $validated = $request->validate([
            'tanggal' => 'required|date',
            'pasar' => 'required|string',
            'jenis_retribusi' => 'required|string|max:100',
            'nominal' => 'required|numeric|min:0',
            'nomor_setor' => 'nullable|string|max:100',
        ]);

        $market = Market::where('name', $validated['pasar'])
            ->first();

        Retribution::create([
            'market_id' => $market?->id,
            'jenis_retribusi' => $validated['jenis_retribusi'],
            'amount' => $validated['nominal'],
            'nomor_setor' => $validated['nomor_setor'] ?? null,
            'retribution_date' => $validated['tanggal'],
            'payment_method' => 'OCR',
            'notes' => 'Input melalui OCR e-Ticketing',
            'recorded_by' => auth()->id(),
        ]);

        return redirect()

            ->route('dashboard')

            ->with(
                'success',
                'Transaksi OCR berhasil disimpan'
            );

    }
}
