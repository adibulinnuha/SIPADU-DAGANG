<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BendelDocumentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'bendel_document_id',
        'retribution_id',
        'market_id',
        'jenis_retribusi',
        'nominal',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(
            BendelDocument::class,
            'bendel_document_id'
        );
    }

    public function retribution(): BelongsTo
    {
        return $this->belongsTo(
            Retribution::class,
            'retribution_id'
        );
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }
}
