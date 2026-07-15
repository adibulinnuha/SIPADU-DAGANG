<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BendelDocumentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'bendel_document_id',
        'market_id',
        'jenis_retribusi',
        'nominal',
    ];

    public function document()
    {
        return $this->belongsTo(BendelDocument::class, 'bendel_document_id');
    }

    public function market()
    {
        return $this->belongsTo(Market::class);
    }
}