<?php

namespace App\Http\Controllers;

use App\Models\Retribution;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class EretDashboardController extends Controller
{
    public function save(Request $request): RedirectResponse
    {
        $tanggal = $request->input('tanggal');
        $rows = $request->input('rows', []);

        foreach ($rows as $row) {
            $kios = (float) ($row['kios'] ?? 0);
            $los = (float) ($row['los'] ?? 0);
            $dasaran = (float) ($row['dasaran'] ?? 0);
            $mck = (float) ($row['mck'] ?? 0);
            $sampah = (float) ($row['sampah'] ?? 0);
            $listrik = (float) ($row['listrik'] ?? 0);

            $total = $kios + $los + $dasaran + $mck + $sampah + $listrik;

            // Lewati baris kosong
            if ($total <= 0) {
                continue;
            }

            Retribution::create([
                'market_id' => $row['market_id'],
                'jenis_retribusi' => 'Retribusi Harian',
                'recorded_by' => Auth::id(),
                'retribution_date' => $tanggal,
                'amount' => $total,
                'payment_method' => 'cash',
                'entry_type' => 'manual',
                'status' => 'draft',
            ]);
        }

        return redirect()
            ->route('dashboard', ['tanggal' => $tanggal])
            ->with('success', 'Data ERET berhasil disimpan.');
    }
}