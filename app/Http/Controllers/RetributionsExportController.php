<?php

namespace App\Http\Controllers;

use App\Services\EretEngine;
use App\Services\EretTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RetributionsExportController extends Controller
{
    public function __construct(
        protected EretTemplateService $eretTemplateService,
        protected EretEngine $eretEngine,
    ) {}

    /**
     * Download template ERET.
     *
     * @throws \RuntimeException
     */
    public function template(Request $request): BinaryFileResponse
    {
        $validated = $request->validate([
            'date' => 'nullable|date',
            'engine' => 'nullable|in:legacy,new,dry-run',
        ]);

        $date = $validated['date'] ?? now()->toDateString();
        $engineMode = $validated['engine'] ?? 'legacy';

        // Use the new ERET Engine if requested
        if ($engineMode === 'dry-run') {
            return $this->dryRun($date);
        }

        if ($engineMode === 'new') {
            return $this->engineExport($date);
        }

        // Default: use legacy EretTemplateService
        $sheetName = now()->translatedFormat('d M');

        $spreadsheet = $this->eretTemplateService->generate(
            $sheetName,
            $date
        );

        $fileName = 'ERET_'.now()->format('Ymd_His').'.xlsx';

        $tempDir = storage_path('app/temp');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $tempFile = $tempDir.DIRECTORY_SEPARATOR.$fileName;

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Export using the new ERET Engine.
     */
    protected function engineExport(string $date): BinaryFileResponse
    {
        Log::info('RetributionsExportController: Using ERET Engine', [
            'date' => $date,
        ]);

        $spreadsheet = $this->eretEngine->generate($date);

        $fileName = 'ERET_'.now()->format('Ymd_His').'.xlsx';

        $tempDir = storage_path('app/temp');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $tempFile = $tempDir.DIRECTORY_SEPARATOR.$fileName;

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        $this->eretEngine->disconnect();

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Dry Run: Show mapping report without saving workbook.
     */
    protected function dryRun(string $date): BinaryFileResponse
    {
        Log::info('RetributionsExportController: DRY RUN', [
            'date' => $date,
        ]);

        $report = $this->eretEngine->dryRun($date);

        // Save mapping report as JSON for inspection
        $reportPath = storage_path('app/temp/eret_dry_run_'.now()->format('Ymd_His').'.json');
        $dir = dirname($reportPath);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(
            $reportPath,
            json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        return response()->download($reportPath, basename($reportPath))
            ->deleteFileAfterSend(true);
    }
}
