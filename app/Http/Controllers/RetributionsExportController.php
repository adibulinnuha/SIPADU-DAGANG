<?php

namespace App\Http\Controllers;

use App\Services\EretTemplateService;
use Carbon\Carbon;
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
        $date = $request->input('date', now()->toDateString());

$sheetName = Carbon::parse($date)->translatedFormat('d M');

        $spreadsheet = $this->eretTemplateService->generate(
            $sheetName,
            $date
        );

        $fileName = 'ERET_'.now()->format('Ymd_His').'.xlsx';

        $tempFile = storage_path('app/temp/'.$fileName);

        if (! is_dir(dirname($tempFile))) {
            mkdir(dirname($tempFile), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
}
