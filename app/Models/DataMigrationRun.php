<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DataMigrationRun extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'completed_at' => 'datetime', 'options' => 'array', 'summary' => 'array'];
    }

    public function issues(): HasMany
    {
        return $this->hasMany(DataMigrationIssue::class, 'run_id');
    }
}
