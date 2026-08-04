<?php

namespace App\Http\Controllers;

use App\Http\Requests\EretDashboardSaveRequest;
use App\Services\EretDashboardService;
use Illuminate\Http\JsonResponse;

class EretDashboardController extends Controller
{
    public function __construct(
        protected EretDashboardService $eretDashboardService
    ) {}

    /**
     * Persist the entire spreadsheet batch for a given date.
     */
    public function save(EretDashboardSaveRequest $request): JsonResponse
    {
        $tanggal = $request->input('tanggal');
        $rows = $request->input('rows', []);

        $result = $this->eretDashboardService->save($tanggal, $rows);

        return response()->json([
            'success' => empty($result['errors']),
            'message' => empty($result['errors'])
                ? 'Data berhasil disimpan.'
                : 'Beberapa baris gagal disimpan.',
            'created' => $result['created'],
            'updated' => $result['updated'],
            'deleted' => $result['deleted'],
            'total' => $result['total'],
            'errors' => $result['errors'],
        ]);
    }
}
