<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowLog extends Model
{
    protected $fillable = [
        'user_id',
        'retribution_id',
        'action',
        'old_status',
        'new_status',
        'description',
    ];

    public function retribution()
    {
        return $this->belongsTo(Retribution::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}