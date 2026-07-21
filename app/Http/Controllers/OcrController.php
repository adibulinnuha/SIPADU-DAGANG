<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Retribution;
use App\Models\Market;

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
                'max:4096'
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



        return view('ocr.review', [

            'ocr' => $ocrData

        ]);

    }





    public function review()
    {

        return redirect()
            ->route('ocr.index');

    }






    public function store(Request $request)
    {

        $request->validate([

            'tanggal' => 'required',

            'pasar' => 'required',

            'jenis_retribusi' => 'required',

            'nominal' => 'required|numeric',

        ]);



        $market = Market::where('name', $request->pasar)
            ->first();



        Retribution::create([

            'market_id' => $market?->id,

            'jenis_retribusi' => $request->jenis_retribusi,

            'amount' => $request->nominal,

            'retribution_date' => $request->tanggal,

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