<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OcrController extends Controller
{
    public function index()
    {
        return view('ocr.upload');
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
        | Temporary OCR Result
        |--------------------------------------------------------------------------
        | Nanti diganti dengan AI OCR engine
        */

        $result = [
            'nomor_tiket' => 'ETK-000123',
            'tanggal' => now()->format('Y-m-d'),
            'pasar' => 'KARIMATA',
            'jenis_retribusi' => 'Kios',
            'nominal' => 5000,
            'image' => $path,
        ];


        return view('ocr.review', compact('result'));
    }
}