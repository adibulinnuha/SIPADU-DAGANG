<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Market extends Model
{
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
}