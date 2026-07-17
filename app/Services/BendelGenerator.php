<?php

namespace App\Services;

use App\Models\Bendel;
use App\Models\BendelDocument;
use App\Models\BendelDocumentItem;
use App\Models\Verification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BendelGenerator
{
    public function generate(
        string $tanggalPendapatan,
        ?string $tanggalSetor = null
    ): Bendel {

        return DB::transaction(function () use (
            $tanggalPendapatan,
            $tanggalSetor
        ) {

            $bendel = Bendel::create([
                'tanggal_pendapatan' => $tanggalPendapatan,
                'tanggal_setor' => $tanggalSetor ?? now()->toDateString(),
                'status' => 'draft',
                'created_by' => Auth::id(),
                'keterangan' => 'Generate otomatis SIPADU-DAGANG',
            ]);


            $verifications = Verification::with([
                    'retribution.market'
                ])
                ->where('status', 'Terverifikasi')
                ->get();


            if ($verifications->isEmpty()) {
                return $bendel;
            }


            $document = BendelDocument::create([
                'bendel_id' => $bendel->id,
                'jenis' => 'Retribusi Harian',
                'kode' => 'RETRIBUSI',
                'status' => 'draft',
                'nominal' => 0,
            ]);


            $total = 0;


            foreach ($verifications as $verification) {

                $retribution = $verification->retribution;


                if (!$retribution) {
                    continue;
                }


                BendelDocumentItem::create([
                    'bendel_document_id' => $document->id,
                    'retribution_id' => $retribution->id,
                    'market_id' => $retribution->market_id,
                    'jenis_retribusi' => $retribution->jenis_retribusi,
                    'nominal' => $retribution->amount,
                ]);


                $total += $retribution->amount;
            }


            $document->update([
                'nominal' => $total,
            ]);


            return $bendel;

        });
    }
}