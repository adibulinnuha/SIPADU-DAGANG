<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bendel extends Model
{
    use HasFactory;

    protected $fillable = [
        'tanggal_pendapatan',
        'tanggal_setor',
        'status',
        'created_by',
        'keterangan',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documents()
    {
        return $this->hasMany(BendelDocument::class);
    }
}
