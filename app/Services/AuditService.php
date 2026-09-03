<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    public function record(string $event, ?Model $subject = null, array $old = [], array $new = [], ?string $reason = null): AuditLog
    {
        return AuditLog::create([
            'user_id' => auth()->id(), 'event' => $event,
            'subject_type' => $subject?->getMorphClass(), 'subject_id' => $subject?->getKey(),
            'old_values' => $this->redact($old), 'new_values' => $this->redact($new), 'reason' => $reason,
            'ip_address' => request()?->ip(), 'user_agent' => request()?->userAgent(),
        ]);
    }

    private function redact(array $values): array
    {
        return collect($values)->except(['password', 'remember_token', 'bank_details'])->all();
    }
}
