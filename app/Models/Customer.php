<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'contact_info',
    ];

    protected $casts = [
        'contact_info' => 'array',
    ];

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }
}
