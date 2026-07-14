<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Verification extends Model
{
    protected $fillable = [
        'retribution_id',
        'nomor_setor',
        'tanggal_verifikasi',
        'status',
        'catatan',
        'verified_by',
    ];


    public function retribution()
    {
        return $this->belongsTo(Retribution::class);
    }


    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}