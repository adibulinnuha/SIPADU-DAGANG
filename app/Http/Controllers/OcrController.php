<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OcrController extends Controller
{
    protected GeminiService $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    public function index()
    {
        return view('ocr.index');
    }

    public function process(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        try {

            $image = $request->file('image');

            $mimeType = $image->getMimeType();

            $base64 = base64_encode(file_get_contents($image->getRealPath()));

            $response = $this->gemini->ocr($base64, $mimeType);

            $text = data_get(
                $response,
                'candidates.0.content.parts.0.text'
            );

            if (!$text) {
                return back()->withErrors([
                    'ocr' => 'Gemini tidak mengembalikan hasil OCR.'
                ]);
            }

            // Hilangkan ```json ... ```
            $text = preg_replace('/```json|```/', '', $text);
            $text = trim($text);

            $json = json_decode($text, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->withErrors([
                    'ocr' => 'Hasil OCR bukan JSON yang valid.'
                ]);
            }

            return view('ocr.index', [
                'result' => $json
            ]);

        } catch (\Throwable $e) {

            Log::error($e);

            return back()->withErrors([
                'ocr' => $e->getMessage()
            ]);
        }
    }
}