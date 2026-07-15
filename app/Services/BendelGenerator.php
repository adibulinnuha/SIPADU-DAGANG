<?php

namespace App\Services;

use App\Models\Bendel;
use Illuminate\Support\Facades\Auth;

class BendelGenerator
{
    public function generate(string $tanggalPendapatan, ?string $tanggalSetor = null): Bendel
    {
        return Bendel::create([
            'tanggal_pendapatan' => $tanggalPendapatan,
            'tanggal_setor'      => $tanggalSetor ?? now()->toDateString(),
            'status'             => 'draft',
            'created_by'         => Auth::id(),
            'keterangan'         => 'Generate otomatis SIPADU-DAGANG',
        ]);
    }
}