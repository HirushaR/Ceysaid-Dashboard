<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowRequest extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['result' => 'array'];
    }
}
