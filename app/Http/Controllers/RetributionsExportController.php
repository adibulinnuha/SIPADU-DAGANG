<?php

namespace App\Http\Controllers;

use App\Services\EretTemplateService;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RetributionsExportController extends Controller
{
    public function __construct(
        protected EretTemplateService $eretTemplateService
    ) {}

    public function template(Request $request): BinaryFileResponse
    {
        $validated = $request->validate([
            'date' => 'nullable|date',
        ]);

        $date = $validated['date'] ?? now()->toDateString();

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
}
