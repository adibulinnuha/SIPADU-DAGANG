<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BendelDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'bendel_id',
        'jenis',
        'kode',
        'nomor_register',
        'nomor_setor',
        'nominal',
        'status',
    ];

    public function bendel()
    {
        return $this->belongsTo(Bendel::class);
    }

    public function items()
    {
        return $this->hasMany(BendelDocumentItem::class);
    }
}
