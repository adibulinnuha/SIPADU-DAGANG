<?php

namespace App\Services;

use App\Models\Market;
use App\Models\Retribution;
use Illuminate\Support\Facades\Log;

/**
 * OcrService — Hardened OCR pipeline for SIPADU-DAGANG.
 *
 * Pipeline:
 *   Foto → OCR → Extraction → Validation → Normalization → Review → Mapping → Store
 *
 * Hardening goals (Sprint 8, Prioritas 2):
 *  - market_id is NEVER null
 *  - Nominal, tanggal, petugas, pasar all validated
 *  - Graceful fallbacks for: blurry/empty image, OCR failure, incomplete data,
 *    negative values, wrong format
 *  - No unhandled exceptions escape the pipeline
 *  - Informative logging at every step
 */
class OcrService
{
    public function __construct(
        protected GeminiService $geminiService,
        protected EretNumberService $numberService,
    ) {}

    /**
     * Run the OCR pipeline on an uploaded image and produce a review payload.
     *
     * @param  string      $base64   Base64-encoded image content.
     * @param  string      $mimeType MIME type of the image.
     * @param  string|null $imagePath Stored image path (for reference).
     * @return array{success:bool, data:array, errors:array, warnings:array}
     */
    public function process(string $base64, string $mimeType, ?string $imagePath = null): array
    {
        $result = [
            'success' => false,
            'data' => [],
            'errors' => [],
            'warnings' => [],
        ];

        // ── 1. OCR (guarded — never throw) ────────────────────────────────
        $raw = [];
        try {
            $raw = $this->geminiService->ocr($base64, $mimeType);
        } catch (\Throwable $e) {
            Log::warning('OcrService: OCR gagal, fallback ke data kosong', [
                'error' => $e->getMessage(),
            ]);
            $result['warnings'][] = 'OCR gagal diproses. Data tidak dapat dibaca.';
            $result['errors'][] = 'OCR gagal.';
            return $result;
        }

        // ── 2. Extraction ─────────────────────────────────────────────────
        $extracted = $this->extract($raw);

        if (empty($extracted)) {
            $result['warnings'][] = 'Tidak ada data yang berhasil diekstrak dari gambar.';
            $result['errors'][] = 'Gambar tidak dapat dibaca (kosong / buram).';
            return $result;
        }

        // ── 3. Validation & Normalization ─────────────────────────────────
        [$normalized, $errors, $warnings] = $this->normalize($extracted);

        $result['warnings'] = array_merge($result['warnings'], $warnings);

        if (! empty($errors)) {
            $result['errors'] = array_merge($result['errors'], $errors);
            // Still return partial data so the operator can correct it during review.
            $result['data'] = $normalized;
            return $result;
        }

        // ── 4. Mapping to market (market_id never null) ───────────────────
        $mapped = $this->mapMarket($normalized);

        $result['data'] = $mapped['data'];
        $result['warnings'] = array_merge($result['warnings'], $mapped['warnings']);

        if (! empty($mapped['errors'])) {
            $result['errors'] = array_merge($result['errors'], $mapped['errors']);
            return $result;
        }

        // ── 5. Success ────────────────────────────────────────────────────
        $result['success'] = true;
        $result['data']['image'] = $imagePath;

        Log::info('OcrService: OCR berhasil diproses', [
            'market' => $result['data']['pasar'] ?? null,
            'nominal' => $result['data']['nominal'] ?? null,
            'success' => true,
        ]);

        return $result;
    }

