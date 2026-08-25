<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowOutboxMessage extends Model
{
    protected $table = 'workflow_outbox';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['payload' => 'array', 'available_at' => 'datetime', 'processed_at' => 'datetime'];
    }
}
