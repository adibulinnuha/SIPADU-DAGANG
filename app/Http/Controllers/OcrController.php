<?php

namespace App\Http\Controllers;

use App\Models\Market;
use App\Models\Retribution;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OcrController extends Controller
{
    public function __construct(
        protected GeminiService $geminiService
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
        $path = $file->store('ocr-temp');

        try {
            $base64 = base64_encode($file->get());
            $mimeType = $file->getMimeType();

            $response = $this->geminiService->ocr($base64, $mimeType);

            $data = $this->extractOcrData($response);

            $ocrData = [
                'nomor_setor' => $data['nomor_setor'] ?? null,
                'tanggal' => $data['tanggal'] ?? now()->format('Y-m-d'),
                'pasar' => $data['pasar'] ?? null,
                'jenis_retribusi' => $data['jenis_retribusi'] ?? null,
                'nominal' => $data['total'] ?? $data['nominal'] ?? 0,
                'image' => $path,
            ];

            session(['ocr_result' => $ocrData]);

            return redirect()
                ->route('ocr.index')
                ->with('ocr_result', $ocrData);
        } catch (\Exception $e) {
            Log::error('OCR Processing failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('ocr.index')
                ->with('error', 'Gagal memproses OCR. Silakan coba lagi.');
        }
    }

    /**
     * Extract OCR payload from Gemini response.
     *
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    private function extractOcrData(array $response): array
    {
        $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';

        $json = trim($text);
        // Strip markdown code fences if present
        if (str_starts_with($json, '```')) {
            $json = preg_replace('/^```(?:json)?\s*/', '', $json);
            $json = preg_replace('/\s*```$/', '', $json);
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
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
