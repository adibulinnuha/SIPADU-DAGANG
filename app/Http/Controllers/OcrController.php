<?php

namespace App\Http\Controllers;

use App\Services\OcrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OcrController extends Controller
{
    public function __construct(
        protected OcrService $ocrService
    ) {}

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

        $file = $request->file('image');

        try {
            $path = $file->store('ocr-temp');

            $base64 = base64_encode($file->get());
            $mimeType = $file->getMimeType();

            $result = $this->ocrService->process($base64, $mimeType, $path);

            if (! $result['success']) {
                Log::warning('OcrController: OCR tidak berhasil', [
                    'errors' => $result['errors'],
                    'warnings' => $result['warnings'],
                ]);

                $message = implode(' ', $result['errors'] ?: ['Gagal memproses OCR. Silakan coba lagi.']);

                return redirect()
                    ->route('ocr.index')
                    ->with('error', $message);
            }

            $ocrData = $result['data'];

            session(['ocr_result' => $ocrData]);

            return redirect()
                ->route('ocr.index')
                ->with('ocr_result', $ocrData);
        } catch (\Throwable $e) {
            Log::error('OCR Processing failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('ocr.index')
                ->with('error', 'Gagal memproses OCR. Silakan coba lagi.');
        }
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

        $result = $this->ocrService->store($validated);

        if (! $result['success']) {
            Log::warning('OcrController: Gagal menyimpan transaksi OCR', [
                'errors' => $result['errors'],
            ]);

            return redirect()
                ->route('ocr.index')
                ->with('error', implode(' ', $result['errors']));
        }

        return redirect()
            ->route('dashboard')
            ->with('success', 'Transaksi OCR berhasil disimpan');
    }
}
