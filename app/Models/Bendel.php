<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bendel extends Model
{
    use HasFactory;

    protected $fillable = [
        'market_id',
        'nomor_bendel',
        'tanggal',
        'periode',
        'file_path',
        'status',
        'keterangan',
    ];

    public function market()
    {
        return $this->belongsTo(Market::class);
    }
}