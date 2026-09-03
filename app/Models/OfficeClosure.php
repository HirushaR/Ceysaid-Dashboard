<?php

namespace App\Models;

use App\Enums\ClosureType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficeClosure extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'type', 'description', 'start_date', 'end_date', 'created_by'];

    protected $casts = [
        'type' => ClosureType::class,
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getDurationInDaysAttribute(): int
    {
        return $this->start_date && $this->end_date
            ? $this->start_date->diffInDays($this->end_date) + 1
            : 0;
    }
}