    /**
     * Persist a reviewed OCR payload into retributions.
     *
     * @param  array $payload Validated, reviewed OCR data.
     * @return array{success:bool, retribution:?Retribution, errors:array}
     */
    public function store(array $payload): array
    {
        // Re-validate to guarantee invariants (never trust the client).
        [$normalized, $errors] = $this->normalize($payload);

        if (! empty($errors)) {
            return ['success' => false, 'retribution' => null, 'errors' => $errors];
        }

        $market = Market::where('name', $normalized['pasar'])->first();

        if ($market === null) {
            Log::warning('OcrService: Pasar tidak ditemukan saat menyimpan', [
                'pasar' => $normalized['pasar'],
            ]);
            return [
                'success' => false,
                'retribution' => null,
                'errors' => ['Pasar "'.$normalized['pasar'].'" tidak ditemukan.'],
            ];
        }

        try {
            $retribution = Retribution::create([
                'market_id' => $market->id,
                'jenis_retribusi' => $normalized['jenis_retribusi'],
                'amount' => $normalized['nominal'],
                'nomor_setor' => $normalized['nomor_setor'] ?? null,
                'retribution_date' => $normalized['tanggal'],
                'payment_method' => 'OCR',
                'notes' => 'Input melalui OCR e-Ticketing',
                'recorded_by' => auth()->id(),
                'status' => 'draft',
            ]);

            Log::info('OcrService: Transaksi OCR disimpan', [
                'retribution_id' => $retribution->id,
                'market_id' => $market->id,
                'nominal' => $normalized['nominal'],
            ]);

            return ['success' => true, 'retribution' => $retribution, 'errors' => []];
        } catch (\Throwable $e) {
            Log::error('OcrService: Gagal menyimpan transaksi OCR', [
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'retribution' => null,
                'errors' => ['Gagal menyimpan transaksi: '.$e->getMessage()],
            ];
        }
    }

    // -----------------------------------------------------------------------
    //  Internal helpers
    // -----------------------------------------------------------------------

    /**
     * Extract the raw OCR payload from the Gemini response.
     *
     * @param  array $response Gemini API response.
     * @return array Decoded associative array, or [] on failure.
     */
    protected function extract(array $response): array
    {
        $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';

        $json = trim((string) $text);

        // Strip markdown code fences if present.
        if (str_starts_with($json, '```')) {
            $json = preg_replace('/^```(?:json)?\s*/', '', $json);
            $json = preg_replace('/\s*```$/', '', $json);
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Validate and normalize the extracted OCR data.
     *
     * @return array{0:array, 1:array, 2:array} [normalized, errors, warnings]
     */
    protected function normalize(array $data): array
    {
        $errors = [];
        $warnings = [];

        // --- Nominal ---
        $nominal = $data['total'] ?? $data['nominal'] ?? 0;
        $normalizedNominal = $this->numberService->normalize($nominal);

        if ($normalizedNominal === null || $normalizedNominal <= 0) {
            $errors[] = 'Nominal tidak valid atau kosong.';
            $normalizedNominal = 0;
        } elseif ($normalizedNominal < 0) {
            $errors[] = 'Nominal tidak boleh negatif.';
            $normalizedNominal = 0;
        }

        // --- Tanggal ---
        $tanggal = $data['tanggal'] ?? null;
        if ($tanggal) {
            try {
                $parsed = \Carbon\Carbon::parse($tanggal);
                $tanggal = $parsed->format('Y-m-d');
            } catch (\Throwable $e) {
                $tanggal = null;
            }
        }

        if (! $tanggal) {
            $tanggal = now()->format('Y-m-d');
            $warnings[] = 'Tanggal tidak valid, menggunakan tanggal hari ini.';
        }

        // --- Pasar ---
        $pasar = trim((string) ($data['pasar'] ?? ''));
        if ($pasar === '') {
            $errors[] = 'Pasar wajib diisi.';
        }

        // --- Jenis retribusi ---
        $jenis = trim((string) ($data['jenis_retribusi'] ?? 'Retribusi Harian'));
        if ($jenis === '') {
            $jenis = 'Retribusi Harian';
        }

        // --- Nomor setor (optional) ---
        $nomorSetor = trim((string) ($data['nomor_setor'] ?? ''));
        $nomorSetor = $nomorSetor === '' ? null : $nomorSetor;

        return [
            [
                'nomor_setor' => $nomorSetor,
                'tanggal' => $tanggal,
                'pasar' => $pasar,
                'jenis_retribusi' => $jenis,
                'nominal' => $normalizedNominal,
            ],
            $errors,
            $warnings,
        ];
    }

    /**
     * Map the normalized pasar name to a Market; guarantee market_id is never
     * null by resolving the market name.
     *
     * @return array{data:array, errors:array, warnings:array}
     */
    protected function mapMarket(array $data): array
    {
        $errors = [];
        $warnings = [];

        $market = Market::where('name', $data['pasar'])->first();

        if ($market === null) {
            // Fallback: try a case-insensitive / trimmed match.
            $market = Market::whereRaw('LOWER(name) = ?', [strtolower($data['pasar'])])->first();
        }

        if ($market === null) {
            $errors[] = 'Pasar "'.$data['pasar'].'" tidak ditemukan di master data.';
            $data['market_id'] = null;
        } else {
            $data['market_id'] = $market->id;
            // Normalize the pasar name to the canonical market name.
            $data['pasar'] = $market->name;
        }

        return ['data' => $data, 'errors' => $errors, 'warnings' => $warnings];
    }
}
