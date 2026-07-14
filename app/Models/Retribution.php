<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'market_id',
    'jenis_retribusi',
    'recorded_by',
    'retribution_date',
    'amount',
    'payment_method',
    'notes'
])]
class Retribution extends Model
{
    use HasFactory;


    protected function casts(): array
    {
        return [
            'retribution_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }


    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }


    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}