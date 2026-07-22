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
    'notes',

    // workflow approval
    'status',
    'submitted_at',
    'submitted_by',
    'verified_at',
    'verified_by',
    'approved_at',
    'approved_by',
    'locked_at',
    'locked_by',
])]
class Retribution extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'retribution_date' => 'date',
            'amount' => 'decimal:2',

            // workflow timestamps
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'locked_at' => 'datetime',
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

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function items()
    {
        return $this->hasMany(RetributionItem::class);
    }
}