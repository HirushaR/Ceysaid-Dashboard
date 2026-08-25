<?php

namespace App\Models;

use App\Enums\WorkflowTaskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowTask extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['status' => WorkflowTaskStatus::class, 'due_at' => 'datetime', 'started_at' => 'datetime', 'completed_at' => 'datetime', 'metadata' => 'array'];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
