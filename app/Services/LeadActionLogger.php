<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadActionLog;
use Illuminate\Support\Facades\Log;

class LeadActionLogger
{
    /**
     * Append a row to the lead's action log (no-op if lead is missing).
     */
    public static function log(
        ?Lead $lead,
        string $action,
        string $description,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        if (! $lead) {
            return;
        }

        try {
            LeadActionLog::create([
                'lead_id' => $lead->id,
                'user_id' => auth()->id(),
                'action' => $action,
                'description' => $description,
                'old_values' => $oldValues,
                'new_values' => $newValues,
            ]);
        } catch (\Throwable $e) {
            Log::error('LeadActionLogger: failed to write log', [
                'lead_id' => $lead->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
