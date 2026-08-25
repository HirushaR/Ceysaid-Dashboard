<?php

namespace App\Enums;

enum WorkflowTaskStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Waiting = 'waiting';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
