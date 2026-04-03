<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSequence extends Model
{
    protected $fillable = [
        'type',
        'year',
        'lead_id',
        'sequence',
    ];

    protected $casts = [
        'year' => 'integer',
        'lead_id' => 'integer',
        'sequence' => 'integer',
    ];
}
