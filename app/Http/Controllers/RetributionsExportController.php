<?php

namespace App\Http\Controllers;

use App\Models\Retribution;
use App\Services\EretTemplateService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RetributionsExportController extends Controller
{
    public function exportTemplate(Request $request, EretTemplateService $service): BinaryFileResponse
    {
        $date = $request->input('date', now()->toDateString());

        $retributions = Retribution::with(['market', 'items'])
            ->whereDate('retribution_date', $date)
            ->get();

        $path = $service->generate($retributions, $date);

        return response()->download($path)->deleteFileAfterSend();
    }
}