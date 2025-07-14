<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $fillable = [
        'lead_id',
        'file_path',
        'original_name',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
} 