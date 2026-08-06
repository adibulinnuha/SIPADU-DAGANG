<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Market extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'address',
        'phone',
        'is_active',
    ];

    public function bendelItems()
    {
        return $this->hasMany(BendelDocumentItem::class);
    }

    /**
     * The petugas (Korwil / Juru Pungut) assigned to this market.
     */
    public function petugas(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
