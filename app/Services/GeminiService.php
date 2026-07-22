<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class GeminiService
{
    protected string $apiKey;

    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model');
    }

    public function ocr(string $base64Image, string $mimeType): array
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        $prompt = <<<'PROMPT'
Anda adalah sistem OCR resmi SIPADU-DAGANG.

Baca struk e-Ticketing retribusi pasar.

Jangan membuat data jika tidak terlihat.

Kembalikan JSON valid tanpa markdown.

{
 "petugas":"",
 "nomor_setor":"",
 "tanggal":"",
 "pasar":"",
 "kios":0,
 "los":0,
 "dasaran":0,
 "mck":0,
 "kebersihan":0,
 "listrik":0,
 "total":0
}
PROMPT;

        $response = Http::timeout(60)
            ->acceptJson()
            ->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $base64Image,
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw new Exception(
                'Gemini API Error: '.$response->body()
            );
        }

        return $response->json();
    }
}
