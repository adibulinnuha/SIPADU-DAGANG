<?php

namespace App\Http\Controllers;

use App\Services\BackupService;
use App\Services\RestoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function __construct(
        protected BackupService $backupService,
        protected RestoreService $restoreService,
    ) {}

    /**
     * List all backups.
     */
    public function index()
    {
        $backups = $this->backupService->list();

        return view('backup.index', compact('backups'));
    }

    /**
     * Create a new backup.
     */
    public function create(Request $request)
    {
        $request->validate([
            'type' => 'nullable|in:full,database,workbook,config',
        ]);

        try {
            $backup = $this->backupService->create($request->input('type'));

            return redirect()
                ->route('backup.index')
                ->with('success', 'Backup berhasil dibuat: '.$backup['filename']);
        } catch (\Throwable $e) {
            Log::error('BackupController: Gagal membuat backup', [
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('backup.index')
                ->with('error', 'Gagal membuat backup: '.$e->getMessage());
        }
    }

    /**
     * Download a backup archive.
     */
    public function download(string $filename): BinaryFileResponse
    {
        try {
            $path = $this->backupService->path($filename);

            return response()->download($path, $filename);
        } catch (\Throwable $e) {
            Log::error('BackupController: Gagal mengunduh backup', [
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('backup.index')
                ->with('error', 'Gagal mengunduh backup: '.$e->getMessage());
        }
    }

    /**
     * Delete a backup archive.
     */
    public function destroy(string $filename)
    {
        try {
            $this->backupService->delete($filename);

            return redirect()
                ->route('backup.index')
                ->with('success', 'Backup dihapus: '.$filename);
        } catch (\Throwable $e) {
            Log::error('BackupController: Gagal menghapus backup', [
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('backup.index')
                ->with('error', 'Gagal menghapus backup: '.$e->getMessage());
        }
    }

    /**
     * Restore a backup (confirmation required).
     */
    public function restore(Request $request, string $filename)
    {
        $request->validate([
            'confirmed' => 'required|accepted',
        ]);

        try {
            $result = $this->restoreService->restore($filename, true);

            return redirect()
                ->route('backup.index')
                ->with('success', $result['message'].' ('.implode('; ', $result['steps']).')');
        } catch (\Throwable $e) {
            Log::error('BackupController: Restore gagal', [
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('backup.index')
                ->with('error', 'Restore gagal: '.$e->getMessage());
        }
    }
}
